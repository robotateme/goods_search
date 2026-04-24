<?php

declare(strict_types=1);

namespace Application\Handlers;

use Application\Commands\ImportProductsToSearchCommand;
use Application\Contracts\Search\ProductSearchIndexer;

final readonly class ImportProductsToSearchHandler
{
    public function __construct(
        private ProductSearchIndexer $indexer,
    ) {}

    public function handle(ImportProductsToSearchCommand $command): void
    {
        $this->indexer->importAll();
    }
}
