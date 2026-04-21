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

class MeilisearchProductSearch implements ProductSearch
{
    public function __construct(
        private readonly Client $client,
        private readonly DatabaseProductSearch $databaseProductSearch,
        private readonly ProductRepositoryInterface $products,
    ) {
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

        $ids = collect($results['hits'] ?? [])->pluck('id')->map(fn (mixed $id) => (int) $id)->all();
        $positions = array_flip($ids);

        $products = collect($this->products->getByIds($ids))
            ->sortBy(fn (Product $product) => $positions[$product->id] ?? PHP_INT_MAX)
            ->values()
            ->all();

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
