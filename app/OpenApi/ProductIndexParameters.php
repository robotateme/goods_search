<?php
declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Parameter(
    parameter: 'ProductsQuery',
    name: 'q',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', maxLength: 255),
)]
#[OA\Parameter(
    parameter: 'ProductsPriceFrom',
    name: 'price_from',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'number', format: 'float', minimum: 0),
)]
#[OA\Parameter(
    parameter: 'ProductsPriceTo',
    name: 'price_to',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'number', format: 'float', minimum: 0),
)]
#[OA\Parameter(
    parameter: 'ProductsCategoryId',
    name: 'category_id',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1),
)]
#[OA\Parameter(
    parameter: 'ProductsInStock',
    name: 'in_stock',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'boolean'),
)]
#[OA\Parameter(
    parameter: 'ProductsRatingFrom',
    name: 'rating_from',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'number', format: 'float', minimum: 0, maximum: 5),
)]
#[OA\Parameter(
    parameter: 'ProductsSort',
    name: 'sort',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', enum: ['price_asc', 'price_desc', 'rating_desc', 'newest']),
)]
#[OA\Parameter(
    parameter: 'ProductsPage',
    name: 'page',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
)]
#[OA\Parameter(
    parameter: 'ProductsPerPage',
    name: 'per_page',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15),
)]
final class ProductIndexParameters
{
}
