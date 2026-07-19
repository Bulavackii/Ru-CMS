# 📢 Модуль NewsIO (Импорт/Экспорт)

**Версия:** 1.0.0  
**Приоритет:** 4

---

## Описание

Массовый импорт и экспорт новостей. Позволяет импортировать новости из различных источников и экспортировать их в различные форматы.

## Основные возможности

- ✅ Импорт новостей из CSV/Excel
- ✅ Экспорт новостей в CSV/Excel
- ✅ Импорт из RSS
- ✅ Импорт из WordPress
- ✅ Валидация данных при импорте
- ✅ Обработка ошибок
- ✅ Фоновая обработка больших файлов

## Структура

```
modules/NewsIO/
├── Controllers/
│   └── Admin/NewsIOController.php
├── Console/
│   ├── ImportNewsCommand.php        # Команда импорта
│   └── ExportNewsCommand.php        # Команда экспорта
├── Services/
│   ├── ImportService.php            # Сервис импорта
│   └── ExportService.php            # Сервис экспорта
└── Views/
    └── admin/                        # Админ-панель
```

## Использование

### Импорт новостей

```bash
# Через команду
php artisan news:import /path/to/file.csv

# Через админ-панель
POST /admin/news/import
```

### Экспорт новостей

```bash
# Через команду
php artisan news:export /path/to/output.csv

# Через админ-панель
GET /admin/news/export
```

### Программный импорт

```php
use Modules\NewsIO\Services\ImportService;

$importService = app(ImportService::class);
$result = $importService->importFromFile('/path/to/file.csv');
```

### Программный экспорт

```php
use Modules\NewsIO\Services\ExportService;

$exportService = app(ExportService::class);
$exportService->exportToFile('/path/to/output.csv', [
    'published' => true, // только опубликованные
    'category_id' => 1,  // из категории
]);
```

## Поддерживаемые форматы

- CSV
- Excel (XLSX)
- RSS
- WordPress XML

## Команды Artisan

```bash
# Импорт
php artisan news:import {file} [--format=csv] [--category=1]

# Экспорт
php artisan news:export {file} [--format=csv] [--published] [--category=1]
```

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




