<?php
declare(strict_types=1);

namespace Infrastructure\Support;

use InvalidArgumentException;

final readonly class ScriptResolver
{
    public function __construct(
        private string $basePath = __DIR__.'/../Scripts',
    ) {
    }

    public function resolve(string $path): string
    {
        $fullPath = $this->basePath.'/'.ltrim($path, '/');

        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            throw new InvalidArgumentException(sprintf('Unknown infrastructure script: %s', $path));
        }

        $script = file_get_contents($fullPath);

        if ($script === false) {
            throw new InvalidArgumentException(sprintf('Unable to read infrastructure script: %s', $path));
        }

        return $script;
    }
}
