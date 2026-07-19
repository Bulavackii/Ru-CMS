# 📊 Модуль Menu (Меню)

**Версия:** 1.0.0  
**Приоритет:** 2

---

## Описание

GUI-редактор меню с поддержкой до 3 уровней вложенности. Позволяет создавать и управлять меню для header, footer и sidebar.

## Основные возможности

- ✅ Создание нескольких меню
- ✅ Позиции: header, footer, sidebar
- ✅ До 3 уровней вложенности пунктов меню
- ✅ Drag-and-drop для изменения порядка
- ✅ Связь с страницами и категориями
- ✅ Внешние ссылки
- ✅ Активация/деактивация меню и пунктов
- ✅ Кэширование меню для производительности

## Структура

```
modules/Menu/
├── Controllers/
│   ├── Admin/
│   │   ├── MenuController.php      # Управление меню
│   │   ├── MenuItemController.php  # Управление пунктами меню
│   │   └── PageController.php      # Управление страницами
│   └── Frontend/
│       └── PageController.php     # Публичные страницы
├── Models/
│   ├── Menu.php                    # Модель меню
│   ├── MenuItem.php               # Модель пункта меню
│   └── Page.php                    # Модель страницы
└── Views/
    ├── admin/                      # Админ-панель
    └── frontend/                   # Публичная часть
```

## Использование

### Получение меню по позиции

```php
use Modules\Menu\Models\Menu;

// Меню для header
$headerMenu = Menu::active()
    ->position('header')
    ->with(['items' => function($q) {
        $q->where('active', true)
          ->whereNull('parent_id')
          ->orderBy('order');
    }])
    ->first();
```

### Создание меню

```php
$menu = Menu::create([
    'title' => 'Главное меню',
    'position' => 'header',
    'active' => true,
]);

// Добавление пункта меню
$menu->items()->create([
    'title' => 'Главная',
    'url' => '/',
    'order' => 1,
    'active' => true,
]);
```

### Вложенные пункты меню

```php
$parentItem = $menu->items()->create([
    'title' => 'О нас',
    'url' => '/about',
    'order' => 2,
]);

// Дочерний пункт
$parentItem->children()->create([
    'title' => 'История',
    'url' => '/about/history',
    'order' => 1,
]);
```

## Маршруты

### Админка
- `GET /admin/menus` - список меню
- `GET /admin/menus/create` - создание меню
- `POST /admin/menus` - сохранение меню
- `GET /admin/menus/{menu}/edit` - редактирование меню
- `PATCH /admin/menus/{menu}/toggle` - включение/выключение меню
- `POST /admin/menus/{menu}/items/update-order` - обновление порядка
- `POST /admin/menus/{menu}/items` - создание пункта меню
- `PUT /admin/menus/{menu}/items/{item}` - обновление пункта
- `DELETE /admin/menus/{menu}/items/{item}` - удаление пункта

### Фронтенд
- `GET /pages/{slug}` - просмотр страницы

## Позиции меню

- `header` - Верхнее меню
- `footer` - Нижнее меню
- `sidebar` - Боковое меню

## Связи

- **items** (hasMany) - пункты меню
- **activeItems** (hasMany) - активные пункты меню
- **MenuItem.parent** (belongsTo) - родительский пункт
- **MenuItem.children** (hasMany) - дочерние пункты

## Использование в Blade

```blade
@php
    $menu = \Modules\Menu\Models\Menu::active()
        ->position('header')
        ->with('activeItems.activeChildren')
        ->first();
@endphp

@if($menu)
    <nav>
        @foreach($menu->activeItems as $item)
            <a href="{{ $item->url }}">{{ $item->title }}</a>
            @if($item->activeChildren->count())
                @foreach($item->activeChildren as $child)
                    <a href="{{ $child->url }}">{{ $child->title }}</a>
                @endforeach
            @endif
        @endforeach
    </nav>
@endif
```

## Миграции

Таблицы:
- `menus` - меню
- `menu_items` - пункты меню
- `pages` - страницы

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




