<?php

namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearchIndexer;
use App\Models\Product;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ProductObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly ProductSearchIndexer $indexer,
    ) {
    }

    public function saved(Product $product): void
    {
        $this->indexer->index($product->getKey());
    }

    public function deleted(Product $product): void
    {
        $this->indexer->remove($product->getKey());
    }
}
