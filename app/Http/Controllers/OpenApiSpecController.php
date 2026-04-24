<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class OpenApiSpecController extends Controller
{
    public function __invoke(string $format): Response
    {
        $contentTypes = [
            'yaml' => 'application/yaml; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
        ];

        abort_unless(isset($contentTypes[$format]), Response::HTTP_NOT_FOUND);

        $path = storage_path(sprintf('api-docs/openapi.%s', $format));

        if (!is_file($path)) {
            return response(
                'OpenAPI spec was not generated yet. Run "composer docs:openapi" first.',
                Response::HTTP_NOT_FOUND,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return response(
                'Failed to read generated OpenAPI spec.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        return response($content, Response::HTTP_OK, [
            'Content-Type' => $contentTypes[$format],
            'Content-Disposition' => 'inline; filename="openapi.' . $format . '"',
        ]);
    }
}
