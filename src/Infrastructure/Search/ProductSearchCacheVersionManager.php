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
        return $this->cacheFactory->store((string) config('search.cache.store'));
    }

    private function versionKey(): string
    {
        return (string) config('search.cache.version_key');
    }
}
