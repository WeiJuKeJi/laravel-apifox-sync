# Laravel Apifox Sync

`weijukeji/laravel-apifox-sync` 是一个 Laravel 扩展包，用于把项目仓库里的 OpenAPI JSON 文档同步到 Apifox。

它解决的问题很直接：接口文档已经在代码仓库中维护，例如 `Modules/*/docs/api/*.json`，但每次修改后还需要通知并更新 Apifox 远端项目。这个扩展包提供 `php artisan apifox:sync` 命令，自动查找本地 API JSON 文件，并调用 Apifox OpenAPI 导入接口完成同步。

## 这个扩展包是做什么的

这个包负责：

- 将本地 `docs/api/*.json` OpenAPI 文档导入 Apifox。
- 支持按指定文件、按模块、或按配置路径批量同步。
- 支持 `--dry-run` 预览将要同步的文件，避免误操作。
- 统一封装 Apifox 项目 ID、访问令牌、导入选项、超时时间等配置。
- 让 Laravel 项目、模块化项目、AI 编码流程都可以用同一个命令同步接口文档。

这个包不负责：

- 生成 OpenAPI JSON。
- 校验接口文档业务质量。
- 从 Controller、Request、Resource 自动推导接口文档。
- 替代项目内的 API 文档规范。

也就是说，项目仍然需要先按自己的规范维护好 `docs/api/*.json`，本扩展包只负责把这些 JSON 同步到 Apifox。

## 适用场景

### 模块化 Laravel 项目

项目使用 `nwidart/laravel-modules` 或类似模块化结构，每个模块独立维护 API 文档：

```text
Modules/Order/docs/api/orders.json
Modules/Ticket/docs/api/tickets.json
Modules/Member/docs/api/members.json
```

可以按模块同步：

```bash
php artisan apifox:sync --module=Order
```

### 公司内部扩展包或供应商包

默认也会扫描：

```text
vendor/weijukeji/*/docs/api/*.json
```

适合把内部 Composer 包自带的接口文档一起同步到同一个 Apifox 项目。

### AI 辅助开发

当 AI 修改 Laravel 后端接口时，通常会同时修改：

- Controller
- FormRequest
- Resource
- 路由
- 模块文档
- `Modules/*/docs/api/*.json`

如果只改本地 JSON，不同步 Apifox，团队看到的远端接口文档会继续保持旧版本。这个扩展包就是给 AI 和开发者提供一个固定的同步动作。

在使用 AI 编码助手时，可以在项目的 `AGENTS.md`、`CLAUDE.md` 或其他约束文件中写明：

```text
若修改任何 Modules/*/docs/api/*.json 或 vendor/weijukeji/*/docs/api/*.json，
必须执行 php artisan apifox:sync ... 同步到 Apifox。
如果未执行，最终输出必须说明原因。
```

这样 AI 的工作流会变成：

1. 阅读项目接口规范。
2. 修改接口实现。
3. 更新对应 OpenAPI JSON 文档。
4. 执行 `php artisan apifox:sync --module=模块名` 或同步指定 JSON 文件。
5. 在最终回复中说明 Apifox 是否已同步。

这可以减少“代码已改、Apifox 还是旧文档”的问题。

### CI 或发布流程

如果团队希望在合并、发布或部署前统一推送 API 文档，可以在 CI 中执行：

```bash
php artisan apifox:sync --all
```

建议在 CI 环境中通过密钥管理配置 `APIFOX_PROJECT_ID` 和 `APIFOX_ACCESS_TOKEN`。

## 安装

```bash
composer require weijukeji/laravel-apifox-sync
```

Laravel 会通过 package auto-discovery 自动注册服务提供者。

如需自定义扫描路径或导入选项，可以发布配置文件：

```bash
php artisan vendor:publish --tag=apifox-sync-config
```

发布后会生成：

```text
config/apifox.php
```

## 环境变量

必须配置：

```dotenv
APIFOX_PROJECT_ID=your_project_id
APIFOX_ACCESS_TOKEN=your_access_token
```

可选配置：

```dotenv
APIFOX_BASE_URL=https://api.apifox.com
APIFOX_API_VERSION=2024-03-28
APIFOX_LOCALE=zh-CN
APIFOX_TIMEOUT=30
```

导入行为相关配置：

```dotenv
APIFOX_TARGET_ENDPOINT_FOLDER_ID=0
APIFOX_TARGET_SCHEMA_FOLDER_ID=0
APIFOX_ENDPOINT_OVERWRITE_BEHAVIOR=OVERWRITE_EXISTING
APIFOX_SCHEMA_OVERWRITE_BEHAVIOR=OVERWRITE_EXISTING
APIFOX_UPDATE_FOLDER_OF_CHANGED_ENDPOINT=false
APIFOX_PREPEND_BASE_PATH=false
```

