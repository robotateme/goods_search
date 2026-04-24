<?php

declare(strict_types=1);

namespace Application\Commands;

use Application\Contracts\Queue\QueuedCommand;

final readonly class ImportProductsToSearchCommand implements QueuedCommand {}
