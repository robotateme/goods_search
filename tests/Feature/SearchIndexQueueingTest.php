<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ImportProductsToSearchJob;
use App\Jobs\IndexProductInSearchJob;
use App\Jobs\RemoveProductFromSearchJob;
use App\Jobs\SyncProductSearchSettingsJob;
use App\Infrastructure\Database\Eloquent\Product;
use Application\Commands\IndexProductInSearchCommand;
use Application\Contracts\Queue\QueueBus;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Infrastructure\Redis\Queue\RedisQueueDeduplicator;
use Override;
use Tests\TestCase;

class SearchIndexQueueingTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    // Проверяет, что изменения товара ставят задачи индексации и удаления из поиска в очередь.
    public function test_product_changes_queue_search_index_jobs(): void
    {
        Bus::fake();

        $product = Product::factory()->createOne();
        $productId = $product->id;

        $product->delete();

        Bus::assertDispatched(IndexProductInSearchJob::class, fn (IndexProductInSearchJob $job) => $job->productId === $productId);
        Bus::assertDispatched(RemoveProductFromSearchJob::class, fn (RemoveProductFromSearchJob $job) => $job->productId === $productId);
    }

    // Проверяет, что консольные команды поиска ставят bulk-задачи в очередь.
    public function test_console_commands_queue_search_index_jobs(): void
    {
        Bus::fake();

        self::assertSame(0, Artisan::call('search:products:sync'));
        self::assertSame(0, Artisan::call('search:products:import'));

        Bus::assertDispatched(SyncProductSearchSettingsJob::class);
        Bus::assertDispatched(ImportProductsToSearchJob::class);
    }

    // Проверяет, что повторная постановка index job для одного товара дедуплицируется.
    public function test_index_job_dispatch_is_deduplicated_for_same_product(): void
    {
        $product = Product::factory()->createOne();

        Bus::fake();
        $this->app->instance(RedisFactory::class, new class implements RedisFactory
        {
            private readonly Connection $connection;

            public function __construct()
            {
                $this->connection = new class extends Connection
                {
                    /** @var array<string, true> */
                    private array $claimedKeys = [];

                    /**
                     * @param  array<array-key, mixed>|string  $channels
                     */
                    #[Override]
                    public function createSubscription($channels, \Closure $callback, $method = 'subscribe'): void {}

                    public function eval(string $script, int $numberOfKeys, string ...$arguments): int
                    {
                        $key = $arguments[0] ?? '';

                        if (isset($this->claimedKeys[$key])) {
                            return 0;
                        }

                        $this->claimedKeys[$key] = true;

                        return 1;
                    }
                };
            }

            #[Override]
            public function connection($name = null): Connection
            {
                return $this->connection;
            }
        });
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.dedup.ttl_seconds', 30);
        $this->app->forgetInstance(RedisQueueDeduplicator::class);
        $this->app->forgetInstance(QueueBus::class);
        $queueBus = $this->app->make(QueueBus::class);

        $queueBus->dispatch(new IndexProductInSearchCommand($product->id));
        $queueBus->dispatch(new IndexProductInSearchCommand($product->id));

        Bus::assertDispatchedTimes(IndexProductInSearchJob::class, 1);
    }
}
