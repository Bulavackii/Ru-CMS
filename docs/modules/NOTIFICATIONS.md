# 🔔 Модуль Notifications (Уведомления)

**Версия:** 1.0.0  
**Приоритет:** 7

---

## Описание

Система уведомлений с различными типами, позициями и поддержкой Web Push уведомлений.

## Основные возможности

- ✅ Различные типы уведомлений
- ✅ Позиции отображения (top, bottom, center)
- ✅ Цвета и иконки
- ✅ Web Push уведомления
- ✅ Автоматическое закрытие
- ✅ Ссылки на страницы
- ✅ Управление через админ-панель

## Структура

```
modules/Notifications/
├── Controllers/
│   ├── Admin/NotificationController.php
│   └── Frontend/NotificationController.php
├── Models/
│   └── Notification.php              # Модель уведомления
├── Resources/
│   └── js/                          # JavaScript для Web Push
└── Views/
    └── admin/                        # Админ-панель
```

## Использование

### Создание уведомления

```php
use Modules\Notifications\Models\Notification;

$notification = Notification::create([
    'title' => 'Новое уведомление',
    'message' => 'Текст уведомления',
    'type' => 'info', // info, success, warning, error
    'position' => 'top-right',
    'color' => '#3b82f6',
    'icon' => 'info-circle',
    'is_active' => true,
    'auto_close' => true,
    'duration' => 5000, // миллисекунды
    'link' => '/page', // опционально
]);
```

## Типы уведомлений

- `info` - Информационное
- `success` - Успех
- `warning` - Предупреждение
- `error` - Ошибка

## Позиции

- `top-left` - Верхний левый угол
- `top-right` - Верхний правый угол
- `top-center` - Верхний центр
- `bottom-left` - Нижний левый угол
- `bottom-right` - Нижний правый угол
- `bottom-center` - Нижний центр

## Web Push уведомления

Модуль поддерживает Web Push уведомления через браузер:

```php
use App\Services\WebPushService;

$webPush = app(WebPushService::class);
$webPush->broadcast([
    'title' => 'Заголовок',
    'body' => 'Текст уведомления',
    'icon' => '/favicon.svg',
    'data' => ['url' => '/page'],
], $userId);
```

## Использование в Blade

```blade
{{-- Компонент уведомлений --}}
<x-notifications />

{{-- Или вручную --}}
@php
    $notifications = \Modules\Notifications\Models\Notification::where('is_active', true)->get();
@endphp

@foreach($notifications as $notification)
    <div class="notification notification-{{ $notification->type }}" 
         data-position="{{ $notification->position }}"
         data-auto-close="{{ $notification->auto_close }}"
         data-duration="{{ $notification->duration }}">
        {{ $notification->message }}
    </div>
@endforeach
```

## Миграции

Таблицы:
- `notifications` - уведомления

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




