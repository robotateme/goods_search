<?php

declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearch;
use Domain\Product\Search\ProductPage;
use Domain\Product\Search\ProductSearchCriteria;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Override;

final readonly class CachedProductSearch implements ProductSearch
{
    public function __construct(
        private ProductSearch $inner,
        private CacheFactory $cacheFactory,
        private ProductSearchCacheVersionManager $versionManager,
        private ProductPageCacheSerializer $serializer,
    ) {}

    #[Override]
    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        if (! (bool) config('search.cache.enabled', true)) {
            return $this->inner->search($criteria);
        }

        $cached = $this->cache()->get($this->key($criteria));

        if ($cached instanceof ProductPage) {
            return $cached;
        }

        if (is_array($cached)) {
            return $this->serializer->deserialize($cached);
        }

        $page = $this->inner->search($criteria);
        $this->cache()->put(
            $this->key($criteria),
            $this->serializer->serialize($page),
            $this->ttlSeconds(),
        );

        return $page;
    }

    private function cache(): CacheRepository
    {
        return $this->cacheFactory->store($this->cacheStore());
    }

    private function key(ProductSearchCriteria $criteria): string
    {
        $payload = [
            'version' => $this->versionManager->currentVersion(),
            'query' => $criteria->query,
            'price_from' => $criteria->priceFrom,
            'price_to' => $criteria->priceTo,
            'category_id' => $criteria->categoryId?->value(),
            'in_stock' => $criteria->inStock,
            'rating_from' => $criteria->ratingFrom,
            'sort' => $criteria->sort->value,
            'per_page' => $criteria->perPage->value(),
            'page' => $criteria->page->value(),
        ];

        return sprintf(
            '%s:%s',
            $this->cachePrefix(),
            sha1((string) json_encode($payload, JSON_THROW_ON_ERROR)),
        );
    }

    private function ttlSeconds(): int
    {
        $ttl = config('search.cache.ttl_seconds', 300);

        if (! is_int($ttl) && ! (is_string($ttl) && is_numeric($ttl))) {
            return 300;
        }

        return (int) $ttl;
    }

    private function cacheStore(): string
    {
        $store = config('search.cache.store');

        if (! is_string($store)) {
            throw new \UnexpectedValueException('Search cache store config must be a string.');
        }

        return $store;
    }

    private function cachePrefix(): string
    {
        $prefix = config('search.cache.prefix', 'search:products');

        if (! is_string($prefix)) {
            return 'search:products';
        }

        return $prefix;
    }
}
