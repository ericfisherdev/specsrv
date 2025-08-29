<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\RateLimitService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 50)]
class RateLimitListener
{
    public function __construct(
        private readonly RateLimitService $rateLimitService
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply rate limiting to API routes
        if (! str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Skip rate limiting for profiler and dev routes
        if (str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        $identifier = $this->rateLimitService->generateIdentifierFromRequest($request);
        $limits = $this->getRouteLimits($request->getPathInfo());

        if (! $this->rateLimitService->isAllowed($identifier, $limits['requests'], $limits['window'])) {
            $response = $this->createRateLimitResponse($identifier, $limits);
            $event->setResponse($response);
        }
    }

    private function getRouteLimits(string $path): array
    {
        $defaultLimits = $this->rateLimitService->getDefaultLimits();

        // More restrictive limits for authentication endpoints
        if (str_contains($path, '/auth/') || str_contains($path, '/login')) {
            return $defaultLimits['auth'];
        }

        // More restrictive limits for file upload endpoints
        if (str_contains($path, '/upload') || str_contains($path, '/files')) {
            return $defaultLimits['upload'];
        }

        // Default API limits
        return $defaultLimits['api'];
    }

    private function createRateLimitResponse(string $identifier, array $limits): JsonResponse
    {
        $remaining = $this->rateLimitService->getRemainingRequests($identifier, $limits['requests'], $limits['window']);
        $resetTime = $this->rateLimitService->getResetTime($identifier, $limits['window']);

        $response = new JsonResponse([
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $resetTime ? $resetTime - time() : $limits['window'],
        ], Response::HTTP_TOO_MANY_REQUESTS);

        // Add rate limiting headers
        $response->headers->set('X-RateLimit-Limit', (string) $limits['requests']);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        if ($resetTime) {
            $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        }

        return $response;
    }
}
