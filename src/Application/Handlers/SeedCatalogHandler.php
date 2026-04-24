<?php

declare(strict_types=1);

namespace Application\Handlers;

use Application\Commands\SeedCatalogCommand;
use Application\Contracts\Catalog\CatalogSeeder;

final readonly class SeedCatalogHandler
{
    public function __construct(
        private CatalogSeeder $catalogSeeder,
    ) {}

    public function handle(SeedCatalogCommand $command): void
    {
        $this->catalogSeeder->seed($command->productsCount, $command->categoriesCount);
    }
}
