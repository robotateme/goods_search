<?php
declare(strict_types=1);

namespace App\Providers;

use Application\Contracts\Queue\QueueBus;
use Application\Contracts\Search\ProductSearch;
use Application\Contracts\Search\ProductSearchIndexer;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Queue\DeduplicatingQueueBus;
use Infrastructure\Queue\LaravelQueueBus;
use Infrastructure\Queue\RedisQueueDeduplicator;
use Infrastructure\Search\CachedProductSearch;
use Infrastructure\Search\DatabaseProductSearch;
use Infrastructure\Search\DatabaseProductSearchIndexer;
use Infrastructure\Search\MeilisearchProductSearch;
use Infrastructure\Search\MeilisearchProductSearchIndexer;
use Infrastructure\Search\ProductSearchCacheVersionManager;
use Meilisearch\Client;

class PortServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisQueueDeduplicator::class, fn () => new RedisQueueDeduplicator(
            $this->app->make(RedisFactory::class),
            $this->app->make(\Infrastructure\Support\ScriptResolver::class),
            $this->app->make(\Illuminate\Contracts\Config\Repository::class),
        ));
        $this->app->singleton(QueueBus::class, fn () => new DeduplicatingQueueBus(
            $this->app->make(LaravelQueueBus::class),
            $this->app->make(RedisQueueDeduplicator::class),
        ));
        $this->app->singleton(Client::class, fn () => new Client(
            (string) config('services.meilisearch.host'),
            config('services.meilisearch.key'),
        ));
        $this->app->singleton(ProductSearchCacheVersionManager::class);
        $this->app->bind(ProductSearch::class, function () {
            $baseSearch = config('search.driver') === 'meilisearch'
                ? $this->app->make(MeilisearchProductSearch::class)
                : $this->app->make(DatabaseProductSearch::class);

            return new CachedProductSearch(
                $baseSearch,
                $this->app->make(CacheFactory::class),
                $this->app->make(ProductSearchCacheVersionManager::class),
            );
        });
        $this->app->bind(ProductSearchIndexer::class, function () {
            return config('search.driver') === 'meilisearch'
                ? $this->app->make(MeilisearchProductSearchIndexer::class)
                : $this->app->make(DatabaseProductSearchIndexer::class);
        });
    }
}
