<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Console\ModelMakeCommand as LaravelModelMakeCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Infrastructure\Database\Eloquent\Product;
use Infrastructure\Search\ProductObserver;
use Override;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->extend(LaravelModelMakeCommand::class, fn ($command, $app) => new class($app['files']) extends LaravelModelMakeCommand
            {
                protected function rootNamespace(): string
                {
                    return 'Infrastructure\\';
                }

                protected function getDefaultNamespace($rootNamespace): string
                {
                    return $rootNamespace.'\\Database\\Eloquent';
                }

                protected function getPath($name): string
                {
                    $relativePath = Str::replaceFirst($this->rootNamespace(), '', $name);

                    return $this->laravel->basePath('src/Infrastructure/'.str_replace('\\', '/', $relativePath).'.php');
                }
            });
        }
    }

    public function boot(): void
    {
        Product::observe($this->app->make(ProductObserver::class));
    }
}
