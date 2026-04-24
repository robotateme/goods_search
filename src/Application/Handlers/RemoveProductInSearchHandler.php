<?php

declare(strict_types=1);

namespace Application\Handlers;

use Application\Commands\RemoveProductInSearchCommand;
use Application\Contracts\Search\ProductSearchIndexer;

final readonly class RemoveProductInSearchHandler
{
    public function __construct(
        private ProductSearchIndexer $indexer,
    ) {}

    public function handle(RemoveProductInSearchCommand $command): void
    {
        $this->indexer->remove($command->productId);
    }
}
