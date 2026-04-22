<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Responses\ProductPageResponseData;
use Application\Handlers\SearchProductsHandler;
use Application\Queries\SearchProductsQueryFactory;
use Illuminate\Http\JsonResponse;

final class ProductIndexController extends Controller
{
    public function __construct(
        private readonly SearchProductsHandler $handler,
        private readonly SearchProductsQueryFactory $queryFactory,
    ) {
    }

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
