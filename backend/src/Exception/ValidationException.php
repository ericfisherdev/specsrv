<?php

declare(strict_types=1);

namespace App\Exception;

class ValidationException extends BaseException
{
    protected string $errorCode = 'VALIDATION_ERROR';
    private array $violations = [];

    public function __construct(
        string $message = 'Validation failed',
        array $violations = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->violations = $violations;
        parent::__construct($message, $code, $previous, ['violations' => $violations]);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function setViolations(array $violations): static
    {
        $this->violations = $violations;
        $this->context['violations'] = $violations;
        return $this;
    }

    public function addViolation(string $field, string $message): static
    {
        $this->violations[$field] = $message;
        $this->context['violations'] = $this->violations;
        return $this;
    }

    public function hasViolations(): bool
    {
        return !empty($this->violations);
    }
}