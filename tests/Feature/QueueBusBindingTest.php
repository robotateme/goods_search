<?php
declare(strict_types=1);


namespace Tests\Feature;

use Application\Contracts\Queue\QueueBus;
use Infrastructure\Queue\LaravelQueueBus;
use Tests\TestCase;

class QueueBusBindingTest extends TestCase
{
    public function test_queue_bus_contract_is_bound_to_infrastructure_adapter(): void
    {
        self::assertInstanceOf(LaravelQueueBus::class, $this->app->make(QueueBus::class));
    }
}
