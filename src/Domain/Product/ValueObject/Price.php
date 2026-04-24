<?php

declare(strict_types=1);

namespace Domain\Product\ValueObject;

use InvalidArgumentException;

final readonly class Price
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = self::normalize($value);

        if (! preg_match('/^\d+\.\d{2}$/', $this->value)) {
            throw new InvalidArgumentException('Price must be a decimal string with up to 2 fraction digits.');
        }
    }

    public static function fromMinorUnits(int $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Price minor units must be greater than or equal to zero.');
        }

        return new self(sprintf('%d.%02d', intdiv($value, 100), $value % 100));
    }

    public static function fromInput(int|float|string $value): self
    {
        if (is_string($value)) {
            return new self($value);
        }

        if (is_int($value)) {
            return new self((string) $value);
        }

        return new self(number_format($value, 2, '.', ''));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function minorUnits(): int
    {
        [$whole, $fraction] = explode('.', $this->value);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private static function normalize(string $value): string
    {
        $normalized = trim($value);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            return $normalized;
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '00');

        return $whole.'.'.str_pad($fraction, 2, '0');
    }
}
