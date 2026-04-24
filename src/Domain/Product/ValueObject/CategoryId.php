<?php

declare(strict_types=1);

namespace Domain\Product\ValueObject;

use InvalidArgumentException;

final readonly class CategoryId
{
    public function __construct(
        private int $value,
    ) {
        if ($this->value < 1) {
            throw new InvalidArgumentException('Category id must be positive.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
