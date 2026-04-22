<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearch;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class CachedProductSearch implements ProductSearch
{
    public function __construct(
        private ProductSearch $inner,
        private CacheFactory $cacheFactory,
        private ProductSearchCacheVersionManager $versionManager,
    ) {
    }

    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        if (! (bool) config('search.cache.enabled', true)) {
            return $this->inner->search($criteria);
        }

        /** @var ProductPage $page */
        $page = $this->cache()->remember(
            $this->key($criteria),
            (int) config('search.cache.ttl_seconds', 300),
            fn (): ProductPage => $this->inner->search($criteria),
        );

        return $page;
    }

    private function cache(): CacheRepository
    {
        return $this->cacheFactory->store((string) config('search.cache.store'));
    }

    private function key(ProductSearchCriteria $criteria): string
    {
        $payload = [
            'version' => $this->versionManager->currentVersion(),
            'query' => $criteria->query,
            'price_from' => $criteria->priceFrom,
            'price_to' => $criteria->priceTo,
            'category_id' => $criteria->categoryId,
            'in_stock' => $criteria->inStock,
            'rating_from' => $criteria->ratingFrom,
            'sort' => $criteria->sort->value,
            'per_page' => $criteria->perPage,
            'page' => $criteria->page,
        ];

        return sprintf(
            '%s:%s',
            (string) config('search.cache.prefix', 'search:products'),
            sha1((string) json_encode($payload, JSON_THROW_ON_ERROR)),
        );
    }
}
