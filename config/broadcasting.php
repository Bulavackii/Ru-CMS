<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 📡 Broadcasting драйвер по умолчанию
    |--------------------------------------------------------------------------
    |
    | Определяет, какой драйвер используется для real-time уведомлений.
    | Возможные значения: pusher, ably, log, null, soketi
    |
    */
    'default' => env('BROADCAST_DRIVER', 'pusher'),

    'connections' => [
        /*
        |--------------------------------------------------------------------------
        | Pusher (рекомендуется для продакшена)
        |--------------------------------------------------------------------------
        |
        | СЮДА ВСТАВИТЬ КЛЮЧИ PUSHER:
        | Получить можно на https://pusher.com
        | PUSHER_APP_KEY - ваш App Key
        | PUSHER_APP_SECRET - ваш App Secret
        | PUSHER_APP_ID - ваш App ID
        | PUSHER_APP_CLUSTER - ваш кластер (например: mt1, eu, ap-southeast-1)
        |
        */
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'), // СЮДА ВСТАВИТЬ PUSHER APP KEY
            'secret' => env('PUSHER_APP_SECRET'), // СЮДА ВСТАВИТЬ PUSHER APP SECRET
            'app_id' => env('PUSHER_APP_ID'), // СЮДА ВСТАВИТЬ PUSHER APP ID
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'), // СЮДА ВСТАВИТЬ PUSHER CLUSTER
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Ably
        |--------------------------------------------------------------------------
        |
        | СЮДА ВСТАВИТЬ КЛЮЧ ABLY:
        | Получить можно на https://ably.com
        | ABLY_KEY - ваш API ключ от Ably
        |
        */
        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'), // СЮДА ВСТАВИТЬ ABLY API KEY
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        // Soketi для локальной разработки
        // СЮДА ВСТАВИТЬ КЛЮЧИ SOKETI (для локальной разработки)
        'soketi' => [
            'driver' => 'pusher',
            'key' => env('SOKETI_APP_KEY'), // СЮДА ВСТАВИТЬ APP KEY от Soketi
            'secret' => env('SOKETI_APP_SECRET'), // СЮДА ВСТАВИТЬ APP SECRET от Soketi
            'app_id' => env('SOKETI_APP_ID'), // СЮДА ВСТАВИТЬ APP ID от Soketi
            'options' => [
                'host' => env('SOKETI_HOST', '127.0.0.1'), // Хост Soketi сервера
                'port' => env('SOKETI_PORT', 6001), // Порт Soketi сервера
                'scheme' => env('SOKETI_SCHEME', 'http'), // http или https
                'encrypted' => false,
            ],
        ],
    ],
];

