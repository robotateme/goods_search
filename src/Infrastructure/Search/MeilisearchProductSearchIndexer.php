<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use Application\Contracts\Search\ProductSearchIndexer;
use Domain\Product\Product;
use Meilisearch\Client;

final readonly class MeilisearchProductSearchIndexer implements ProductSearchIndexer
{
    public function __construct(
        private readonly Client $client,
        private readonly ProductSearchDocumentMapper $mapper,
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    public function syncSettings(): void
    {
        $this->client
            ->index((string) config('search.products.index'))
            ->updateSettings([
                'filterableAttributes' => config('search.products.filterable_attributes', []),
                'sortableAttributes' => config('search.products.sortable_attributes', []),
            ]);
    }

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
                    ->index((string) config('search.products.index'))
                    ->addDocuments($documents, 'id');
            }
        });
    }

    public function index(int $productId): void
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            return;
        }

        $this->client
            ->index((string) config('search.products.index'))
            ->addDocuments([$this->mapper->map($product)], 'id');
    }

    public function remove(int $productId): void
    {
        $this->client
            ->index((string) config('search.products.index'))
            ->deleteDocument((string) $productId);
    }
}
