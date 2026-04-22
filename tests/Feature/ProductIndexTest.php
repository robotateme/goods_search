<?php
declare(strict_types=1);


namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
        config()->set('rate_limit.products.enabled', false);
    }

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

    public function test_it_sorts_by_supported_sort_values(): void
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

        $this->getJson('/api/products?sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $cheapest->id);

        $this->getJson('/api/products?sort=price_desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $oldest->id);

        $this->getJson('/api/products?sort=rating_desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $highestRated->id);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newest->id);
    }

    public function test_it_validates_invalid_query_params(): void
    {
        $response = $this->getJson('/api/products?sort=invalid&rating_from=6&per_page=0');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'rating_from', 'per_page']);
    }
}
