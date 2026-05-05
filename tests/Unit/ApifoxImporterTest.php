<?php

namespace Weijukeji\LaravelApifoxSync\Tests\Unit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Weijukeji\LaravelApifoxSync\ApifoxImporter;
use Weijukeji\LaravelApifoxSync\Tests\TestCase;

class ApifoxImporterTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().'/laravel-apifox-sync-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->workspace);
        $this->app->setBasePath($this->workspace);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_it_imports_openapi_json_to_apifox(): void
    {
        config()->set('apifox.project_id', 'project-1');
        config()->set('apifox.access_token', 'token-1');
        config()->set('apifox.base_url', 'https://api.example.test');
        config()->set('apifox.locale', 'zh-CN');

        $this->writeJson('Modules/Order/docs/api/orders.json', [
            'openapi' => '3.0.3',
            'info' => ['title' => 'Orders'],
            'paths' => ['/v1/orders' => []],
        ]);

        Http::fake([
            'api.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $result = app(ApifoxImporter::class)->import('Modules/Order/docs/api/orders.json');

        $this->assertSame(200, $result['status']);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $request->url() === 'https://api.example.test/v1/projects/project-1/import-openapi?locale=zh-CN'
                && $request->hasHeader('Authorization', 'Bearer token-1')
                && $request->hasHeader('X-Apifox-Api-Version', '2024-03-28')
                && $body['input'] === '{"openapi":"3.0.3","info":{"title":"Orders"},"paths":{"/v1/orders":[]}}'
                && $body['options']['endpointOverwriteBehavior'] === 'OVERWRITE_EXISTING';
        });
    }

    public function test_it_requires_project_id_and_access_token(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APIFOX_PROJECT_ID');

        app(ApifoxImporter::class)->ensureConfigured();
    }

    private function writeJson(string $path, array $document): void
    {
        $absolutePath = $this->workspace.'/'.$path;

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
