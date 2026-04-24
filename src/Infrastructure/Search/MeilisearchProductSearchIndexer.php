<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use Application\Contracts\Search\ProductSearchIndexer;
use Domain\Product\Entity\Product;
use Meilisearch\Client;
use Override;

final readonly class MeilisearchProductSearchIndexer implements ProductSearchIndexer
{
    public function __construct(
        private readonly Client $client,
        private readonly ProductSearchDocumentMapper $mapper,
        private readonly ProductRepositoryInterface $products,
        private readonly ProductSearchCacheVersionManager $cacheVersionManager,
    ) {}

    #[Override]
    public function syncSettings(): void
    {
        $this->client
            ->index($this->indexName())
            ->updateSettings([
                'filterableAttributes' => $this->stringListConfig('search.products.filterable_attributes'),
                'sortableAttributes' => $this->stringListConfig('search.products.sortable_attributes'),
            ]);
        $this->cacheVersionManager->bump();
    }

    #[Override]
    public function importAll(): void
    {
        $this->syncSettings();

        $this->products->chunkById(500, function ($products): void {
            $documents = array_map(
                fn (Product $product): array => $this->mapper->map($product),
                $products,
            );

            if ($documents !== []) {
                $this->client
                    ->index($this->indexName())
                    ->addDocuments($documents, 'id');
            }
        });
    }

    #[Override]
    public function index(int $productId): void
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            return;
        }

        $this->client
            ->index($this->indexName())
            ->addDocuments([$this->mapper->map($product)], 'id');
        $this->cacheVersionManager->bump();
    }

    #[Override]
    public function remove(int $productId): void
    {
        $this->client
            ->index($this->indexName())
            ->deleteDocument((string) $productId);
        $this->cacheVersionManager->bump();
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
     * @return list<string>
     */
    private function stringListConfig(string $key): array
    {
        $value = config($key, []);

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }
}
