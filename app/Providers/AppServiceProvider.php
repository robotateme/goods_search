<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\ModelMakeCommand;
use App\Infrastructure\Database\Eloquent\Product;
use Illuminate\Foundation\Console\ModelMakeCommand as LaravelModelMakeCommand;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Search\ProductObserver;
use Override;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->extend(LaravelModelMakeCommand::class, fn ($command, $app) => new ModelMakeCommand($app['files']));
        }
    }

    public function boot(): void
    {
        Product::observe($this->app->make(ProductObserver::class));
    }
}
