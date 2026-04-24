<?php

declare(strict_types=1);

namespace Application\Contracts\Catalog;

interface CatalogSeeder
{
    public function seed(int $productsCount, int $categoriesCount): void;
}
