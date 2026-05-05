<?php

namespace Weijukeji\LaravelApifoxSync;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;

class ApifoxDocumentFinder
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Filesystem $files,
    ) {}

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public function fromExplicitPaths(array $paths): array
    {
        $resolved = [];

        foreach ($paths as $path) {
            $absolutePath = $this->normalizePath($path);

            if (! $this->isSupportedDocument($absolutePath)) {
                continue;
            }

            $resolved[] = $absolutePath;
        }

        return $this->uniqueSorted($resolved);
    }

    /**
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    public function fromModules(array $modules): array
    {
        $paths = [];
        $pattern = (string) $this->config->get('apifox.module_path_pattern', 'Modules/{module}/docs/api/*.json');

        foreach ($modules as $module) {
            $paths = array_merge(
                $paths,
                $this->globRelativePattern(str_replace('{module}', $module, $pattern))
            );
        }

        return $this->uniqueSorted($paths);
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        $paths = [];

        foreach ((array) $this->config->get('apifox.paths', []) as $pattern) {
            $paths = array_merge($paths, $this->globRelativePattern((string) $pattern));
        }

        return $this->uniqueSorted($paths);
    }

    public function exists(string $path): bool
    {
        return $this->files->exists($this->normalizePath($path));
    }

    public function isSupportedDocument(string $path): bool
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return str_ends_with($normalized, '.json')
            && str_contains($normalized, DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR);
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @return array<int, string>
     */
    private function globRelativePattern(string $pattern): array
    {
        $absolutePattern = str_starts_with($pattern, DIRECTORY_SEPARATOR)
            ? $pattern
            : base_path($pattern);

        return array_values(array_filter(
            glob($absolutePattern) ?: [],
            fn (string $path): bool => $this->isSupportedDocument($path) && $this->files->exists($path)
        ));
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
