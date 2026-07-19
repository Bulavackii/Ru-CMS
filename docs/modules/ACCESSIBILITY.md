# ♿ Модуль Accessibility (Спецвозможности)

**Версия:** 1.0.0  
**Приоритет:** 14

---

## Описание

Настройки доступности для людей с ограниченными возможностями. Улучшает доступность сайта для всех пользователей.

## Основные возможности

- ✅ Увеличение шрифта
- ✅ Высокий контраст
- ✅ Упрощенная навигация
- ✅ Голосовое сопровождение
- ✅ Настройки для слабовидящих

## Структура

```
modules/Accessibility/
├── Controllers/
│   └── Admin/AccessibilityController.php
├── Models/
│   └── AccessibilitySetting.php     # Настройки доступности
└── Views/
    ├── admin/                         # Админ-панель
    ├── Components/                    # Компоненты
    └── frontend/                      # Публичная часть
```

## Использование

### Получение настроек

```php
use Modules\Accessibility\Models\AccessibilitySetting;

$settings = AccessibilitySetting::first();
```

### Применение настроек

Настройки применяются автоматически через компоненты и JavaScript.

## Использование в Blade

```blade
{{-- Компонент доступности --}}
<x-accessibility-settings />

{{-- Или вручную --}}
@include('Accessibility::frontend.settings')
```

## Миграции

Таблицы:
- `accessibility_settings` - настройки доступности

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




