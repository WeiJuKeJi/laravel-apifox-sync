<?php

namespace Weijukeji\LaravelApifoxSync\Tests\Feature;

use Illuminate\Support\Facades\File;
use Weijukeji\LaravelApifoxSync\Tests\TestCase;

class ApifoxSyncCommandTest extends TestCase
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

    public function test_dry_run_lists_module_documents_without_importing(): void
    {
        $this->writeJson('Modules/Order/docs/api/orders.json');

        $this->artisan('apifox:sync', [
            '--module' => ['Order'],
            '--dry-run' => true,
        ])
            ->expectsOutput('待同步文件:')
            ->expectsOutput('- Modules/Order/docs/api/orders.json')
            ->expectsOutput('DRY RUN 完成，共 1 个文件。')
            ->assertSuccessful();
    }

    public function test_it_rejects_missing_explicit_file(): void
    {
        $this->artisan('apifox:sync', [
            'paths' => ['Modules/Order/docs/api/missing.json'],
        ])
            ->expectsOutput('文件不存在: Modules/Order/docs/api/missing.json')
            ->assertFailed();
    }

    private function writeJson(string $path): void
    {
        $absolutePath = $this->workspace.'/'.$path;

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, '{"openapi":"3.0.3"}');
    }
}
