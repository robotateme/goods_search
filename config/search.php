<?php
declare(strict_types=1);


return [
    'driver' => env('SEARCH_DRIVER', 'meilisearch'),
    'cache' => [
        'enabled' => env('SEARCH_CACHE_ENABLED', true),
        'store' => env('SEARCH_CACHE_STORE', env('CACHE_STORE', 'redis')),
        'ttl_seconds' => (int) env('SEARCH_CACHE_TTL_SECONDS', 300),
        'prefix' => env('SEARCH_CACHE_PREFIX', 'search:products'),
        'version_key' => env('SEARCH_CACHE_VERSION_KEY', 'search:products:version'),
    ],

    'products' => [
        'index' => env('SEARCH_PRODUCTS_INDEX', 'products'),
        'filterable_attributes' => [
            'id',
            'category_id',
            'in_stock',
            'price',
            'rating',
            'created_at_timestamp',
        ],
        'sortable_attributes' => [
            'price',
            'rating',
            'created_at_timestamp',
        ],
    ],
];
