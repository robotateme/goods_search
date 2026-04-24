<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Commands\IndexProductInSearchCommand;
use Application\Commands\RemoveProductInSearchCommand;
use Application\Contracts\Queue\QueueBus;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Infrastructure\Database\Eloquent\Product;

final readonly class ProductObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly QueueBus $queueBus,
    ) {}

    public function saved(Product $product): void
    {
        $this->queueBus->dispatch(new IndexProductInSearchCommand($this->productId($product)));
    }

    public function deleted(Product $product): void
    {
        $this->queueBus->dispatch(new RemoveProductInSearchCommand($this->productId($product)));
    }

    private function productId(Product $product): int
    {
        $id = $product->getKey();

        if (! is_int($id)) {
            throw new \UnexpectedValueException('Observed product key must be an integer.');
        }

        return $id;
    }
}
