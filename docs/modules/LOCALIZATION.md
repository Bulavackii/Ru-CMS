# 🌍 Модуль Localization (Локализация)

**Версия:** 1.0.0  
**Приоритет:** 50

---

## Описание

Модуль управления переводами, форматами даты, времени и валюты для разных стран. Поддержка мультиязычности и локализации контента.

## Основные возможности

- ✅ Управление языками
- ✅ Переводы интерфейса
- ✅ Форматы даты и времени для разных стран
- ✅ Форматы валют
- ✅ Автоматическое определение языка
- ✅ Переключатель языков
- ✅ Пресеты для стран (Россия, Украина, Казахстан и др.)

## Структура

```
modules/Localization/
├── Controllers/
│   ├── Admin/LocalizationController.php
│   └── Frontend/LanguageController.php
├── Models/
│   ├── Language.php                  # Языки
│   └── Country.php                   # Страны
├── Services/
│   └── LocalizationService.php      # Сервис локализации
└── Views/
    ├── admin/                        # Админ-панель
    └── frontend/                     # Публичная часть
```

## Использование

### Получение текущего языка

```php
use Modules\Localization\Services\LocalizationService;

$localization = app(LocalizationService::class);
$currentLanguage = $localization->getCurrentLanguage();
```

### Форматирование даты

```php
$date = now();
$formatted = $localization->formatDate($date, 'ru_RU');
// Результат: 28.01.2025
```

### Форматирование валюты

```php
$amount = 1000.50;
$formatted = $localization->formatCurrency($amount, 'RUB', 'ru_RU');
// Результат: 1 000,50 ₽
```

### Переключение языка

```php
$localization->setLanguage('en');
```

## Поддерживаемые языки

- `ru` - Русский
- `en` - Английский
- Другие языки (настраиваемые)

## Пресеты стран

Модуль включает пресеты для:
- Россия
- Украина
- Казахстан
- Беларусь
- И другие страны СНГ

## Middleware

Модуль автоматически определяет язык пользователя через `LocalizationMiddleware`.

## Использование в Blade

```blade
{{-- Переключатель языков --}}
@include('Localization::frontend.switcher')

{{-- Переводы --}}
{{ __('Localization::messages.welcome') }}

{{-- Форматирование даты --}}
{{ $localization->formatDate(now()) }}

{{-- Форматирование валюты --}}
{{ $localization->formatCurrency(1000, 'RUB') }}
```

## Конфигурация

Файл: `config/localization.php`

```php
return [
    'default_locale' => 'ru',
    'fallback_locale' => 'en',
    'supported_locales' => ['ru', 'en'],
];
```

## Миграции

Таблицы:
- `languages` - языки
- `countries` - страны
- `translations` - переводы

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




