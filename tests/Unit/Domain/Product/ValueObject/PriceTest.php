<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product\ValueObject;

use Domain\Product\ValueObject\Price;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function test_it_normalizes_decimal_strings(): void
    {
        self::assertSame('149.90', new Price('149.9')->value());
        self::assertSame('149.00', new Price('149')->value());
    }

    public function test_it_converts_between_major_and_minor_units(): void
    {
        $price = Price::fromInput('149.99');

        self::assertSame(14999, $price->minorUnits());
        self::assertSame('149.99', Price::fromMinorUnits(14999)->value());
    }
}
