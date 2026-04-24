<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class ProductSearchCacheVersionManager
{
    public function __construct(
        private CacheFactory $cacheFactory,
    ) {}

    public function currentVersion(): int
    {
        $version = $this->cache()->get($this->versionKey());

        if (! is_int($version)) {
            $version = 1;
            $this->cache()->forever($this->versionKey(), $version);
        }

        return $version;
    }

    public function bump(): int
    {
        $version = $this->currentVersion() + 1;
        $this->cache()->forever($this->versionKey(), $version);

        return $version;
    }

    private function cache(): CacheRepository
    {
        return $this->cacheFactory->store($this->cacheStore());
    }

    private function versionKey(): string
    {
        $key = config('search.cache.version_key');

        if (! is_string($key)) {
            throw new \UnexpectedValueException('Search cache version key config must be a string.');
        }

        return $key;
    }

    private function cacheStore(): string
    {
        $store = config('search.cache.store');

        if (! is_string($store)) {
            throw new \UnexpectedValueException('Search cache store config must be a string.');
        }

        return $store;
    }
}
