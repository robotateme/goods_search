<?php
declare(strict_types=1);

return [
    'products' => [
        'enabled' => env('PRODUCTS_RATE_LIMIT_ENABLED', true),
        'redis_connection' => env('PRODUCTS_RATE_LIMIT_REDIS_CONNECTION', 'default'),
        'prefix' => env('PRODUCTS_RATE_LIMIT_PREFIX', 'rate-limit:products'),
        'max_requests' => (int) env('PRODUCTS_RATE_LIMIT_MAX_REQUESTS', 60),
        'window_seconds' => (int) env('PRODUCTS_RATE_LIMIT_WINDOW_SECONDS', 60),
    ],
];
