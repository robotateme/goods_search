<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Goods Search API',
    description: 'HTTP API for product search with filters, sorting, pagination, queue-based indexing, and optional Redis caching.',
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Local development server',
)]
#[OA\Tag(
    name: 'Products',
    description: 'Product search endpoints',
)]
final class OpenApiSpec {}
