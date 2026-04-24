<?php

declare(strict_types=1);

namespace Infrastructure\Redis\RateLimit;

final readonly class SlidingWindowResult
{
    public function __construct(
        public bool $allowed,
        public int $remaining,
        public int $retryAfterSeconds,
    ) {}
}
