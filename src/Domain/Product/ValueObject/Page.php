<?php
declare(strict_types=1);

namespace Domain\Product\ValueObject;

use InvalidArgumentException;

final readonly class Page
{
    public function __construct(
        private int $value,
    ) {
        if ($this->value < 1) {
            throw new InvalidArgumentException('Page must be greater than zero.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
