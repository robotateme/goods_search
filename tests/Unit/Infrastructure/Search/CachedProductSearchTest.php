<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Search;

use Application\Contracts\Search\ProductSearch;
use Domain\Product\Entity\Product;
use Domain\Product\Search\ProductPage;
use Domain\Product\Search\ProductSearchCriteria;
use Domain\Product\Search\ProductSort;
use Domain\Product\ValueObject\CategoryId;
use Domain\Product\ValueObject\Page;
use Domain\Product\ValueObject\PerPage;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductId;
use Domain\Product\ValueObject\Rating;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Infrastructure\Search\CachedProductSearch;
use Infrastructure\Search\ProductSearchCacheVersionManager;
use Tests\TestCase;

class CachedProductSearchTest extends TestCase
{
    // Проверяет, что одинаковые поисковые критерии переиспользуют кэшированный результат.
    public function test_it_reuses_cached_search_results_for_identical_criteria(): void
    {
        $calls = 0;
        $inner = new class($calls) implements ProductSearch
        {
            public function __construct(
                private int &$calls,
            ) {
            }

            public function search(ProductSearchCriteria $criteria): ProductPage
            {
                $this->calls++;

                return new ProductPage(
                    items: [
                        new Product(
                            id: new ProductId(1),
                            name: 'Cached Mouse',
                            price: new Price('149.99'),
                            categoryId: new CategoryId(2),
                            inStock: true,
                            rating: new Rating(4.8),
                            createdAt: null,
                            updatedAt: null,
                        ),
                    ],
                    total: 1,
                    perPage: $criteria->perPage,
                    currentPage: $criteria->page,
                );
            }
        };

        $cachedSearch = $this->makeCachedSearch($inner);
        $criteria = ProductSearchCriteria::fromInput(
            query: 'mouse',
            priceFrom: null,
            priceTo: null,
            categoryId: null,
            inStock: null,
            ratingFrom: null,
            sort: ProductSort::Newest,
            perPage: 15,
            page: 1,
        );

        $first = $cachedSearch->search($criteria);
        $second = $cachedSearch->search($criteria);

        self::assertSame(1, $calls);
        self::assertSame($first->total, $second->total);
        self::assertSame($first->items[0]->name, $second->items[0]->name);
    }

    // Проверяет, что смена версии search cache инвалидирует ранее сохранённый результат.
    public function test_cache_version_bump_invalidates_previous_results(): void
    {
        $calls = 0;
        $inner = new class($calls) implements ProductSearch
        {
            public function __construct(
                private int &$calls,
            ) {
            }

            public function search(ProductSearchCriteria $criteria): ProductPage
            {
                $this->calls++;

                return new ProductPage(
                    items: [],
                    total: $this->calls,
                    perPage: $criteria->perPage,
                    currentPage: $criteria->page,
                );
            }
        };

        $versionManager = $this->app->make(ProductSearchCacheVersionManager::class);
        $cachedSearch = $this->makeCachedSearch($inner, $versionManager);
        $criteria = ProductSearchCriteria::fromInput(
            query: null,
            priceFrom: null,
            priceTo: null,
            categoryId: null,
            inStock: null,
            ratingFrom: null,
            sort: ProductSort::Newest,
            perPage: 15,
            page: 1,
        );

        $first = $cachedSearch->search($criteria);
        $versionManager->bump();
        $second = $cachedSearch->search($criteria);

        self::assertSame(2, $calls);
        self::assertSame(1, $first->total);
        self::assertSame(2, $second->total);
    }

    private function makeCachedSearch(ProductSearch $inner, ?ProductSearchCacheVersionManager $versionManager = null): CachedProductSearch
    {
        $this->app['config']->set('search.cache.enabled', true);
        $this->app['config']->set('search.cache.store', 'array');
        $this->app['config']->set('search.cache.ttl_seconds', 300);
        $this->app['config']->set('search.cache.prefix', 'test:search:products');
        $this->app['config']->set('search.cache.version_key', 'test:search:products:version');

        return new CachedProductSearch(
            $inner,
            $this->app->make(CacheManager::class),
            $versionManager ?? $this->app->make(ProductSearchCacheVersionManager::class),
        );
    }
}
