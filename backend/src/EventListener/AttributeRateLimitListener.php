<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Attribute\RateLimit;
use App\Service\RateLimitService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER, priority: 10)]
class AttributeRateLimitListener
{
    public function __construct(
        private readonly RateLimitService $rateLimitService
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (! is_array($controller)) {
            return;
        }

        $controllerObject = $controller[0];
        $method = $controller[1];

        $reflection = new \ReflectionMethod($controllerObject, $method);
        $rateLimitAttribute = $reflection->getAttributes(RateLimit::class)[0] ?? null;

        if (! $rateLimitAttribute) {
            // Check class-level attribute
            $classReflection = new \ReflectionClass($controllerObject);
            $rateLimitAttribute = $classReflection->getAttributes(RateLimit::class)[0] ?? null;
        }

        if (! $rateLimitAttribute) {
            return;
        }

        /** @var RateLimit $rateLimit */
        $rateLimit = $rateLimitAttribute->newInstance();
        $request = $event->getRequest();

        $identifier = $this->rateLimitService->generateIdentifierFromRequest($request);

        // Add attribute identifier suffix for more granular control
        if ('default' !== $rateLimit->identifier) {
            $identifier .= '_'.$rateLimit->identifier;
        }

        if (! $this->rateLimitService->isAllowed($identifier, $rateLimit->requests, $rateLimit->window)) {
            $remaining = $this->rateLimitService->getRemainingRequests($identifier, $rateLimit->requests, $rateLimit->window);
            $resetTime = $this->rateLimitService->getResetTime($identifier, $rateLimit->window);

            $response = new JsonResponse([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $resetTime ? $resetTime - time() : $rateLimit->window,
            ], Response::HTTP_TOO_MANY_REQUESTS);

            // Add rate limiting headers
            $response->headers->set('X-RateLimit-Limit', (string) $rateLimit->requests);
            $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

            if ($resetTime) {
                $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
            }

            $event->setController(function () use ($response) {
                return $response;
            });
        }
    }
}
