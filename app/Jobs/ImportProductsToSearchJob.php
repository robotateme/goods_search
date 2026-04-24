<?php

declare(strict_types=1);

namespace App\Jobs;

use Application\Commands\ImportProductsToSearchCommand;
use Application\Handlers\ImportProductsToSearchHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ImportProductsToSearchJob implements ShouldQueue
{
    use Queueable;

    public function handle(ImportProductsToSearchHandler $handler): void
    {
        $handler->handle(new ImportProductsToSearchCommand());
    }
}
