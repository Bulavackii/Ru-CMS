# 🛡️ Модуль Captcha (Каптча)

**Версия:** 1.0.0  
**Приоритет:** 80

---

## Описание

Гибкая система каптчи с возможностью встраивания в любое место. Поддерживает различные типы каптчи: изображения, слайдер, математика, вопросы.

## Основные возможности

- ✅ Типы каптчи:
  - Изображения (выбор правильного изображения)
  - Слайдер (перетаскивание)
  - Математика (решение примера)
  - Вопросы (ответ на вопрос)
- ✅ Интеграция с Яндекс.Каптча (опционально)
- ✅ Настраиваемая сложность
- ✅ Защита от ботов

## Структура

```
modules/Captcha/
├── Controllers/
│   └── CaptchaController.php        # Генерация и проверка каптчи
├── Services/
│   ├── CaptchaService.php           # Основной сервис
│   └── YandexCaptchaService.php     # Интеграция с Яндекс
├── Config/
│   └── captcha.php                  # Конфигурация
└── Views/
    └── admin/                         # Админ-панель
```

## Использование

### Генерация каптчи

```php
use Modules\Captcha\Services\CaptchaService;

$captchaService = app(CaptchaService::class);
$captcha = $captchaService->generate('image'); // или 'slider', 'math', 'question'
```

### Проверка каптчи

```php
$isValid = $captchaService->verify($userInput, 'image');
```

## Типы каптчи

### Изображения
Пользователь должен выбрать правильное изображение из предложенных.

### Слайдер
Пользователь должен перетащить слайдер в правильную позицию.

### Математика
Пользователь должен решить простой математический пример.

### Вопросы
Пользователь должен ответить на вопрос.

## Использование в Blade

```blade
{{-- Компонент каптчи --}}
<x-captcha type="image" />

{{-- Или вручную --}}
@php
    $captchaService = app(\Modules\Captcha\Services\CaptchaService::class);
    $captcha = $captchaService->generate('image');
@endphp

<form method="POST">
    @csrf
    <div class="captcha">
        {!! $captcha['html'] !!}
        <input type="text" name="captcha" required>
        <input type="hidden" name="captcha_id" value="{{ $captcha['id'] }}">
    </div>
    <button type="submit">Отправить</button>
</form>
```

## Конфигурация

Файл: `modules/Captcha/Config/captcha.php`

```php
return [
    'default_type' => 'image', // image, slider, math, question
    'yandex' => [
        'enabled' => false,
        'server_key' => env('YANDEX_CAPTCHA_SERVER_KEY'),
        'client_key' => env('YANDEX_CAPTCHA_CLIENT_KEY'),
    ],
];
```

## Интеграция с Яндекс.Каптча

Для использования Яндекс.Каптча:

1. Получите ключи на https://yandex.ru/dev/captcha/
2. Добавьте в `.env`:
   ```
   YANDEX_CAPTCHA_SERVER_KEY=your_server_key
   YANDEX_CAPTCHA_CLIENT_KEY=your_client_key
   ```
3. Включите в конфиге: `'enabled' => true`

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




