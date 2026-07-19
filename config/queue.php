<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 📌 Очередь по умолчанию
    |--------------------------------------------------------------------------
    |
    | Laravel использует единую API для работы с разными типами очередей:
    | database, redis, beanstalkd, Amazon SQS и др.
    | Здесь указывается, какая из них будет использоваться по умолчанию.
    |
    */
    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Подключения к очередям
    |--------------------------------------------------------------------------
    |
    | Здесь ты можешь настроить несколько подключений к разным драйверам.
    | Например, одни задачи можно обрабатывать через Redis, другие — через SQS.
    |
    | Поддерживаемые драйверы:
    | "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */
    'connections' => [

        // 🔃 Sync — задачи выполняются немедленно (без очереди)
        'sync' => [
            'driver' => 'sync',
        ],

        // 🗃️ Database — хранит задачи в таблице `jobs`
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),              // подключение к БД
            'table' => env('DB_QUEUE_TABLE', 'jobs'),                // таблица заданий
            'queue' => env('DB_QUEUE', 'default'),                   // имя очереди
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),  // через сколько секунд пробовать снова
            'after_commit' => false,                                 // запускать только после commit транзакции
        ],

        // 📦 Beanstalkd — лёгкий брокер очередей
        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,         // ожидание новых задач (0 = не блокирует)
            'after_commit' => false,
        ],

        // ☁️ Amazon SQS
        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'), // СЮДА ВСТАВИТЬ AWS ACCESS KEY ID
            'secret' => env('AWS_SECRET_ACCESS_KEY'), // СЮДА ВСТАВИТЬ AWS SECRET ACCESS KEY
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        // 🔴 Redis — быстрая in-memory очередь
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 🧺 Пакетная обработка задач (Job Batching)
    |--------------------------------------------------------------------------
    |
    | Задачи можно объединять в пакеты (batch), чтобы запускать их группами.
    | Здесь указывается БД и таблица, где будет храниться информация об этом.
    |
    */
    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | ❌ Обработка неудачных заданий
    |--------------------------------------------------------------------------
    |
    | Если задание не удалось выполнить — Laravel может записать информацию
    | о нём в файл или таблицу. Это позволяет потом повторно запустить задание.
    |
    | Поддерживаемые драйверы: "database-uuids", "dynamodb", "file", "null"
    |
    */
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), // рекомендуемый — database-uuids
        'database' => env('DB_CONNECTION', 'sqlite'),             // подключение к БД
        'table' => 'failed_jobs',                                 // таблица для ошибок
    ],

];
