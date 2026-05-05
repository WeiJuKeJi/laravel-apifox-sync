<?php

namespace Weijukeji\LaravelApifoxSync;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory as HttpFactory;
use JsonException;
use RuntimeException;

class ApifoxImporter
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Filesystem $files,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array{path:string,status:int,body:array|string|null}
     */
    public function import(string $path): array
    {
        $absolutePath = $this->normalizePath($path);

        if (! $this->files->exists($absolutePath)) {
            throw new RuntimeException("Apifox 文档不存在: {$path}");
        }

        $document = $this->encodeInput($this->files->get($absolutePath), $absolutePath);
        $response = $this->http
            ->timeout((int) $this->config->get('apifox.timeout', 30))
            ->withToken($this->accessToken())
            ->withHeaders([
                'X-Apifox-Api-Version' => (string) $this->config->get('apifox.api_version', '2024-03-28'),
            ])
            ->acceptJson()
            ->post($this->endpoint(), [
                'input' => $document,
                'options' => $this->options(),
            ]);

        if ($response->failed()) {
            $message = $response->json('message')
                ?? $response->json('error.message')
                ?? $response->body();

            throw new RuntimeException(sprintf(
                'Apifox 导入失败 [%s] HTTP %d: %s',
                $this->relativePath($absolutePath),
                $response->status(),
                $message
            ));
        }

        return [
            'path' => $absolutePath,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }

    public function relativePath(string $path): string
    {
        $absolutePath = $this->normalizePath($path);
        $basePath = base_path().DIRECTORY_SEPARATOR;

        if (str_starts_with($absolutePath, $basePath)) {
            return substr($absolutePath, strlen($basePath));
        }

        return $absolutePath;
    }

    public function ensureConfigured(): void
    {
        $missing = [];

        if ((string) $this->config->get('apifox.project_id', '') === '') {
            $missing[] = 'APIFOX_PROJECT_ID';
        }

        if ((string) $this->config->get('apifox.access_token', '') === '') {
            $missing[] = 'APIFOX_ACCESS_TOKEN';
        }

        if ($missing !== []) {
            throw new RuntimeException('Apifox 配置缺失，请先设置: '.implode(', ', $missing));
        }
    }

    private function accessToken(): string
    {
        $this->ensureConfigured();

        return (string) $this->config->get('apifox.access_token');
    }

    private function endpoint(): string
    {
        $baseUrl = rtrim((string) $this->config->get('apifox.base_url', 'https://api.apifox.com'), '/');
        $projectId = (string) $this->config->get('apifox.project_id');
        $locale = urlencode((string) $this->config->get('apifox.locale', 'zh-CN'));

        return "{$baseUrl}/v1/projects/{$projectId}/import-openapi?locale={$locale}";
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function options(): array
    {
        return (array) $this->config->get('apifox.import_options', []);
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function encodeInput(string $content, string $path): string
    {
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Apifox 文档 JSON 无法解析 [{$this->relativePath($path)}]: {$exception->getMessage()}", previous: $exception);
        }

        $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new RuntimeException("Apifox 文档 JSON 无法重新编码 [{$this->relativePath($path)}]");
        }

        return $encoded;
    }
}
