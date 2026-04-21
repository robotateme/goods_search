<?php
declare(strict_types=1);

namespace App\Jobs;

use Application\Contracts\Search\ProductSearchIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveProductFromSearchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $productId,
    ) {
    }

    public function handle(ProductSearchIndexer $indexer): void
    {
        $indexer->remove($this->productId);
    }
}
