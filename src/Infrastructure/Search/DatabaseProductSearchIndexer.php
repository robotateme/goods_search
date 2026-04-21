<?php

namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearchIndexer;

class DatabaseProductSearchIndexer implements ProductSearchIndexer
{
    public function syncSettings(): void
    {
    }

    public function importAll(): void
    {
    }

    public function index(int $productId): void
    {
    }

    public function remove(int $productId): void
    {
    }
}
