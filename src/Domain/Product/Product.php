<?php
declare(strict_types=1);

namespace Domain\Product;

use DateTimeImmutable;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public Price $price,
        public int $categoryId,
        public bool $inStock,
        public Rating $rating,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }
}
