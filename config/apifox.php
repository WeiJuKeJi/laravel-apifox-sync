<?php

return [
    'base_url' => env('APIFOX_BASE_URL', 'https://api.apifox.com'),
    'project_id' => env('APIFOX_PROJECT_ID'),
    'access_token' => env('APIFOX_ACCESS_TOKEN'),
    'api_version' => env('APIFOX_API_VERSION', '2024-03-28'),
    'locale' => env('APIFOX_LOCALE', 'zh-CN'),
    'timeout' => (int) env('APIFOX_TIMEOUT', 30),

    'paths' => [
        'Modules/*/docs/api/*.json',
        'vendor/weijukeji/*/docs/api/*.json',
    ],

    'module_path_pattern' => 'Modules/{module}/docs/api/*.json',

    'import_options' => [
        'targetEndpointFolderId' => (int) env('APIFOX_TARGET_ENDPOINT_FOLDER_ID', 0),
        'targetSchemaFolderId' => (int) env('APIFOX_TARGET_SCHEMA_FOLDER_ID', 0),
        'endpointOverwriteBehavior' => env('APIFOX_ENDPOINT_OVERWRITE_BEHAVIOR', 'OVERWRITE_EXISTING'),
        'schemaOverwriteBehavior' => env('APIFOX_SCHEMA_OVERWRITE_BEHAVIOR', 'OVERWRITE_EXISTING'),
        'updateFolderOfChangedEndpoint' => (bool) env('APIFOX_UPDATE_FOLDER_OF_CHANGED_ENDPOINT', false),
        'prependBasePath' => (bool) env('APIFOX_PREPEND_BASE_PATH', false),
    ],
];
