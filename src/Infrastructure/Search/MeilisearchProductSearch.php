<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use Application\Contracts\Search\ProductSearch;
use Domain\Product\Product;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;
use Domain\Product\ProductSort;
use Meilisearch\Client;

final readonly class MeilisearchProductSearch implements ProductSearch
{
    public function __construct(
        private readonly Client $client,
        private readonly DatabaseProductSearch $databaseProductSearch,
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    /**
     * @param  array{hits?: list<array<string, mixed>>, totalHits?: int, estimatedTotalHits?: int}  $results
     * @return list<int>
     */
    private function extractIds(array $results): array
    {
        $ids = [];

        foreach ($results['hits'] ?? [] as $hit) {
            $id = $hit['id'] ?? null;

            if (is_int($id)) {
                $ids[] = $id;
                continue;
            }

            if (is_string($id) && ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * @param  list<Product>  $products
     * @param  list<int>  $ids
     * @return list<Product>
     */
    private function sortBySearchOrder(array $products, array $ids): array
    {
        $positions = array_flip($ids);

        usort(
            $products,
            fn (Product $left, Product $right): int => ($positions[$left->id] ?? PHP_INT_MAX) <=> ($positions[$right->id] ?? PHP_INT_MAX),
        );

        return $products;
    }

    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        if (! $criteria->hasQuery()) {
            return $this->databaseProductSearch->search($criteria);
        }

        $results = $this->client
            ->index((string) config('search.products.index'))
            ->rawSearch($criteria->query ?? '', array_filter([
                'filter' => $this->buildFilterExpression($criteria),
                'sort' => $this->buildSort($criteria->sort),
                'hitsPerPage' => $criteria->perPage,
                'page' => $criteria->page,
            ]));

        $ids = $this->extractIds($results);
        $products = $this->sortBySearchOrder($this->products->getByIds($ids), $ids);

        return new ProductPage(
            $products,
            $results['totalHits'] ?? $results['estimatedTotalHits'] ?? count($ids),
            $criteria->perPage,
            $criteria->page,
        );
    }

    private function buildFilterExpression(ProductSearchCriteria $criteria): ?string
    {
        $expressions = [];

        if ($criteria->priceFrom !== null) {
            $expressions[] = 'price >= '.$criteria->priceFrom;
        }

        if ($criteria->priceTo !== null) {
            $expressions[] = 'price <= '.$criteria->priceTo;
        }

        if ($criteria->categoryId !== null) {
            $expressions[] = 'category_id = '.$criteria->categoryId;
        }

        if ($criteria->inStock !== null) {
            $expressions[] = 'in_stock = '.($criteria->inStock ? 'true' : 'false');
        }

        if ($criteria->ratingFrom !== null) {
            $expressions[] = 'rating >= '.$criteria->ratingFrom;
        }

        return $expressions === [] ? null : implode(' AND ', $expressions);
    }

    /**
     * @return list<string>
     */
    private function buildSort(ProductSort $sort): array
    {
        return match ($sort) {
            ProductSort::PriceAsc => ['price:asc'],
            ProductSort::PriceDesc => ['price:desc'],
            ProductSort::RatingDesc => ['rating:desc'],
            default => ['created_at_timestamp:desc'],
        };
    }
}
