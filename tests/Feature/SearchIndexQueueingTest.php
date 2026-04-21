<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ImportProductsToSearchJob;
use App\Jobs\IndexProductInSearchJob;
use App\Jobs\RemoveProductFromSearchJob;
use App\Jobs\SyncProductSearchSettingsJob;
use App\Models\Product;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SearchIndexQueueingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    public function test_product_changes_queue_search_index_jobs(): void
    {
        Bus::fake();

        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        Bus::assertDispatched(IndexProductInSearchJob::class, fn (IndexProductInSearchJob $job) => $job->productId === $productId);
        Bus::assertDispatched(RemoveProductFromSearchJob::class, fn (RemoveProductFromSearchJob $job) => $job->productId === $productId);
    }

    public function test_console_commands_queue_search_index_jobs(): void
    {
        Bus::fake();

        $this->artisan('search:products:sync')->assertSuccessful();
        $this->artisan('search:products:import')->assertSuccessful();

        Bus::assertDispatched(SyncProductSearchSettingsJob::class);
        Bus::assertDispatched(ImportProductsToSearchJob::class);
    }
}
