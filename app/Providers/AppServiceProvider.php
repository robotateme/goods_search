<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
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
            $this->app->extend(
                LaravelModelMakeCommand::class,
                fn (LaravelModelMakeCommand $command, Application $app): LaravelModelMakeCommand => new class($app->make(Filesystem::class)) extends LaravelModelMakeCommand
                {
                    #[Override]
                    protected function rootNamespace(): string
                    {
                        return 'Infrastructure\\';
                    }

                    #[Override]
                    protected function getDefaultNamespace($rootNamespace): string
                    {
                        return $rootNamespace.'\\Database\\Eloquent';
                    }

                    #[Override]
                    protected function getPath($name): string
                    {
                        $relativePath = Str::replaceFirst($this->rootNamespace(), '', $name);

                        return $this->laravel->basePath('src/Infrastructure/'.str_replace('\\', '/', $relativePath).'.php');
                    }
                },
            );
        }
    }

    public function boot(): void
    {
        Product::observe($this->app->make(ProductObserver::class));
    }
}
