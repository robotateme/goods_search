<?php

declare(strict_types=1);

namespace App\Jobs;

use Application\Commands\IndexProductInSearchCommand;
use Application\Handlers\IndexProductInSearchHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class IndexProductInSearchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $productId,
    ) {}

    public function handle(IndexProductInSearchHandler $handler): void
    {
        $handler->handle(new IndexProductInSearchCommand($this->productId));
    }
}
