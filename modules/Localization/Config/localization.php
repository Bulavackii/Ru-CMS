<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 🌍 Настройки локализации
    |--------------------------------------------------------------------------
    |
    | Основные настройки модуля локализации
    |
    */

    // Кеш время (в секундах)
    'cache_ttl' => env('LOCALIZATION_CACHE_TTL', 3600),

    // Страна по умолчанию
    'default_country' => env('LOCALIZATION_DEFAULT_COUNTRY', 'RU'),

    // Поддерживаемые форматы дат
    'date_formats' => [
        'd.m.Y' => 'DD.MM.YYYY',
        'Y-m-d' => 'YYYY-MM-DD',
        'm/d/Y' => 'MM/DD/YYYY',
        'd/m/Y' => 'DD/MM/YYYY',
        'Y.m.d' => 'YYYY.MM.DD',
    ],

    // Поддерживаемые форматы времени
    'time_formats' => [
        'H:i' => '24 часа (14:30)',
        'h:i A' => '12 часов (02:30 PM)',
        'H:i:s' => '24 часа с секундами (14:30:45)',
    ],

    // Разделители
    'decimal_separators' => [
        '.' => 'Точка (.)',
        ',' => 'Запятая (,)',
    ],

    'thousands_separators' => [
        ' ' => 'Пробел',
        ',' => 'Запятая',
        '.' => 'Точка',
    ],

    // Группы настроек
    'groups' => [
        'general' => 'Общие',
        'date' => 'Дата и время',
        'currency' => 'Валюта',
        'format' => 'Форматы',
        'translation' => 'Переводы',
    ],

    // Типы значений
    'types' => [
        'string' => 'Текст',
        'number' => 'Число',
        'boolean' => 'Логическое',
        'json' => 'JSON',
        'array' => 'Массив',
    ],

    // Предустановленные страны (можно импортировать)
    'preset_countries' => [
        'RU' => [
            'name' => 'Россия',
            'native_name' => 'Россия',
            'flag' => '🇷🇺',
            'currency_code' => 'RUB',
            'currency_symbol' => '₽',
            'locale' => 'ru',
            'timezone' => 'Europe/Moscow',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'decimal_places' => 2,
            'active' => true,
        ],
        'KZ' => [
            'name' => 'Казахстан',
            'native_name' => 'Қазақстан',
            'flag' => '🇰🇿',
            'currency_code' => 'KZT',
            'currency_symbol' => '₸',
            'locale' => 'kk',
            'timezone' => 'Asia/Almaty',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'decimal_places' => 2,
            'active' => true,
        ],
        'US' => [
            'name' => 'США',
            'native_name' => 'United States',
            'flag' => '🇺🇸',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'date_format' => 'm/d/Y',
            'time_format' => 'h:i A',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimal_places' => 2,
            'active' => true,
        ],
        'GB' => [
            'name' => 'Великобритания',
            'native_name' => 'United Kingdom',
            'flag' => '🇬🇧',
            'currency_code' => 'GBP',
            'currency_symbol' => '£',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimal_places' => 2,
            'active' => true,
        ],
        'DE' => [
            'name' => 'Германия',
            'native_name' => 'Deutschland',
            'flag' => '🇩🇪',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'locale' => 'de',
            'timezone' => 'Europe/Berlin',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'decimal_places' => 2,
            'active' => true,
        ],
        'FR' => [
            'name' => 'Франция',
            'native_name' => 'France',
            'flag' => '🇫🇷',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'locale' => 'fr',
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'decimal_places' => 2,
            'active' => true,
        ],
        'IT' => [
            'name' => 'Италия',
            'native_name' => 'Italia',
            'flag' => '🇮🇹',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'locale' => 'it',
            'timezone' => 'Europe/Rome',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'decimal_places' => 2,
            'active' => true,
        ],
    ],
];
