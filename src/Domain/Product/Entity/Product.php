<?php

declare(strict_types=1);

namespace Domain\Product\Entity;

use DateTimeImmutable;
use Domain\Product\ValueObject\CategoryId;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductId;
use Domain\Product\ValueObject\Rating;

final readonly class Product
{
    public function __construct(
        public ProductId $id,
        public string $name,
        public Price $price,
        public CategoryId $categoryId,
        public bool $inStock,
        public Rating $rating,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {}
}
