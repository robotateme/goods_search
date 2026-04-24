<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ModelMakeCommand as BaseModelMakeCommand;
use Override;

final class ModelMakeCommand extends BaseModelMakeCommand
{
    #[Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Infrastructure\\Database\\Eloquent';
    }
}
