<?php
declare(strict_types=1);

namespace Domain\Product;

final readonly class ProductSearchCriteria
{
    public function __construct(
        public ?string $query,
        public ?float $priceFrom,
        public ?float $priceTo,
        public ?int $categoryId,
        public ?bool $inStock,
        public ?float $ratingFrom,
        public ProductSort $sort,
        public int $perPage,
        public int $page,
    ) {
    }

    public function hasQuery(): bool
    {
        return $this->query !== null && trim($this->query) !== '';
    }
}
