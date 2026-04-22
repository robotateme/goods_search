<?php
declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationError',
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
)]
final class ValidationErrorResponse
{
}
