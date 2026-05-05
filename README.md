# Laravel Apifox Sync

Laravel package for syncing local OpenAPI JSON documents to Apifox by using the Apifox OpenAPI import endpoint.

## Installation

```bash
composer require weijukeji/laravel-apifox-sync
```

Publish the configuration when you need to customize paths or import options:

```bash
php artisan vendor:publish --tag=apifox-sync-config
```

## Configuration

Set the required environment variables:

```dotenv
APIFOX_PROJECT_ID=your_project_id
APIFOX_ACCESS_TOKEN=your_access_token
```

Optional environment variables:

```dotenv
APIFOX_BASE_URL=https://api.apifox.com
APIFOX_API_VERSION=2024-03-28
APIFOX_LOCALE=zh-CN
APIFOX_TIMEOUT=30
```

Default document discovery patterns:

```php
'paths' => [
    'Modules/*/docs/api/*.json',
    'vendor/weijukeji/*/docs/api/*.json',
],

'module_path_pattern' => 'Modules/{module}/docs/api/*.json',
```

## Usage

Sync one or more files:

```bash
php artisan apifox:sync Modules/Order/docs/api/orders.json
```

Sync a module:

```bash
php artisan apifox:sync --module=Order
```

Sync every configured document:

```bash
php artisan apifox:sync --all
```

Preview matched files without sending requests:

```bash
php artisan apifox:sync --module=Order --dry-run
```

## Notes

The package intentionally only provides Apifox sync behavior. Project-specific API document quality checks should live in the consuming application or in a separate rule-based package.
