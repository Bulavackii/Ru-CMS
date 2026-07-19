<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateApiDocs extends Command
{
    protected $signature = 'api:docs:generate';
    protected $description = 'Generate API documentation from OpenAPI annotations';

    public function handle()
    {
        $this->info('Generating API documentation...');

        // Базовая структура OpenAPI документации
        $docs = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'RU CMS API',
                'version' => '1.0.0',
                'description' => 'API документация для RU CMS - модульной CMS для России и СНГ',
                'contact' => [
                    'email' => 'support@rucms.ru',
                ],
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api',
                    'description' => 'Production server',
                ],
            ],
            'tags' => [
                ['name' => 'Auth', 'description' => 'Аутентификация и авторизация'],
                ['name' => 'News', 'description' => 'Управление новостями'],
                ['name' => 'Categories', 'description' => 'Управление категориями'],
                ['name' => 'Pages', 'description' => 'Управление страницами'],
            ],
            'paths' => $this->generatePaths(),
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Введите токен в формате: Bearer {token}',
                    ],
                ],
                'schemas' => $this->generateSchemas(),
            ],
        ];

        $jsonPath = public_path('api-docs.json');
        File::ensureDirectoryExists(public_path());
        File::put($jsonPath, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info("API documentation generated successfully at: {$jsonPath}");
        return 0;
    }

    private function generatePaths(): array
    {
        return [
            '/v1/auth/register' => [
                'post' => [
                    'tags' => ['Auth'],
                    'summary' => 'Регистрация нового пользователя',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'email', 'password', 'password_confirmation'],
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'John Doe'],
                                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'],
                                        'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123'],
                                        'password_confirmation' => ['type' => 'string', 'format' => 'password'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Пользователь зарегистрирован',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'message' => ['type' => 'string'],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'token' => ['type' => 'string'],
                                                    'user' => ['$ref' => '#/components/schemas/User'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '422' => ['description' => 'Ошибка валидации'],
                    ],
                ],
            ],
            '/v1/auth/login' => [
                'post' => [
                    'tags' => ['Auth'],
                    'summary' => 'Аутентификация пользователя',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['email', 'password'],
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'password' => ['type' => 'string', 'format' => 'password'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Успешная аутентификация',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'message' => ['type' => 'string'],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'token' => ['type' => 'string'],
                                                    'user' => ['$ref' => '#/components/schemas/User'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['description' => 'Неверные учетные данные'],
                    ],
                ],
            ],
            '/v1/auth/logout' => [
                'post' => [
                    'tags' => ['Auth'],
                    'summary' => 'Выход из системы',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'Успешный выход'],
                        '401' => ['description' => 'Не авторизован'],
                    ],
                ],
            ],
            '/v1/auth/me' => [
                'get' => [
                    'tags' => ['Auth'],
                    'summary' => 'Информация о текущем пользователе',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Информация о пользователе',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => ['$ref' => '#/components/schemas/User'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['description' => 'Не авторизован'],
                    ],
                ],
            ],
            '/v1/news' => [
                'get' => [
                    'tags' => ['News'],
                    'summary' => 'Список новостей',
                    'parameters' => [
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'items' => [
                                                        'type' => 'array',
                                                        'items' => ['$ref' => '#/components/schemas/News'],
                                                    ],
                                                    'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['News'],
                    'summary' => 'Создать новость',
                    'security' => [['bearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['title', 'content'],
                                    'properties' => [
                                        'title' => ['type' => 'string'],
                                        'content' => ['type' => 'string'],
                                        'slug' => ['type' => 'string'],
                                        'meta_title' => ['type' => 'string'],
                                        'meta_description' => ['type' => 'string'],
                                        'category_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Created'],
                        '401' => ['description' => 'Не авторизован'],
                    ],
                ],
            ],
            '/v1/news/{id}' => [
                'get' => [
                    'tags' => ['News'],
                    'summary' => 'Получить новость',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Success'],
                        '404' => ['description' => 'Not Found'],
                    ],
                ],
                'put' => [
                    'tags' => ['News'],
                    'summary' => 'Обновить новость',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => ['type' => 'string'],
                                        'content' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Success'],
                        '401' => ['description' => 'Не авторизован'],
                    ],
                ],
                'delete' => [
                    'tags' => ['News'],
                    'summary' => 'Удалить новость',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'No Content'],
                        '401' => ['description' => 'Не авторизован'],
                    ],
                ],
            ],
        ];
    }

    private function generateSchemas(): array
    {
        return [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'News' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'content' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'meta_title' => ['type' => 'string', 'nullable' => true],
                    'meta_description' => ['type' => 'string', 'nullable' => true],
                    'published' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                ],
            ],
        ];
    }
}

