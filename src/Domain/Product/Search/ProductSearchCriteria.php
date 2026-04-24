<?php

declare(strict_types=1);

namespace Domain\Product\Search;

use Domain\Product\ValueObject\CategoryId;
use Domain\Product\ValueObject\Page;
use Domain\Product\ValueObject\PerPage;

final readonly class ProductSearchCriteria
{
    public function __construct(
        public ?string $query,
        public ?float $priceFrom,
        public ?float $priceTo,
        public ?CategoryId $categoryId,
        public ?bool $inStock,
        public ?float $ratingFrom,
        public ProductSort $sort,
        public PerPage $perPage,
        public Page $page,
    ) {}

    public function hasQuery(): bool
    {
        return $this->query !== null && trim($this->query) !== '';
    }

    public static function fromInput(
        ?string $query,
        ?float $priceFrom,
        ?float $priceTo,
        ?int $categoryId,
        ?bool $inStock,
        ?float $ratingFrom,
        ProductSort $sort,
        int $perPage,
        int $page,
    ): self {
        $normalizedQuery = $query !== null ? trim($query) : null;

        return new self(
            query: $normalizedQuery === '' ? null : $normalizedQuery,
            priceFrom: $priceFrom,
            priceTo: $priceTo,
            categoryId: $categoryId !== null ? new CategoryId($categoryId) : null,
            inStock: $inStock,
            ratingFrom: $ratingFrom,
            sort: $sort,
            perPage: new PerPage($perPage),
            page: new Page($page),
        );
    }
}