默认会覆盖已有接口和 Schema：

```dotenv
APIFOX_ENDPOINT_OVERWRITE_BEHAVIOR=OVERWRITE_EXISTING
APIFOX_SCHEMA_OVERWRITE_BEHAVIOR=OVERWRITE_EXISTING
```

如果你的 Apifox 项目需要不同的导入策略，请先确认 Apifox OpenAPI 导入接口支持的选项，再调整这些环境变量。

## 默认扫描规则

默认配置：

```php
'paths' => [
    'Modules/*/docs/api/*.json',
    'vendor/weijukeji/*/docs/api/*.json',
],

'module_path_pattern' => 'Modules/{module}/docs/api/*.json',
```

`apifox:sync` 只会同步路径中包含 `docs/api/` 且扩展名为 `.json` 的文件。类似 `Modules/Order/docs/orders.json` 这样的文件会被忽略。

## 使用方法

### 同步指定文件

```bash
php artisan apifox:sync Modules/Order/docs/api/orders.json
```

也可以一次传多个文件：

```bash
php artisan apifox:sync \
  Modules/Order/docs/api/orders.json \
  Modules/Ticket/docs/api/tickets.json
```

### 按模块同步

```bash
php artisan apifox:sync --module=Order
```

多个模块：

```bash
php artisan apifox:sync --module=Order --module=Ticket
```

命令会根据 `module_path_pattern` 查找：

```text
Modules/Order/docs/api/*.json
Modules/Ticket/docs/api/*.json
```

### 同步全部配置文档

```bash
php artisan apifox:sync --all
```

命令会扫描 `config/apifox.php` 中的 `paths`。

### 仅预览，不发送请求

```bash
php artisan apifox:sync --module=Order --dry-run
```

输出示例：

```text
待同步文件:
- Modules/Order/docs/api/orders.json
DRY RUN 完成，共 1 个文件。
```

建议 AI 或开发者在不确定匹配范围时先执行 `--dry-run`。

## 推荐工作流

### 开发者手动同步

1. 修改接口实现。
2. 修改对应 `docs/api/*.json`。
3. 执行 dry-run：

```bash
php artisan apifox:sync --module=Order --dry-run
```

4. 确认文件列表正确后同步：

```bash
php artisan apifox:sync --module=Order
```

### AI 修改接口时

如果 AI 修改了 `Modules/Order/docs/api/*.json`，推荐要求 AI 执行：

```bash
php artisan apifox:sync --module=Order
```

如果只修改了单个文档：

```bash
php artisan apifox:sync Modules/Order/docs/api/orders.json
```

AI 最终输出中应明确说明：

- 是否修改了 OpenAPI JSON。
- 是否执行了 Apifox 同步。
- 同步命令是什么。
- 如果未同步，原因是什么，例如缺少 `APIFOX_ACCESS_TOKEN` 或当前环境不能访问 Apifox。

## 常见错误

### Apifox 配置缺失

错误示例：

```text
Apifox 配置缺失，请先设置: APIFOX_PROJECT_ID, APIFOX_ACCESS_TOKEN
```

解决方式：在 `.env` 或部署环境中配置：

```dotenv
APIFOX_PROJECT_ID=your_project_id
APIFOX_ACCESS_TOKEN=your_access_token
```

### 文件不存在

错误示例：

```text
文件不存在: Modules/Order/docs/api/missing.json
```

解决方式：确认路径相对于 Laravel 项目根目录，或者传入绝对路径。

### 文件路径不受支持

错误示例：

```text
仅支持同步 docs/api/*.json 文件: Modules/Order/docs/orders.json
```

解决方式：把 API 文档放到模块的 `docs/api/` 目录下，例如：

```text
Modules/Order/docs/api/orders.json
```

### JSON 无法解析

错误示例：

```text
Apifox 文档 JSON 无法解析 [Modules/Order/docs/api/orders.json]: ...
```

解决方式：先修复 JSON 格式，再重新执行同步。

## 命令摘要

```bash
# 同步指定文件
php artisan apifox:sync Modules/Order/docs/api/orders.json

# 同步指定模块
php artisan apifox:sync --module=Order

# 同步多个模块
php artisan apifox:sync --module=Order --module=Ticket

# 同步全部配置路径中的文档
php artisan apifox:sync --all

# 预览待同步文件
php artisan apifox:sync --module=Order --dry-run
```

## 开发与测试

```bash
composer install
composer test
```

## License

MIT
