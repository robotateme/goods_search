<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use Application\Contracts\Search\ProductSearch;
use Domain\Product\Entity\Product;
use Domain\Product\Search\ProductPage;
use Domain\Product\Search\ProductSearchCriteria;
use Domain\Product\Search\ProductSort;
use Domain\Product\ValueObject\Price;
use Infrastructure\Database\Search\DatabaseProductSearch;
use Meilisearch\Client;
use Override;

final readonly class MeilisearchProductSearch implements ProductSearch
{
    public function __construct(
        private readonly Client $client,
        private readonly DatabaseProductSearch $databaseProductSearch,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @param  array{hits?: list<array<int|string, mixed>>, totalHits?: int, estimatedTotalHits?: int}  $results
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
            fn (Product $left, Product $right): int => ($positions[$left->id->value()] ?? PHP_INT_MAX) <=> ($positions[$right->id->value()] ?? PHP_INT_MAX),
        );

        return $products;
    }

    #[Override]
    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        if (! $criteria->hasQuery()) {
            return $this->databaseProductSearch->search($criteria);
        }

        $rawResults = $this->client
            ->index($this->indexName())
            ->rawSearch($criteria->query ?? '', array_filter([
                'filter' => $this->buildFilterExpression($criteria),
                'sort' => $this->buildSort($criteria->sort),
                'hitsPerPage' => $criteria->perPage->value(),
                'page' => $criteria->page->value(),
            ]));
        $results = $this->normalizeResults($rawResults);

        $ids = $this->extractIds($results);
        $products = $this->sortBySearchOrder($this->products->getByIds($ids), $ids);

        return new ProductPage(
            $products,
            $this->totalHits($results, $ids),
            $criteria->perPage,
            $criteria->page,
        );
    }

    private function buildFilterExpression(ProductSearchCriteria $criteria): ?string
    {
        $expressions = [];

        if ($criteria->priceFrom !== null) {
            $expressions[] = 'price >= '.Price::fromInput($criteria->priceFrom)->minorUnits();
        }

        if ($criteria->priceTo !== null) {
            $expressions[] = 'price <= '.Price::fromInput($criteria->priceTo)->minorUnits();
        }

        if ($criteria->categoryId !== null) {
            $expressions[] = 'category_id = '.$criteria->categoryId->value();
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

    private function indexName(): string
    {
        $index = config('search.products.index');

        if (! is_string($index)) {
            throw new \UnexpectedValueException('Search index name config must be a string.');
        }

        return $index;
    }

    /**
     * @param  mixed  $results
     * @return array{hits?: list<array<int|string, mixed>>, totalHits?: int, estimatedTotalHits?: int}
     */
    private function normalizeResults(mixed $results): array
    {
        if (! is_array($results)) {
            throw new \UnexpectedValueException('Meilisearch raw search response must be an array.');
        }

        $normalized = [];

        if (array_key_exists('hits', $results)) {
            if (! is_array($results['hits'])) {
                throw new \UnexpectedValueException('Meilisearch hits payload must be a list.');
            }

            $hits = [];

            foreach ($results['hits'] as $hit) {
                if (! is_array($hit)) {
                    throw new \UnexpectedValueException('Each Meilisearch hit must be an array.');
                }

                $hits[] = $hit;
            }

            $normalized['hits'] = $hits;
        }

        if (array_key_exists('totalHits', $results)) {
            if (! is_int($results['totalHits'])) {
                throw new \UnexpectedValueException('Meilisearch totalHits must be an integer.');
            }

            $normalized['totalHits'] = $results['totalHits'];
        }

        if (array_key_exists('estimatedTotalHits', $results)) {
            if (! is_int($results['estimatedTotalHits'])) {
                throw new \UnexpectedValueException('Meilisearch estimatedTotalHits must be an integer.');
            }

            $normalized['estimatedTotalHits'] = $results['estimatedTotalHits'];
        }

        return $normalized;
    }

    /**
     * @param  array{hits?: list<array<int|string, mixed>>, totalHits?: int, estimatedTotalHits?: int}  $results
     * @param  list<int>  $ids
     */
    private function totalHits(array $results, array $ids): int
    {
        return $results['totalHits'] ?? $results['estimatedTotalHits'] ?? count($ids);
    }
}
