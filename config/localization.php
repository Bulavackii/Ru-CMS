<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 🌍 Настройки локализации для РФ/СНГ
    |--------------------------------------------------------------------------
    |
    | Конфигурация для адаптации системы под Россию и страны СНГ
    |
    */

    'default_country' => 'RU',
    'default_locale' => 'ru',
    'default_timezone' => 'Europe/Moscow',
    'default_currency' => 'RUB',
    'default_currency_symbol' => '₽',

    /*
    |--------------------------------------------------------------------------
    | Поддерживаемые страны
    |--------------------------------------------------------------------------
    */
    'supported_countries' => [
        'RU' => [
            'name' => 'Россия',
            'locale' => 'ru_RU',
            'timezone' => 'Europe/Moscow',
            'currency' => 'RUB',
            'currency_symbol' => '₽',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
        ],
        'BY' => [
            'name' => 'Беларусь',
            'locale' => 'ru_BY',
            'timezone' => 'Europe/Minsk',
            'currency' => 'BYN',
            'currency_symbol' => 'Br',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
        ],
        'KZ' => [
            'name' => 'Казахстан',
            'locale' => 'ru_KZ',
            'timezone' => 'Asia/Almaty',
            'currency' => 'KZT',
            'currency_symbol' => '₸',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
        ],
        'UA' => [
            'name' => 'Украина',
            'locale' => 'uk_UA',
            'timezone' => 'Europe/Kiev',
            'currency' => 'UAH',
            'currency_symbol' => '₴',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Поддерживаемые языки
    |--------------------------------------------------------------------------
    */
    'supported_languages' => [
        'ru' => 'Русский',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Форматы дат и времени
    |--------------------------------------------------------------------------
    */
    'date_formats' => [
        'ru' => 'd.m.Y',
        'en' => 'Y-m-d',
    ],

    'time_formats' => [
        'ru' => 'H:i',
        'en' => 'h:i A',
    ],

    /*
    |--------------------------------------------------------------------------
    | Форматы чисел
    |--------------------------------------------------------------------------
    */
    'number_formats' => [
        'ru' => [
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'decimals' => 2,
        ],
        'en' => [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
    ],
];

