<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Search\ProductObserver;
use Override;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void {}

    public function boot(): void
    {
        Product::observe($this->app->make(ProductObserver::class));
    }
}
