<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | 🧩 Подключение к БД по умолчанию
    |--------------------------------------------------------------------------
    |
    | Определяет, какая база данных будет использоваться по умолчанию
    | во всех Eloquent-запросах и миграциях.
    |
    | 🔴 Поддерживается ТОЛЬКО PostgreSQL (`pgsql`). Это железное правило
    | проекта: в мастере установки других драйверов нет, а запросы и миграции
    | рассчитаны на него.
    |
    | `sqlite` — единственное исключение: на нём гоняются тесты (phpunit.xml),
    | поэтому миграции обязаны быть драйвер-нейтральными (Schema Builder, а не
    | сырой SQL).
    |
    | Описания mysql, mariadb и sqlsrv отсюда убраны, чтобы не выглядели
    | поддерживаемым выбором.
    |
    | ⚠️ НО ИЗ РАБОТАЮЩЕГО ПРИЛОЖЕНИЯ ОНИ НЕ ИСЧЕЗАЮТ. Laravel 11+ сливает
    | `database.connections` со своими значениями по умолчанию
    | (`LoadConfiguration::mergeableOptions()`), поэтому
    | `config('database.connections')` по-прежнему отдаёт все пять. Удалением
    | из этого файла драйвер не отключить — проверено.
    |
    | Настоящий заслон стоит в `AppServiceProvider`: он падает с внятной
    | ошибкой, если `DB_CONNECTION` не pgsql и не sqlite.
    |
    */
    'default' => env('DB_CONNECTION', 'pgsql'),

    /*
    |--------------------------------------------------------------------------
    | 💾 Подключения ко всем базам данных
    |--------------------------------------------------------------------------
    |
    | Здесь можно задать настройки для разных драйверов и серверов.
    | Можно использовать сразу несколько подключений в одном проекте.
    |
    */
    'connections' => [

        // 🟣 SQLite — файл, простой для локальной разработки
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        // 🔵 PostgreSQL (pgsql)
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''), // СЮДА ВСТАВИТЬ ПАРОЛЬ БАЗЫ ДАННЫХ
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 🧬 Таблица для миграций
    |--------------------------------------------------------------------------
    |
    | Laravel отслеживает выполненные миграции в этой таблице,
    | чтобы не запускать их повторно.
    |
    */
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 🔴 Redis — быстрый кэш и брокер сообщений
    |--------------------------------------------------------------------------
    |
    | Redis — мощный key-value store. Используется для:
    | - Кэширования
    | - Очередей
    | - Хранения временных сессий
    |
    */
    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        // 🔁 Основное подключение к Redis
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'), // СЮДА ВСТАВИТЬ REDIS PASSWORD (если установлен)
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        // 📦 Redis для кэша
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'), // СЮДА ВСТАВИТЬ REDIS PASSWORD (если установлен)
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
