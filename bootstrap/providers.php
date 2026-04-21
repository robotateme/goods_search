<?php
declare(strict_types=1);


use App\Providers\AppServiceProvider;
use App\Providers\PortServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    PortServiceProvider::class,
];
