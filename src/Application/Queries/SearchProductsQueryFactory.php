<?php

declare(strict_types=1);

namespace Application\Queries;

use Domain\Product\Search\ProductSearchCriteria;
use Domain\Product\Search\ProductSort;

final class SearchProductsQueryFactory
{
    /**
     * @param  array{
     *     q?: string|null,
     *     price_from?: float|int|string|null,
     *     price_to?: float|int|string|null,
     *     category_id?: int|string|null,
     *     in_stock?: bool|null,
     *     rating_from?: float|int|string|null,
     *     sort?: string|null,
     *     page?: int|string|null,
     *     per_page?: int|string|null
     * }  $filters
     */
    public function fromFilters(array $filters): SearchProductsQuery
    {
        $page = $this->intOrDefault($filters['page'] ?? null, 1);
        $perPage = $this->intOrDefault($filters['per_page'] ?? null, 15);

        return new SearchProductsQuery(
            ProductSearchCriteria::fromInput(
                query: $filters['q'] ?? null,
                priceFrom: $this->nullableNumericString($filters['price_from'] ?? null),
                priceTo: $this->nullableNumericString($filters['price_to'] ?? null),
                categoryId: $this->nullableInt($filters['category_id'] ?? null),
                inStock: $this->nullableBool($filters['in_stock'] ?? null),
                ratingFrom: $this->nullableFloat($filters['rating_from'] ?? null),
                sort: ProductSort::tryFrom($filters['sort'] ?? ProductSort::Newest->value) ?? ProductSort::Newest,
                perPage: $perPage,
                page: $page,
            ),
        );
    }

    private function intOrDefault(int|string|null $value, int $default): int
    {
        if ($value === null) {
            return $default;
        }

        return is_int($value) ? $value : (int) $value;
    }

    private function nullableNumericString(int|float|string|null $value): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        return (string) $value;
    }

    private function nullableInt(int|string|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return is_int($value) ? $value : (int) $value;
    }

    private function nullableFloat(int|float|string|null $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return is_float($value) ? $value : (float) $value;
    }

    private function nullableBool(?bool $value): ?bool
    {
        return $value;
    }
}
