<?php

declare(strict_types=1);

namespace App\Exception;

class AccessDeniedException extends BaseException
{
    protected string $errorCode = 'ACCESS_DENIED';

    public function __construct(
        string $message = 'Access denied',
        ?string $resource = null,
        ?string $action = null,
        ?\Throwable $previous = null
    ) {
        $context = [];

        if ($resource) {
            $context['resource'] = $resource;
        }

        if ($action) {
            $context['action'] = $action;
        }

        parent::__construct($message, 403, $previous, $context);
    }

    public function getResource(): ?string
    {
        return $this->context['resource'] ?? null;
    }

    public function getAction(): ?string
    {
        return $this->context['action'] ?? null;
    }
}
