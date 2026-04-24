<?php

declare(strict_types=1);

namespace Application\Handlers;

use Application\Commands\SyncProductSearchSettingsCommand;
use Application\Contracts\Search\ProductSearchIndexer;

final readonly class SyncProductSearchSettingsHandler
{
    public function __construct(
        private ProductSearchIndexer $indexer,
    ) {}

    public function handle(SyncProductSearchSettingsCommand $command): void
    {
        $this->indexer->syncSettings();
    }
}
