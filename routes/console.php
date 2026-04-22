<?php
declare(strict_types=1);


use Application\Contracts\Queue\QueueBus;
use App\Jobs\ImportProductsToSearchJob;
use App\Jobs\SyncProductSearchSettingsJob;
use Database\Seeders\CatalogSeeder;
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

Artisan::command('catalog:seed {products=5000} {categories=12}', function (int $products, int $categories): int {
    (new CatalogSeeder(
        categoriesCount: $categories,
        productsCount: $products,
    ))->run();

    $this->info(sprintf('Catalog seeded: %d categories, %d products.', $categories, $products));

    return self::SUCCESS;
})->purpose('Seed catalog data for search and load testing');
