<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RateLimit
{
    public function __construct(
        public readonly int $requests = 100,
        public readonly int $window = 3600, // seconds
        public readonly string $identifier = 'default'
    ) {
    }
}