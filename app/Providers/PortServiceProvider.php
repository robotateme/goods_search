<?php

declare(strict_types=1);

namespace App\Providers;

use App\Jobs\LaravelQueueCommandMapper;
use Application\Contracts\Queue\QueueBus;
use Application\Contracts\Search\ProductSearch;
use Application\Contracts\Search\ProductSearchIndexer;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Database\Search\DatabaseProductSearch;
use Infrastructure\Database\Search\DatabaseProductSearchIndexer;
use Infrastructure\Ports\Queue\CommandDeduplicationKeyResolver;
use Infrastructure\Ports\Queue\DeduplicatingQueueBus;
use Infrastructure\Ports\Queue\LaravelQueueBus;
use Infrastructure\Ports\Queue\QueueCommandJobMapper;
use Infrastructure\Redis\Queue\RedisQueueDeduplicator;
use Infrastructure\Redis\ScriptResolver;
use Infrastructure\Search\CachedProductSearch;
use Infrastructure\Search\MeilisearchProductSearch;
use Infrastructure\Search\MeilisearchProductSearchIndexer;
use Infrastructure\Search\ProductPageCacheSerializer;
use Infrastructure\Search\ProductSearchCacheVersionManager;
use Meilisearch\Client;
use Override;
use UnexpectedValueException;

class PortServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(QueueCommandJobMapper::class, LaravelQueueCommandMapper::class);
        $this->app->singleton(RedisQueueDeduplicator::class, fn () => new RedisQueueDeduplicator(
            $this->app->make(RedisFactory::class),
            $this->app->make(ScriptResolver::class),
            $this->app->make(Repository::class),
        ));
        $this->app->singleton(CommandDeduplicationKeyResolver::class);
        $this->app->singleton(QueueBus::class, fn () => new DeduplicatingQueueBus(
            $this->app->make(LaravelQueueBus::class),
            $this->app->make(RedisQueueDeduplicator::class),
            $this->app->make(CommandDeduplicationKeyResolver::class),
        ));
        $this->app->singleton(Client::class, fn () => new Client(
            $this->stringConfig('services.meilisearch.host'),
            $this->nullableStringConfig('services.meilisearch.key'),
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

    private function stringConfig(string $key): string
    {
        $value = config($key);

        if (! is_string($value)) {
            throw new UnexpectedValueException(sprintf('Config value "%s" must be a string.', $key));
        }

        return $value;
    }

    private function nullableStringConfig(string $key): ?string
    {
        $value = config($key);

        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException(sprintf('Config value "%s" must be a string or null.', $key));
    }
}
