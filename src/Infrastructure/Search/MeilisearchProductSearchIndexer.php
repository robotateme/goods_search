<?php
declare(strict_types=1);


namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearchIndexer;
use App\Models\Product;
use Meilisearch\Client;

class MeilisearchProductSearchIndexer implements ProductSearchIndexer
{
    public function __construct(
        private readonly Client $client,
        private readonly ProductSearchDocumentMapper $mapper,
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

        Product::query()
            ->orderBy('id')
            ->chunk(500, function ($products): void {
                $documents = $products
                    ->map(fn (Product $product) => $this->mapper->map($product))
                    ->all();

                if ($documents !== []) {
                    $this->client
                        ->index((string) config('search.products.index'))
                        ->addDocuments($documents, 'id');
                }
            });
    }

    public function index(int $productId): void
    {
        $product = Product::query()->find($productId);

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
