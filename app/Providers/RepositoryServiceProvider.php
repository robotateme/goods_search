<?php
declare(strict_types=1);

namespace App\Providers;

use Application\Contracts\Repositories\ProductRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\ProductRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    }
}
