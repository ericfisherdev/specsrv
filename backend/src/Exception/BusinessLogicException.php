<?php

declare(strict_types=1);

namespace App\Exception;

class BusinessLogicException extends BaseException
{
    protected string $errorCode = 'BUSINESS_LOGIC_ERROR';

    public function __construct(
        string $message = 'Business logic error',
        string $operation = null,
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        $context = [];
        
        if ($operation) {
            $context['operation'] = $operation;
        }

        parent::__construct($message, $code, $previous, $context);
    }

    public function getOperation(): ?string
    {
        return $this->context['operation'] ?? null;
    }
}