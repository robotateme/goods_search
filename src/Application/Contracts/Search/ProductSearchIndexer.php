<?php

namespace Application\Contracts\Search;

interface ProductSearchIndexer
{
    public function syncSettings(): void;

    public function importAll(): void;

    public function index(int $productId): void;

    public function remove(int $productId): void;
}
