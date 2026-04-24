<?php

declare(strict_types=1);

namespace App\Providers;

use Application\Contracts\Queue\QueueBus;
use Application\Contracts\Search\ProductSearch;
use Application\Contracts\Search\ProductSearchIndexer;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Database\Search\DatabaseProductSearch;
use Infrastructure\Database\Search\DatabaseProductSearchIndexer;
use Infrastructure\Ports\Queue\DeduplicatingQueueBus;
use Infrastructure\Ports\Queue\LaravelQueueBus;
use Infrastructure\Redis\Queue\RedisQueueDeduplicator;
use Infrastructure\Redis\ScriptResolver;
use Infrastructure\Search\CachedProductSearch;
use Infrastructure\Search\MeilisearchProductSearch;
use Infrastructure\Search\MeilisearchProductSearchIndexer;
use Infrastructure\Search\ProductPageCacheSerializer;
use Infrastructure\Search\ProductSearchCacheVersionManager;
use Meilisearch\Client;
use Override;

class PortServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(RedisQueueDeduplicator::class, fn () => new RedisQueueDeduplicator(
            $this->app->make(RedisFactory::class),
            $this->app->make(ScriptResolver::class),
            $this->app->make(Repository::class),
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
                $this->app->make(ProductPageCacheSerializer::class),
            );
        });
        $this->app->bind(ProductSearchIndexer::class, function () {
            return config('search.driver') === 'meilisearch'
                ? $this->app->make(MeilisearchProductSearchIndexer::class)
                : $this->app->make(DatabaseProductSearchIndexer::class);
        });
    }
}
