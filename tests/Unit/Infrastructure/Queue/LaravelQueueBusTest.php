<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queue;

use Illuminate\Contracts\Bus\Dispatcher;
use Infrastructure\Queue\LaravelQueueBus;
use PHPUnit\Framework\TestCase;

class LaravelQueueBusTest extends TestCase
{
    public function test_it_dispatches_commands(): void
    {
        $command = new class {};
        $dispatcher = new InMemoryDispatcher();
        $dispatcher->dispatchResult = 'queued';

        $queueBus = new LaravelQueueBus($dispatcher);

        self::assertSame('queued', $queueBus->dispatch($command));
        self::assertSame($command, $dispatcher->lastCommand);
    }

    public function test_it_dispatches_commands_synchronously(): void
    {
        $command = new class {};
        $dispatcher = new InMemoryDispatcher();
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

    public function dispatch($command)
    {
        $this->lastCommand = $command;

        return $this->dispatchResult;
    }

    public function dispatchSync($command, $handler = null)
    {
        $this->lastSyncCommand = $command;

        return $this->dispatchSyncResult;
    }

    public function dispatchAfterResponse($command, $handler = null): void
    {
    }

    public function dispatchNow($command, $handler = null)
    {
        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>|null  $jobs
     */
    public function chain($jobs = null)
    {
        return null;
    }

    public function hasCommandHandler($command): bool
    {
        return false;
    }

    public function getCommandHandler($command)
    {
        return null;
    }

    /**
     * @param  array<int, mixed>  $pipes
     */
    public function pipeThrough(array $pipes)
    {
        return $this;
    }

    /**
     * @param  array<int|string, class-string>  $map
     */
    public function map(array $map)
    {
        return $this;
    }
}
