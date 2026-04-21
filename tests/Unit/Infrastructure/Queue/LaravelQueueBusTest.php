<?php

namespace Tests\Unit\Infrastructure\Queue;

use Illuminate\Contracts\Bus\Dispatcher;
use Infrastructure\Queue\LaravelQueueBus;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LaravelQueueBusTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_dispatches_commands(): void
    {
        $command = new class {};

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($command)
            ->andReturn('queued');

        $queueBus = new LaravelQueueBus($dispatcher);

        self::assertSame('queued', $queueBus->dispatch($command));
    }

    public function test_it_dispatches_commands_synchronously(): void
    {
        $command = new class {};

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher
            ->shouldReceive('dispatchSync')
            ->once()
            ->with($command)
            ->andReturn('handled');

        $queueBus = new LaravelQueueBus($dispatcher);

        self::assertSame('handled', $queueBus->dispatchSync($command));
    }
}
