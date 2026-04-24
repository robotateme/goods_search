<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queue;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Collection;
use Infrastructure\Ports\Queue\LaravelQueueBus;
use Override;
use PHPUnit\Framework\TestCase;

class LaravelQueueBusTest extends TestCase
{
    // Проверяет, что QueueBus делегирует асинхронную отправку диспетчеру Laravel.
    public function test_it_dispatches_commands(): void
    {
        $command = new class {};
        $dispatcher = new InMemoryDispatcher;
        $dispatcher->dispatchResult = 'queued';

        $queueBus = new LaravelQueueBus($dispatcher);

        self::assertSame('queued', $queueBus->dispatch($command));
        self::assertSame($command, $dispatcher->lastCommand);
    }

    // Проверяет, что QueueBus делегирует синхронную отправку диспетчеру Laravel.
    public function test_it_dispatches_commands_synchronously(): void
    {
        $command = new class {};
        $dispatcher = new InMemoryDispatcher;
        $dispatcher->dispatchSyncResult = 'handled';

        $queueBus = new LaravelQueueBus($dispatcher);

        self::assertSame('handled', $queueBus->dispatchSync($command));
        self::assertSame($command, $dispatcher->lastSyncCommand);
    }
}

final class InMemoryDispatcher implements Dispatcher
{
    public object $lastCommand;

    public object $lastSyncCommand;

    public mixed $dispatchResult = null;

    public mixed $dispatchSyncResult = null;

    #[Override]
    public function dispatch($command)
    {
        $this->lastCommand = $command;

        return $this->dispatchResult;
    }

    #[Override]
    public function dispatchSync($command, $handler = null)
    {
        $this->lastSyncCommand = $command;

        return $this->dispatchSyncResult;
    }

    #[Override]
    public function dispatchAfterResponse($command, $handler = null): void {}

    #[Override]
    public function dispatchNow($command, $handler = null)
    {
        return null;
    }

    /**
     * @param  Collection<array-key, mixed>|array<array-key, mixed>|null  $jobs
     */
    #[Override]
    public function chain($jobs = null)
    {
        return null;
    }

    #[Override]
    public function hasCommandHandler($command): bool
    {
        return false;
    }

    #[Override]
    public function getCommandHandler($command)
    {
        return null;
    }

    /**
     * @param  array<array-key, mixed>  $pipes
     */
    #[Override]
    public function pipeThrough(array $pipes)
    {
        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $map
     */
    #[Override]
    public function map(array $map)
    {
        return $this;
    }
}
