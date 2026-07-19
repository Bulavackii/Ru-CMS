<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ✉️ Почтовый драйвер по умолчанию
    |--------------------------------------------------------------------------
    |
    | Этот параметр определяет, какой mailer используется по умолчанию
    | во всём приложении. Его можно изменить через переменную .env:
    |
    | MAIL_MAILER=smtp / log / ses / mailgun / sendmail и т.д.
    |
    */
    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | 🛠️ Конфигурация почтовых драйверов (mailers)
    |--------------------------------------------------------------------------
    |
    | Здесь настраиваются все доступные mailers (транспортные драйверы).
    | Каждый mailer имеет свои параметры, например: smtp, ses, log и т.д.
    |
    | Поддерживаемые драйверы:
    | "smtp", "sendmail", "mailgun", "ses", "ses-v2", "postmark", "resend",
    | "log", "array", "failover", "roundrobin"
    |
    */
    'mailers' => [

        // 📤 SMTP — классическая отправка через SMTP-сервер
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'), // tls / ssl
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'), // СЮДА ВСТАВИТЬ ИМЯ ПОЛЬЗОВАТЕЛЯ SMTP
            'password' => env('MAIL_PASSWORD'), // СЮДА ВСТАВИТЬ ПАРОЛЬ SMTP
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // ☁️ Amazon SES
        'ses' => [
            'transport' => 'ses',
        ],

        // 💬 Postmark — сервис отправки email
        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => ['timeout' => 5],
        ],

        // 🔁 Resend — современный mail API от Vercel
        'resend' => [
            'transport' => 'resend',
        ],

        // 📮 Sendmail — системная отправка через Linux (устаревший способ)
        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        // 🪵 Log — запись писем в лог-файл (удобно для разработки)
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'), // может быть "stack", "daily" и т.д.
        ],

        // 🧪 Array — сохраняет письма в массив (только в runtime, не отправляет)
        'array' => [
            'transport' => 'array',
        ],

        // 🚨 Failover — резервная отправка: если один mailer не сработал, используется другой
        'failover' => [
            'transport' => 'failover',
            'mailers' => ['smtp', 'log'], // сначала smtp, если ошибка — лог
            'retry_after' => 60, // повторить попытку через 60 секунд
        ],

        // 🔄 Roundrobin — циклическая отправка через несколько провайдеров
        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => ['ses', 'postmark'],
            'retry_after' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 📬 Адрес отправителя по умолчанию
    |--------------------------------------------------------------------------
    |
    | Указывается email и имя, от которых будет отправляться вся почта
    | по умолчанию. Их можно изменить в .env:
    |
    | MAIL_FROM_ADDRESS=no-reply@example.com
    | MAIL_FROM_NAME="My CMS"
    |
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
