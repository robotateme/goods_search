<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use App\Infrastructure\Database\Eloquent\Product;
use Application\Commands\IndexProductInSearchCommand;
use Application\Commands\RemoveProductInSearchCommand;
use Application\Contracts\Queue\QueueBus;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final readonly class ProductObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly QueueBus $queueBus,
    ) {}

    public function saved(Product $product): void
    {
        $this->queueBus->dispatch(new IndexProductInSearchCommand($product->getKey()));
    }

    public function deleted(Product $product): void
    {
        $this->queueBus->dispatch(new RemoveProductInSearchCommand($product->getKey()));
    }
}
