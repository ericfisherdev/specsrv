<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\BaseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
class ExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $environment = 'dev'
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle JSON API requests
        if (!$this->isApiRequest($request)) {
            return;
        }

        $response = $this->createApiErrorResponse($exception);
        $event->setResponse($response);

        // Log the exception
        $this->logException($exception, $request->getPathInfo());
    }

    private function isApiRequest($request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/') ||
               $request->headers->get('Content-Type') === 'application/json' ||
               $request->headers->get('Accept') === 'application/json';
    }

    private function createApiErrorResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = $this->getStatusCode($exception);
        $errorCode = $this->getErrorCode($exception);
        $message = $this->getErrorMessage($exception);
        $context = $this->getErrorContext($exception);

        $data = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        // Add context if available
        if (!empty($context)) {
            $data['error']['details'] = $context;
        }

        // Add debug information in development
        if ($this->environment === 'dev') {
            $data['debug'] = [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return new JsonResponse($data, $statusCode);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($exception instanceof BaseException) {
            return $exception->getCode() > 0 ? $exception->getCode() : Response::HTTP_BAD_REQUEST;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getErrorCode(\Throwable $exception): string
    {
        if ($exception instanceof BaseException) {
            return $exception->getErrorCode();
        }

        if ($exception instanceof HttpException) {
            return 'HTTP_ERROR';
        }

        if ($exception instanceof AuthenticationException) {
            return 'AUTHENTICATION_ERROR';
        }

        return 'INTERNAL_SERVER_ERROR';
    }

    private function getErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof BaseException) {
            return $exception->getMessage();
        }

        if ($exception instanceof HttpException) {
            return $exception->getMessage() ?: 'HTTP Error';
        }

        if ($exception instanceof AuthenticationException) {
            return 'Authentication failed';
        }

        return $this->environment === 'dev' 
            ? $exception->getMessage() 
            : 'Internal server error';
    }

    private function getErrorContext(\Throwable $exception): array
    {
        if ($exception instanceof BaseException) {
            return $exception->getContext();
        }

        return [];
    }

    private function logException(\Throwable $exception, string $path): void
    {
        $context = [
            'exception' => get_class($exception),
            'path' => $path,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($exception instanceof BaseException) {
            $context = array_merge($context, $exception->getContext());
        }

        $this->logger->error('API Exception: ' . $exception->getMessage(), $context);
    }
}