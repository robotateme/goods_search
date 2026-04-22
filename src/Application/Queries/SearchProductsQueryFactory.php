<?php
declare(strict_types=1);

namespace Application\Queries;

use Domain\Product\ProductSearchCriteria;
use Domain\Product\ProductSort;

final class SearchProductsQueryFactory
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function fromFilters(array $filters): SearchProductsQuery
    {
        $page = isset($filters['page']) ? (int) $filters['page'] : 1;
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return new SearchProductsQuery(
            new ProductSearchCriteria(
                query: $filters['q'] ?? null,
                priceFrom: isset($filters['price_from']) ? (float) $filters['price_from'] : null,
                priceTo: isset($filters['price_to']) ? (float) $filters['price_to'] : null,
                categoryId: isset($filters['category_id']) ? (int) $filters['category_id'] : null,
                inStock: $filters['in_stock'] ?? null,
                ratingFrom: isset($filters['rating_from']) ? (float) $filters['rating_from'] : null,
                sort: ProductSort::tryFrom((string) ($filters['sort'] ?? ProductSort::Newest->value)) ?? ProductSort::Newest,
                perPage: $perPage,
                page: $page,
            ),
        );
    }
}
