<?php
declare(strict_types=1);


namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearch;
use Illuminate\Pagination\LengthAwarePaginator;
use Meilisearch\Client;

class MeilisearchProductSearch implements ProductSearch
{
    public function __construct(
        private readonly Client $client,
        private readonly DatabaseProductSearch $databaseProductSearch,
    ) {
    }

    public function search(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        if (blank($filters['q'] ?? null)) {
            return $this->databaseProductSearch->search($filters, $perPage, $page);
        }

        $results = $this->client
            ->index((string) config('search.products.index'))
            ->rawSearch($filters['q'] ?? '', array_filter([
                'filter' => $this->buildFilterExpression($filters),
                'sort' => $this->buildSort($filters['sort'] ?? 'newest'),
                'hitsPerPage' => $perPage,
                'page' => $page,
            ]));

        $ids = collect($results['hits'] ?? [])->pluck('id')->map(fn (mixed $id) => (int) $id)->all();
        $positions = array_flip($ids);

        $products = \App\Models\Product::query()
            ->with('category')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (\App\Models\Product $product) => $positions[$product->getKey()] ?? PHP_INT_MAX)
            ->values();

        return new LengthAwarePaginator(
            $products,
            $results['totalHits'] ?? $results['estimatedTotalHits'] ?? count($ids),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    private function buildFilterExpression(array $filters): ?string
    {
        $expressions = [];

        if (isset($filters['price_from'])) {
            $expressions[] = 'price >= '.$filters['price_from'];
        }

        if (isset($filters['price_to'])) {
            $expressions[] = 'price <= '.$filters['price_to'];
        }

        if (isset($filters['category_id'])) {
            $expressions[] = 'category_id = '.$filters['category_id'];
        }

        if (isset($filters['in_stock'])) {
            $expressions[] = 'in_stock = '.($filters['in_stock'] ? 'true' : 'false');
        }

        if (isset($filters['rating_from'])) {
            $expressions[] = 'rating >= '.$filters['rating_from'];
        }

        return $expressions === [] ? null : implode(' AND ', $expressions);
    }

    private function buildSort(string $sort): array
    {
        return match ($sort) {
            'price_asc' => ['price:asc'],
            'price_desc' => ['price:desc'],
            'rating_desc' => ['rating:desc'],
            default => ['created_at_timestamp:desc'],
        };
    }
}
