# 🎨 Модуль Visual (Визуальный редактор)

**Версия:** 1.1.0  
**Приоритет:** 100

---

## Описание

Визуальный редактор тем и фрагментов. Позволяет создавать и редактировать темы и фрагменты кода через веб-интерфейс.

## Основные возможности

- ✅ Визуальный редактор тем
- ✅ Редактирование фрагментов (snippets)
- ✅ Предпросмотр изменений
- ✅ Версионирование тем
- ✅ Экспорт/импорт тем
- ✅ Поддержка Blade шаблонов

## Структура

```
modules/Visual/
├── Controllers/
│   └── Admin/VisualController.php
├── Models/
│   ├── Theme.php                     # Модель темы
│   ├── ThemeVersion.php             # Версии тем
│   └── Fragment.php                 # Фрагменты
├── Services/
│   └── ThemeService.php             # Сервис управления темами
└── Views/
    └── admin/                        # Админ-панель
```

## Использование

### Создание темы

```php
use Modules\Visual\Models\Theme;

$theme = Theme::create([
    'name' => 'my-theme',
    'title' => 'Моя тема',
    'description' => 'Описание темы',
    'is_active' => true,
]);
```

### Редактирование фрагмента

```php
use Modules\Visual\Models\Fragment;

$fragment = Fragment::create([
    'theme_id' => $theme->id,
    'name' => 'header',
    'content' => '<header>...</header>',
    'type' => 'blade', // blade, html, css, js
]);
```

## Типы фрагментов

- `blade` - Blade шаблоны
- `html` - HTML код
- `css` - CSS стили
- `js` - JavaScript код

## Использование в Blade

```blade
@php
    $theme = \Modules\Visual\Models\Theme::where('is_active', true)->first();
    $fragment = $theme->fragments()->where('name', 'header')->first();
@endphp

{!! $fragment->content !!}
```

## Миграции

Таблицы:
- `visual_themes` - темы
- `theme_versions` - версии тем
- `fragments` - фрагменты

---

**Версия:** 1.1.0  
**Последнее обновление:** 2025-01-28




