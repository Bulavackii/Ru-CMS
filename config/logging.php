<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | 📋 Канал логирования по умолчанию
    |--------------------------------------------------------------------------
    |
    | Laravel будет писать логи именно в этот канал, если ты явно не укажешь другой.
    | Чаще всего используется "stack" — объединение нескольких каналов.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | ⚠️ Логирование устаревших функций
    |--------------------------------------------------------------------------
    |
    | Позволяет логировать предупреждения о "устаревших" функциях и фичах
    | (например, в PHP 8.3/9.0). Полезно для подготовки к будущим версиям.
    |
    */
    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'), // логирование в канал (или null)
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),       // показывать стек вызова
    ],

    /*
    |--------------------------------------------------------------------------
    | 📡 Доступные каналы логирования
    |--------------------------------------------------------------------------
    |
    | Здесь настраиваются все возможные способы логирования.
    | Laravel использует библиотеку Monolog с поддержкой множества драйверов.
    |
    | Поддерживаемые драйверы: "single", "daily", "slack", "syslog",
    |                          "errorlog", "monolog", "custom", "stack"
    |
    */
    'channels' => [

        // 🧱 Stack — объединение каналов
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')), // можно указать несколько каналов
            'ignore_exceptions' => false, // не скрывать ошибки в логах других каналов
        ],

        // 📄 Single — один лог-файл (обычно: storage/logs/laravel.log)
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // 🗂️ Daily — создаёт новый файл каждый день, хранит ограниченное количество дней
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14), // количество дней хранения логов
            'replace_placeholders' => true,
        ],

        // 💬 Slack — отправка критических ошибок в Slack-чат
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'), // :warning: :fire: :zap:
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        // ☁️ Papertrail — внешняя система логирования
        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // 📤 stderr — вывод ошибок в стандартный поток (для Docker, CI/CD)
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'), // кастомный формат вывода
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // 🖥️ syslog — системный лог сервера (Linux)
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        // 📟 errorlog — системный лог PHP (вывод через error_log())
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // 🚫 null — логирование в никуда (выключено)
        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        // 🆘 emergency — аварийный лог, используется при сбоях в лог-системе
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
