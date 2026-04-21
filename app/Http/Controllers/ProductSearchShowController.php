<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductSearchRequest;
use Illuminate\Http\JsonResponse;

class ProductSearchShowController extends Controller
{
    public function __invoke(ProductSearchRequest $productSearchRequest): JsonResponse
    {
        $response = [
            'id' => $productSearchRequest->uuid,
            'status' => $productSearchRequest->status,
            'error' => $productSearchRequest->error,
            'created_at' => $productSearchRequest->created_at?->format(DATE_ATOM),
            'completed_at' => $productSearchRequest->completed_at?->format(DATE_ATOM),
        ];

        if ($productSearchRequest->result !== null) {
            $response['result'] = $productSearchRequest->result;
        }

        return response()->json($response);
    }
}
