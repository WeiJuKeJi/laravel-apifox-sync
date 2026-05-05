<?php

namespace Weijukeji\LaravelApifoxSync\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Weijukeji\LaravelApifoxSync\ApifoxDocumentFinder;
use Weijukeji\LaravelApifoxSync\ApifoxImporter;

class ApifoxSyncCommand extends Command
{
    protected $signature = 'apifox:sync
        {paths?* : 指定一个或多个 docs/api/*.json 文件}
        {--module=* : 按模块名同步对应 docs/api 下的全部 JSON}
        {--all : 同步所有已配置的 Apifox JSON 文档}
        {--dry-run : 仅打印待同步文件，不发起请求}';

    protected $description = '将 OpenAPI JSON 文档同步到 Apifox';

    public function handle(ApifoxImporter $importer, ApifoxDocumentFinder $finder): int
    {
        $argumentPaths = array_values(array_filter((array) $this->argument('paths')));
        $modules = array_values(array_filter((array) $this->option('module')));

        $paths = [];

        foreach ($argumentPaths as $path) {
            if (! $finder->exists($path)) {
                $this->error("文件不存在: {$path}");

                return self::FAILURE;
            }

            $resolvedPaths = $finder->fromExplicitPaths([$path]);

            if ($resolvedPaths === []) {
                $this->error("仅支持同步 docs/api/*.json 文件: {$path}");

                return self::FAILURE;
            }

            $paths = array_merge($paths, $resolvedPaths);
        }

        if ($modules !== []) {
            $paths = array_merge($paths, $finder->fromModules($modules));
        }

        if ($this->option('all')) {
            $paths = array_merge($paths, $finder->all());
        }

        $paths = $this->uniqueSorted($paths);

        if ($paths === []) {
            $this->error('未找到待同步的 Apifox JSON 文档，请传入文件路径，或使用 --module / --all。');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('待同步文件:');

            foreach ($paths as $path) {
                $this->line('- '.$importer->relativePath($path));
            }

            $this->info('DRY RUN 完成，共 '.count($paths).' 个文件。');

            return self::SUCCESS;
        }

        try {
            $importer->ensureConfigured();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $successCount = 0;

        foreach ($paths as $path) {
            try {
                $result = $importer->import($path);
                $successCount++;

                $this->info(sprintf(
                    '已同步 [%d/%d] %s (HTTP %d)',
                    $successCount,
                    count($paths),
                    $importer->relativePath($result['path']),
                    $result['status']
                ));
            } catch (RuntimeException $exception) {
                $this->error($exception->getMessage());
            }
        }

        if ($successCount !== count($paths)) {
            $this->warn("Apifox 同步完成，但存在失败项：{$successCount}/".count($paths));

            return self::FAILURE;
        }

        $this->info("Apifox 同步完成：{$successCount}/".count($paths));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function uniqueSorted(array $paths): array
    {
        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }
}
