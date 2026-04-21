<?php
declare(strict_types=1);

namespace App\Jobs;

use Application\Contracts\Search\ProductSearchIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportProductsToSearchJob implements ShouldQueue
{
    use Queueable;

    public function handle(ProductSearchIndexer $indexer): void
    {
        $indexer->importAll();
    }
}
