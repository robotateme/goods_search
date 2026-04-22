<?php
declare(strict_types=1);


namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearchIndexer;

final class DatabaseProductSearchIndexer implements ProductSearchIndexer
{
    public function __construct(
        private ProductSearchCacheVersionManager $cacheVersionManager,
    ) {
    }

    public function syncSettings(): void
    {
        $this->cacheVersionManager->bump();
    }

    public function importAll(): void
    {
        $this->cacheVersionManager->bump();
    }

    public function index(int $productId): void
    {
        $this->cacheVersionManager->bump();
    }

    public function remove(int $productId): void
    {
        $this->cacheVersionManager->bump();
    }
}
