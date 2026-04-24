<?php

declare(strict_types=1);

namespace App\Providers;

use Application\Contracts\Catalog\CatalogSeeder as CatalogSeederContract;
use Application\Contracts\Repositories\ProductRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Database\CatalogSeeder;
use Infrastructure\Database\ProductRepository;
use Override;

class RepositoryServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(CatalogSeederContract::class, CatalogSeeder::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    }
}
