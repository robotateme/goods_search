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
            new OA\Parameter(ref: '#/components/parameters/ProductsQuery'),
            new OA\Parameter(ref: '#/components/parameters/ProductsPriceFrom'),
            new OA\Parameter(ref: '#/components/parameters/ProductsPriceTo'),
            new OA\Parameter(ref: '#/components/parameters/ProductsCategoryId'),
            new OA\Parameter(ref: '#/components/parameters/ProductsInStock'),
            new OA\Parameter(ref: '#/components/parameters/ProductsRatingFrom'),
            new OA\Parameter(ref: '#/components/parameters/ProductsSort'),
            new OA\Parameter(ref: '#/components/parameters/ProductsPage'),
            new OA\Parameter(ref: '#/components/parameters/ProductsPerPage'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated product list',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductPageResponse'),
            ),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
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
