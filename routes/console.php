<?php
declare(strict_types=1);


use Application\Contracts\Search\ProductSearchIndexer;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (Command $command): void {
    $command->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('search:products:sync', function (Command $command, ProductSearchIndexer $indexer): int {
    $indexer->syncSettings();
    $command->info('Product search settings synchronized.');

    return Command::SUCCESS;
})->purpose('Sync product search index settings');

Artisan::command('search:products:import', function (Command $command, ProductSearchIndexer $indexer): int {
    $indexer->importAll();
    $command->info('Products imported into search index.');

    return Command::SUCCESS;
})->purpose('Import products into the search index');
