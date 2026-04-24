<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use App\Jobs\IndexProductInSearchJob;
use App\Jobs\RemoveProductFromSearchJob;
use App\Models\Product;
use Application\Contracts\Queue\QueueBus;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final readonly class ProductObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly QueueBus $queueBus,
    ) {}

    public function saved(Product $product): void
    {
        $this->queueBus->dispatch(new IndexProductInSearchJob($product->getKey()));
    }

    public function deleted(Product $product): void
    {
        $this->queueBus->dispatch(new RemoveProductFromSearchJob($product->getKey()));
    }
}
