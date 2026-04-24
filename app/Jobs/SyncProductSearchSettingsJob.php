<?php

declare(strict_types=1);

namespace App\Jobs;

use Application\Commands\SyncProductSearchSettingsCommand;
use Application\Handlers\SyncProductSearchSettingsHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncProductSearchSettingsJob implements ShouldQueue
{
    use Queueable;

    public function handle(SyncProductSearchSettingsHandler $handler): void
    {
        $handler->handle(new SyncProductSearchSettingsCommand());
    }
}
