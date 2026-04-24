<?php

declare(strict_types=1);

namespace Infrastructure\Database\Search;

use Application\Contracts\Search\ProductSearchIndexer;
use Infrastructure\Search\ProductSearchCacheVersionManager;
use Override;

final class DatabaseProductSearchIndexer implements ProductSearchIndexer
{
    public function __construct(
        private ProductSearchCacheVersionManager $cacheVersionManager,
    ) {}

    #[Override]
    public function syncSettings(): void
    {
        $this->cacheVersionManager->bump();
    }

    #[Override]
    public function importAll(): void
    {
        $this->cacheVersionManager->bump();
    }

    #[Override]
    public function index(int $productId): void
    {
        $this->cacheVersionManager->bump();
    }

    #[Override]
    public function remove(int $productId): void
    {
        $this->cacheVersionManager->bump();
    }
}
