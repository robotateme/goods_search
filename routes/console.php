<?php
declare(strict_types=1);


use Application\Contracts\Queue\QueueBus;
use App\Jobs\ImportProductsToSearchJob;
use App\Jobs\SyncProductSearchSettingsJob;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function (): void {
    echo Inspiring::quote().PHP_EOL;
})->purpose('Display an inspiring quote');

Artisan::command('search:products:sync', function (QueueBus $queueBus): int {
    $queueBus->dispatch(new SyncProductSearchSettingsJob());
    echo 'Product search settings sync queued.'.PHP_EOL;

    return Command::SUCCESS;
})->purpose('Sync product search index settings');

Artisan::command('search:products:import', function (QueueBus $queueBus): int {
    $queueBus->dispatch(new ImportProductsToSearchJob());
    echo 'Products import queued.'.PHP_EOL;

    return Command::SUCCESS;
})->purpose('Import products into the search index');

Artisan::command('catalog:seed {products=5000} {categories=12}', function (int $products, int $categories): int {
    (new CatalogSeeder(
        categoriesCount: $categories,
        productsCount: $products,
    ))->run();

    echo sprintf('Catalog seeded: %d categories, %d products.', $categories, $products).PHP_EOL;

    return Command::SUCCESS;
})->purpose('Seed catalog data for search and load testing');
