<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'RU CMS API Documentation',
                'version' => '1.0.0',
                'description' => 'API документация для RU CMS - модульной CMS для России и СНГ',
            ],
            'routes' => [
                'api' => 'api/documentation',
            ],
            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', false),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'annotations' => [
                    base_path('app/Http/Controllers/Api'),
                ],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_by' => 'tags',
            'hide_from_documentation' => false,
        ],
        'paths' => [
            'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', false),
            'docs_json' => 'api-docs.json',
            'docs_yaml' => 'api-docs.yaml',
            'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
            'annotations' => [
                base_path('app/Http/Controllers'),
            ],
        ],
        'swagger' => [
            'swagger' => '2.0',
            'info' => [
                'description' => 'API документация для RU CMS',
                'title' => 'RU CMS API',
                'version' => '1.0.0',
                'contact' => [
                    'email' => 'support@rucms.ru',
                ],
            ],
            'host' => env('APP_URL', 'http://localhost'),
            'basePath' => '/api',
            'schemes' => [
                'http',
                'https',
            ],
            'consumes' => [
                'application/json',
            ],
            'produces' => [
                'application/json',
            ],
            'securityDefinitions' => [
                'bearerAuth' => [
                    'type' => 'apiKey',
                    'name' => 'Authorization',
                    'in' => 'header',
                    'description' => 'Введите токен в формате: Bearer {token}',
                ],
            ],
        ],
    ],
];

