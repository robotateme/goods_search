<?php
declare(strict_types=1);


return [
    'driver' => env('SEARCH_DRIVER', 'meilisearch'),

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
