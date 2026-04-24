<?php

declare(strict_types=1);

namespace Tests\Feature;

use Application\Contracts\Queue\QueueBus;
use Infrastructure\Ports\Queue\DeduplicatingQueueBus;
use Infrastructure\Ports\Queue\LaravelQueueBus;
use Tests\TestCase;

class QueueBusBindingTest extends TestCase
{
    // Проверяет, что application-порт очереди резолвится в инфраструктурный адаптер.
    public function test_queue_bus_contract_is_bound_to_infrastructure_adapter(): void
    {
        $queueBus = $this->app->make(QueueBus::class);

        self::assertInstanceOf(DeduplicatingQueueBus::class, $queueBus);
        self::assertInstanceOf(LaravelQueueBus::class, $this->app->make(LaravelQueueBus::class));
    }
}
