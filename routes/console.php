<?php
declare(strict_types=1);


use Application\Contracts\Queue\QueueBus;
use App\Jobs\ImportProductsToSearchJob;
use App\Jobs\SyncProductSearchSettingsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('search:products:sync', function (QueueBus $queueBus): int {
    $queueBus->dispatch(new SyncProductSearchSettingsJob());
    $this->info('Product search settings sync queued.');

    return self::SUCCESS;
})->purpose('Sync product search index settings');

Artisan::command('search:products:import', function (QueueBus $queueBus): int {
    $queueBus->dispatch(new ImportProductsToSearchJob());
    $this->info('Products import queued.');

    return self::SUCCESS;
})->purpose('Import products into the search index');
