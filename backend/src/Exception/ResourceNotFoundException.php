<?php

declare(strict_types=1);

namespace App\Exception;

class ResourceNotFoundException extends BaseException
{
    protected string $errorCode = 'RESOURCE_NOT_FOUND';

    public function __construct(
        string $resourceType = 'Resource',
        mixed $identifier = null,
        ?\Throwable $previous = null
    ) {
        $message = $identifier
            ? "{$resourceType} with identifier '{$identifier}' not found"
            : "{$resourceType} not found";

        $context = [
            'resource_type' => $resourceType,
            'identifier' => $identifier,
        ];

        parent::__construct($message, 404, $previous, $context);
    }

    public function getResourceType(): string
    {
        return $this->context['resource_type'] ?? 'Resource';
    }

    public function getIdentifier(): mixed
    {
        return $this->context['identifier'] ?? null;
    }
}
