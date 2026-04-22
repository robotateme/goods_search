<?php
declare(strict_types=1);

namespace Domain\Product\ValueObject;

use InvalidArgumentException;

final readonly class Rating
{
    public function __construct(
        private float $value,
    ) {
        if ($this->value < 0.0 || $this->value > 5.0) {
            throw new InvalidArgumentException('Rating must be between 0 and 5.');
        }
    }

    public function value(): float
    {
        return $this->value;
    }
}
