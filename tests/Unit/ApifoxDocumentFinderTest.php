<?php

namespace Weijukeji\LaravelApifoxSync\Tests\Unit;

use Illuminate\Support\Facades\File;
use Weijukeji\LaravelApifoxSync\ApifoxDocumentFinder;
use Weijukeji\LaravelApifoxSync\Tests\TestCase;

class ApifoxDocumentFinderTest extends TestCase
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

    public function test_it_finds_configured_documents(): void
    {
        $this->writeJson('Modules/Order/docs/api/orders.json');
        $this->writeJson('Modules/Ticket/docs/api/tickets.json');
        $this->writeJson('Modules/Order/docs/ignored.json');

        config()->set('apifox.paths', [
            'Modules/*/docs/api/*.json',
        ]);

        $paths = app(ApifoxDocumentFinder::class)->all();

        $this->assertSame([
            $this->workspace.'/Modules/Order/docs/api/orders.json',
            $this->workspace.'/Modules/Ticket/docs/api/tickets.json',
        ], $paths);
    }

    public function test_it_finds_module_documents(): void
    {
        $this->writeJson('Modules/Order/docs/api/orders.json');
        $this->writeJson('Modules/Ticket/docs/api/tickets.json');

        $paths = app(ApifoxDocumentFinder::class)->fromModules(['Order']);

        $this->assertSame([
            $this->workspace.'/Modules/Order/docs/api/orders.json',
        ], $paths);
    }

    public function test_it_rejects_non_api_json_paths(): void
    {
        $this->writeJson('Modules/Order/docs/api/orders.json');
        $this->writeJson('Modules/Order/docs/orders.json');

        $paths = app(ApifoxDocumentFinder::class)->fromExplicitPaths([
            'Modules/Order/docs/api/orders.json',
            'Modules/Order/docs/orders.json',
        ]);

        $this->assertSame([
            $this->workspace.'/Modules/Order/docs/api/orders.json',
        ], $paths);
    }

    private function writeJson(string $path): void
    {
        $absolutePath = $this->workspace.'/'.$path;

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, '{"openapi":"3.0.3"}');
    }
}
