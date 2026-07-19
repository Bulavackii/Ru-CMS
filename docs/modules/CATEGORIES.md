# 🗂️ Модуль Categories (Категории)

**Версия:** 1.0.0  
**Приоритет:** 3

---

## Описание

Модуль категоризации контента и товаров с поддержкой иерархической структуры.

## Основные возможности

- ✅ Иерархическая структура категорий
- ✅ Связь с новостями, товарами, страницами
- ✅ SEO оптимизация
- ✅ Управление через админ-панель

## Структура

```
modules/Categories/
├── Controllers/
│   └── Admin/CategoryController.php
├── Models/
│   └── Category.php
└── Views/
    └── admin/
```

## Использование

```php
use Modules\Categories\Models\Category;

// Получить все категории
$categories = Category::all();

// Категории с дочерними
$categories = Category::with('children')->whereNull('parent_id')->get();
```

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




