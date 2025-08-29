<?php

declare(strict_types=1);

namespace App\Exception;

class ServiceException extends BaseException
{
    protected string $errorCode = 'SERVICE_ERROR';

    public function __construct(
        string $message = 'Service error',
        ?string $service = null,
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        $context = [];

        if ($service) {
            $context['service'] = $service;
        }

        parent::__construct($message, $code, $previous, $context);
    }

    public function getService(): ?string
    {
        return $this->context['service'] ?? null;
    }
}
