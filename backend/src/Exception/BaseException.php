<?php

declare(strict_types=1);

namespace App\Exception;

abstract class BaseException extends \Exception
{
    protected string $errorCode = 'GENERIC_ERROR';
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function addContext(string $key, mixed $value): static
    {
        $this->context[$key] = $value;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'error_code' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}
