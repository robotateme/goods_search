<?php

declare(strict_types=1);

namespace Tests\Feature;

use Override;
use Tests\TestCase;

class OpenApiSpecControllerTest extends TestCase
{
    private string $docsDirectory;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->docsDirectory = storage_path('api-docs');

        if (!is_dir($this->docsDirectory)) {
            mkdir($this->docsDirectory, 0777, true);
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        @unlink($this->docsDirectory . '/openapi.yaml');
        @unlink($this->docsDirectory . '/openapi.json');

        parent::tearDown();
    }

    public function test_it_serves_generated_openapi_yaml(): void
    {
        file_put_contents($this->docsDirectory . '/openapi.yaml', "openapi: 3.1.0\ninfo:\n  title: Goods Search API\n");

        $this->get('/openapi.yaml')
            ->assertOk()
            ->assertHeader('content-type', 'application/yaml; charset=UTF-8')
            ->assertSee('openapi: 3.1.0', false);
    }

    public function test_it_serves_generated_openapi_json(): void
    {
        file_put_contents($this->docsDirectory . '/openapi.json', '{"openapi":"3.1.0","info":{"title":"Goods Search API"}}');

        $this->get('/openapi.json')
            ->assertOk()
            ->assertHeader('content-type', 'application/json; charset=UTF-8')
            ->assertSee('"openapi":"3.1.0"', false);
    }

    public function test_it_returns_not_found_when_spec_is_missing(): void
    {
        $this->get('/openapi.yaml')
            ->assertNotFound()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('Run "composer docs:openapi" first.', false);
    }
}
