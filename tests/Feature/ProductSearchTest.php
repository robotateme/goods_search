<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RunProductSearchJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSearchRequest;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    public function test_it_queues_a_product_search_request(): void
    {
        Bus::fake();

        $response = $this->postJson('/api/product-searches', [
            'q' => 'Mouse',
            'price_from' => 100,
            'per_page' => 5,
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('status', ProductSearchRequest::STATUS_PENDING)
            ->assertJsonStructure(['id', 'status', 'status_url']);

        $searchRequest = ProductSearchRequest::query()->firstOrFail();

        self::assertSame('Mouse', $searchRequest->criteria['query']);
        self::assertEquals(100.0, $searchRequest->criteria['price_from']);
        self::assertSame(5, $searchRequest->criteria['per_page']);

        Bus::assertDispatched(RunProductSearchJob::class, fn (RunProductSearchJob $job) => $job->searchRequestId === $searchRequest->id);
    }

    public function test_it_returns_completed_search_results_by_request_id(): void
    {
        $targetCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $matchingProduct = Product::factory()->create([
            'name' => 'Wireless Mouse Pro',
            'price' => 149.99,
            'category_id' => $targetCategory->id,
            'in_stock' => true,
            'rating' => 4.8,
        ]);

        Product::factory()->create([
            'name' => 'Wireless Keyboard',
            'price' => 149.99,
            'category_id' => $targetCategory->id,
            'in_stock' => false,
            'rating' => 4.8,
        ]);

        Product::factory()->create([
            'name' => 'Wireless Mouse Premium',
            'price' => 149.99,
            'category_id' => $otherCategory->id,
            'in_stock' => true,
            'rating' => 4.8,
        ]);

        $createResponse = $this->postJson('/api/product-searches', [
            'q' => 'Mouse',
            'price_from' => 100,
            'price_to' => 200,
            'category_id' => $targetCategory->id,
            'in_stock' => 'true',
            'rating_from' => 4.5,
        ]);

        $searchId = (string) $createResponse->json('id');

        $this->getJson('/api/product-searches/'.$searchId)
            ->assertOk()
            ->assertJsonPath('status', ProductSearchRequest::STATUS_COMPLETED)
            ->assertJsonPath('result.total', 1)
            ->assertJsonPath('result.data.0.id', $matchingProduct->id)
            ->assertJsonPath('result.data.0.name', $matchingProduct->name);
    }

    public function test_it_validates_invalid_search_request_payload(): void
    {
        $response = $this->postJson('/api/product-searches', [
            'sort' => 'invalid',
            'rating_from' => 6,
            'per_page' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'rating_from', 'per_page']);
    }
}
