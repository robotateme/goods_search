<?php
declare(strict_types=1);

return [
    'redis_connection' => env('RATE_LIMIT_REDIS_CONNECTION', 'default'),
    'products' => [
        'enabled' => env('PRODUCTS_RATE_LIMIT_ENABLED', true),
    ],
];
