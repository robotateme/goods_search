<?php

declare(strict_types=1);

namespace Domain\Product\ValueObject;

use InvalidArgumentException;

final readonly class Price
{
    public function __construct(
        private string $value,
    ) {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $this->value)) {
            throw new InvalidArgumentException('Price must be a decimal string with up to 2 fraction digits.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
