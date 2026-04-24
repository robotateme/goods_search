<?php

declare(strict_types=1);

namespace App\Jobs;

use Application\Commands\RemoveProductInSearchCommand;
use Application\Handlers\RemoveProductInSearchHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RemoveProductFromSearchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $productId,
    ) {}

    public function handle(RemoveProductInSearchHandler $handler): void
    {
        $handler->handle(new RemoveProductInSearchCommand($this->productId));
    }
}
