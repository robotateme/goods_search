<?php

declare(strict_types=1);

namespace Application\Commands;

final readonly class SeedCatalogCommand
{
    public function __construct(
        public int $productsCount,
        public int $categoriesCount,
    ) {}
}
