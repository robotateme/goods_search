<?php

declare(strict_types=1);

namespace App\Jobs;

use Application\Commands\ImportProductsToSearchCommand;
use Application\Commands\IndexProductInSearchCommand;
use Application\Commands\RemoveProductInSearchCommand;
use Application\Commands\SyncProductSearchSettingsCommand;
use Infrastructure\Ports\Queue\QueueCommandJobMapper;
use InvalidArgumentException;
use Override;

final readonly class LaravelQueueCommandMapper implements QueueCommandJobMapper
{
    #[Override]
    public function map(object $command): object
    {
        return match (true) {
            $command instanceof IndexProductInSearchCommand => new IndexProductInSearchJob($command->productId),
            $command instanceof RemoveProductInSearchCommand => new RemoveProductFromSearchJob($command->productId),
            $command instanceof ImportProductsToSearchCommand => new ImportProductsToSearchJob(),
            $command instanceof SyncProductSearchSettingsCommand => new SyncProductSearchSettingsJob(),
            default => throw new InvalidArgumentException(sprintf('Unsupported queued command: %s', $command::class)),
        };
    }
}
