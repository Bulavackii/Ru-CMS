<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | 🗄️ Хранилище кэша по умолчанию
    |--------------------------------------------------------------------------
    |
    | Здесь указывается, какое "хранилище" (store) будет использоваться
    | по умолчанию во всём приложении, если не указано иное.
    | Наиболее надёжный вариант — `database`, `redis`, `file`.
    |
    */
    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | 🧰 Доступные хранилища кэша
    |--------------------------------------------------------------------------
    |
    | Здесь определяются все доступные драйверы кэша и их настройки.
    | Можно использовать сразу несколько разных систем кэширования.
    |
    | Поддерживаемые драйверы:
    | "array", "database", "file", "memcached", "redis", "dynamodb", "octane", "null"
    |
    */
    'stores' => [

        // 🔁 Быстрый временный кэш (RAM, сбрасывается при перезапуске)
        'array' => [
            'driver' => 'array',
            'serialize' => false, // если false — не сериализует объекты (экономит память)
        ],

        // 🗃️ Кэш в БД
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'), // можно задать другую БД
            'table' => env('DB_CACHE_TABLE', 'cache'), // таблица для хранения кэша
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'), // для блокировок
            'lock_table' => env('DB_CACHE_LOCK_TABLE'), // таблица блокировок
        ],

        // 📂 Кэш в файловой системе
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'), // папка хранения
            'lock_path' => storage_path('framework/cache/data'), // блокировки
        ],

        // ⚡ Memcached (кэш в памяти, используется на крупных проектах)
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        // 🟥 Redis — быстрый кэш в памяти (рекомендуется для продакшена)
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        // ☁️ AWS DynamoDB (облачный масштабируемый кэш)
        /*
        |--------------------------------------------------------------------------
        | ☁️ DynamoDB Cache
        |--------------------------------------------------------------------------
        |
        | СЮДА ВСТАВИТЬ КЛЮЧИ AWS для DynamoDB:
        | AWS_ACCESS_KEY_ID - ваш Access Key ID
        | AWS_SECRET_ACCESS_KEY - ваш Secret Access Key
        | AWS_DEFAULT_REGION - регион AWS
        | DYNAMODB_CACHE_TABLE - название таблицы в DynamoDB
        |
        */
        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'), // СЮДА ВСТАВИТЬ AWS ACCESS KEY ID
            'secret' => env('AWS_SECRET_ACCESS_KEY'), // СЮДА ВСТАВИТЬ AWS SECRET ACCESS KEY
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), // СЮДА ВСТАВИТЬ AWS REGION
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        // 🚀 Octane кэш — для high-performance серверов (RoadRunner, Swoole)
        'octane' => [
            'driver' => 'octane',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 🔑 Префикс для ключей кэша
    |--------------------------------------------------------------------------
    |
    | Чтобы избежать конфликтов ключей между несколькими приложениями,
    | использующими один и тот же механизм кэширования, можно задать префикс.
    | Например: mycms_cache_homepage, mycms_cache_menu и т.д.
    |
    */
    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_cache_'),

];
