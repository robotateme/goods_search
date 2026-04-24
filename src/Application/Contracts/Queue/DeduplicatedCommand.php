<?php

declare(strict_types=1);

namespace Application\Contracts\Queue;

interface DeduplicatedCommand
{
    public function deduplicationKey(): string;
}
