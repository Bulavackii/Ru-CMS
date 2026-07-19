<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 📧 Mailgun
    |--------------------------------------------------------------------------
    |
    | СЮДА ВСТАВИТЬ КЛЮЧИ MAILGUN:
    | Получить можно на https://mailgun.com
    | MAILGUN_DOMAIN - ваш домен в Mailgun
    | MAILGUN_SECRET - ваш API Secret Key
    |
    */
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'), // СЮДА ВСТАВИТЬ MAILGUN DOMAIN
        'secret' => env('MAILGUN_SECRET'), // СЮДА ВСТАВИТЬ MAILGUN SECRET KEY
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | 📧 Postmark
    |--------------------------------------------------------------------------
    |
    | СЮДА ВСТАВИТЬ ТОКЕН POSTMARK:
    | Получить можно на https://postmarkapp.com
    | POSTMARK_TOKEN - ваш Server API Token
    |
    */
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'), // СЮДА ВСТАВИТЬ POSTMARK SERVER API TOKEN
    ],

    /*
    |--------------------------------------------------------------------------
    | ☁️ Amazon SES
    |--------------------------------------------------------------------------
    |
    | СЮДА ВСТАВИТЬ КЛЮЧИ AWS:
    | Получить можно в AWS Console (IAM)
    | AWS_ACCESS_KEY_ID - ваш Access Key ID
    | AWS_SECRET_ACCESS_KEY - ваш Secret Access Key
    | AWS_DEFAULT_REGION - регион (например: us-east-1, eu-west-1)
    |
    */
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'), // СЮДА ВСТАВИТЬ AWS ACCESS KEY ID
        'secret' => env('AWS_SECRET_ACCESS_KEY'), // СЮДА ВСТАВИТЬ AWS SECRET ACCESS KEY
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), // СЮДА ВСТАВИТЬ AWS REGION
    ],

    /*
    |--------------------------------------------------------------------------
    | 💬 Telegram Bot
    |--------------------------------------------------------------------------
    |
    | СЮДА ВСТАВИТЬ ДАННЫЕ TELEGRAM BOT:
    | Получить можно у @BotFather в Telegram
    | TELEGRAM_BOT_TOKEN - токен вашего бота
    | TELEGRAM_CHAT_ID - ID чата для уведомлений
    |
    */
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'), // СЮДА ВСТАВИТЬ TELEGRAM BOT TOKEN
        'chat_id' => env('TELEGRAM_CHAT_ID'), // СЮДА ВСТАВИТЬ TELEGRAM CHAT ID
    ],

    /*
    |--------------------------------------------------------------------------
    | 🔍 Elasticsearch
    |--------------------------------------------------------------------------
    |
    | Настройки для полнотекстового поиска
    | ELASTICSEARCH_ENABLED - включить/выключить (true/false)
    | ELASTICSEARCH_HOST - адрес сервера Elasticsearch
    | ELASTICSEARCH_INDEX - название индекса
    |
    */
    'elasticsearch' => [
        'enabled' => env('ELASTICSEARCH_ENABLED', false),
        'host' => env('ELASTICSEARCH_HOST', 'http://localhost:9200'), // СЮДА ВСТАВИТЬ АДРЕС ELASTICSEARCH
        'index' => env('ELASTICSEARCH_INDEX', 'cms_content'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 📱 SMS провайдеры
    |--------------------------------------------------------------------------
    |
    | SMS_PROVIDER - выберите провайдера: smsru или twilio
    |
    | Для SMS.ru:
    | SMSRU_API_ID - ваш API ID от SMS.ru (получить на https://sms.ru)
    |
    | Для Twilio:
    | TWILIO_ACCOUNT_SID - ваш Account SID
    | TWILIO_AUTH_TOKEN - ваш Auth Token
    | TWILIO_FROM - номер отправителя (формат: +1234567890)
    |
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'smsru'), // smsru или twilio
        'smsru' => [
            'api_id' => env('SMSRU_API_ID'), // СЮДА ВСТАВИТЬ SMS.RU API ID
        ],
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'), // СЮДА ВСТАВИТЬ TWILIO ACCOUNT SID
            'auth_token' => env('TWILIO_AUTH_TOKEN'), // СЮДА ВСТАВИТЬ TWILIO AUTH TOKEN
            'from' => env('TWILIO_FROM'), // СЮДА ВСТАВИТЬ TWILIO НОМЕР ОТПРАВИТЕЛЯ
        ],
    ],
];
