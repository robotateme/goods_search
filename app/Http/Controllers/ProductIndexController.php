<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Responses\ProductPageResponseData;
use Application\Handlers\SearchProductsHandler;
use Application\Queries\SearchProductsQueryFactory;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ProductIndexController extends Controller
{
    public function __construct(
        private readonly SearchProductsHandler $handler,
        private readonly SearchProductsQueryFactory $queryFactory,
    ) {
    }

    #[OA\Get(
        path: '/api/products',
        operationId: 'products.index',
        summary: 'Search products',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'price_from', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float', minimum: 0)),
            new OA\Parameter(name: 'price_to', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float', minimum: 0)),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'in_stock', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'rating_from', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float', minimum: 0, maximum: 5)),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['price_asc', 'price_desc', 'rating_desc', 'newest']),
            ),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated product list',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductPageResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    required: ['message', 'errors'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: true,
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function __invoke(ProductIndexRequest $request): JsonResponse
    {
        $result = $this->handler->handle(
            $this->queryFactory->fromFilters($request->filters()),
        );

        return response()->json(
            ProductPageResponseData::fromPage($result, $request->url())->toArray(),
        );
    }
}
