<?php

declare(strict_types=1);

namespace Tests\Feature;

use Infrastructure\Database\Eloquent\Category;
use Infrastructure\Database\Eloquent\Product;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
        config()->set('rate_limit.products.enabled', false);
    }

    // Проверяет, что поиск возвращает пагинированный список товаров.
    public function test_it_returns_paginated_products(): void
    {
        Product::factory()->count(18)->create();

        $response = $this->getJson('/api/products?per_page=5&page=2');

        $response
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', 5)
            ->assertJsonCount(5, 'data');
    }

    public function test_repeated_requests_work_with_search_cache_enabled(): void
    {
        config()->set('search.cache.enabled', true);
        config()->set('search.cache.store', 'array');

        Product::factory()->count(8)->create();

        $first = $this->getJson('/api/products?per_page=5&page=1');
        $second = $this->getJson('/api/products?per_page=5&page=1');

        $first->assertOk()->assertJsonCount(5, 'data');
        $second->assertOk()->assertJsonCount(5, 'data');
        self::assertSame($first->json('data.0.id'), $second->json('data.0.id'));
    }

    // Проверяет совместную работу всех поддержанных фильтров поиска.
    public function test_it_filters_by_all_supported_filters(): void
    {
        $targetCategory = Category::factory()->createOne();
        $otherCategory = Category::factory()->createOne();

        $matchingProduct = Product::factory()->createOne([
            'name' => 'Wireless Mouse Pro',
            'price' => 149.99,
            'category_id' => $targetCategory->id,
            'in_stock' => true,
            'rating' => 4.8,
        ]);

        Product::factory()->createOne([
            'name' => 'Wireless Keyboard',
            'price' => 149.99,
            'category_id' => $targetCategory->id,
            'in_stock' => false,
            'rating' => 4.8,
        ]);

        Product::factory()->createOne([
            'name' => 'Wireless Mouse Basic',
            'price' => 90.00,
            'category_id' => $targetCategory->id,
            'in_stock' => true,
            'rating' => 4.8,
        ]);

        Product::factory()->createOne([
            'name' => 'Wireless Mouse Premium',
            'price' => 149.99,
            'category_id' => $otherCategory->id,
            'in_stock' => true,
            'rating' => 4.8,
        ]);

        Product::factory()->createOne([
            'name' => 'Wireless Mouse Mini',
            'price' => 149.99,
            'category_id' => $targetCategory->id,
            'in_stock' => true,
            'rating' => 4.1,
        ]);

        $response = $this->getJson(sprintf(
            '/api/products?q=%s&price_from=100&price_to=200&category_id=%d&in_stock=true&rating_from=4.5',
            urlencode('Mouse'),
            $targetCategory->id,
        ));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->id)
            ->assertJsonPath('data.0.name', $matchingProduct->name);
    }

    // Проверяет поддержанные варианты сортировки и поведение по умолчанию.
    #[DataProvider('sortProvider')]
    public function test_it_sorts_by_supported_sort_values(string $uri, string $expectedProductKey): void
    {
        $category = Category::factory()->createOne();

        $oldest = Product::factory()->createOne([
            'name' => 'Old Product',
            'category_id' => $category->id,
            'price' => 200.00,
            'rating' => 4.2,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $cheapest = Product::factory()->createOne([
            'name' => 'Cheap Product',
            'category_id' => $category->id,
            'price' => 50.00,
            'rating' => 3.5,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $highestRated = Product::factory()->createOne([
            'name' => 'Top Rated Product',
            'category_id' => $category->id,
            'price' => 120.00,
            'rating' => 4.9,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $newest = Product::factory()->createOne([
            'name' => 'Newest Product',
            'category_id' => $category->id,
            'price' => 180.00,
            'rating' => 4.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expectedProducts = [
            'price_asc' => $cheapest->id,
            'price_desc' => $oldest->id,
            'rating_desc' => $highestRated->id,
            'newest' => $newest->id,
        ];

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonPath('data.0.id', $expectedProducts[$expectedProductKey]);
    }

    // Проверяет, что невалидные query-параметры возвращают ошибку валидации.
    public function test_it_validates_invalid_query_params(): void
    {
        $response = $this->getJson('/api/products?sort=invalid&rating_from=6&per_page=0');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'rating_from', 'per_page']);
    }

    /**
     * Набор сценариев сортировки для одного и того же search endpoint.
     *
     * @return array<string, array{string, string}>
     */
    public static function sortProvider(): array
    {
        return [
            'price ascending' => ['/api/products?sort=price_asc', 'price_asc'],
            'price descending' => ['/api/products?sort=price_desc', 'price_desc'],
            'rating descending' => ['/api/products?sort=rating_desc', 'rating_desc'],
            'default newest' => ['/api/products', 'newest'],
        ];
    }
}
