<?php

declare(strict_types=1);

namespace Application\Handlers;

use Application\Commands\IndexProductInSearchCommand;
use Application\Contracts\Search\ProductSearchIndexer;

final readonly class IndexProductInSearchHandler
{
    public function __construct(
        private ProductSearchIndexer $indexer,
    ) {}

    public function handle(IndexProductInSearchCommand $command): void
    {
        $this->indexer->index($command->productId);
    }
}
