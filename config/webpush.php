<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    |
    | Ключи для VAPID (Voluntary Application Server Identification)
    | Генерируются командой: php artisan webpush:generate-keys
    |
    */
    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
        'subject' => env('VAPID_SUBJECT', env('APP_URL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки уведомлений
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'default_icon' => env('APP_URL') . '/favicon.svg',
        'default_badge' => env('APP_URL') . '/favicon.svg',
        'default_sound' => null,
        'default_vibrate' => [200, 100, 200],
        'default_ttl' => 86400, // 24 часа
    ],
];

