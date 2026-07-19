# 📁 Модуль Files (Файлы)

**Версия:** 1.0.0  
**Приоритет:** 5

---

## Описание

Управление файлами: загрузка, скачивание, категории. Медиа-библиотека для управления всеми файлами системы.

## Основные возможности

- ✅ Загрузка файлов
- ✅ Категории файлов
- ✅ Метаданные файлов (размер, MIME-тип, размеры для изображений)
- ✅ Теги для поиска
- ✅ Alt-текст для изображений
- ✅ Управление через админ-панель

## Структура

```
modules/Files/
├── Controllers/
│   └── Admin/
│       ├── FileController.php        # Управление файлами
│       └── FileCategoryController.php # Управление категориями
├── Models/
│   ├── File.php                      # Модель файла
│   └── FileCategory.php              # Модель категории файлов
└── Views/
    └── admin/                         # Админ-панель
```

## Использование

### Загрузка файла

```php
use Modules\Files\Models\File;
use Illuminate\Support\Facades\Storage;

$uploadedFile = $request->file('file');
$path = $uploadedFile->store('files', 'public');

$file = File::create([
    'name' => $uploadedFile->hashName(),
    'original_name' => $uploadedFile->getClientOriginalName(),
    'path' => $path,
    'mime_type' => $uploadedFile->getMimeType(),
    'size' => $uploadedFile->getSize(),
    'category_id' => $categoryId,
]);

// Для изображений
if (str_starts_with($file->mime_type, 'image/')) {
    $image = \Intervention\Image\ImageManagerStatic::make($uploadedFile);
    $file->update([
        'width' => $image->width(),
        'height' => $image->height(),
    ]);
}
```

### Получение файлов

```php
use Modules\Files\Models\File;

// Все файлы
$files = File::all();

// По категории
$files = File::where('category_id', $categoryId)->get();

// Изображения
$images = File::where('mime_type', 'like', 'image/%')->get();
```

### Создание категории

```php
use Modules\Files\Models\FileCategory;

$category = FileCategory::create([
    'name' => 'Изображения',
    'slug' => 'images',
    'description' => 'Категория для изображений',
]);
```

## Миграции

Таблицы:
- `files` - файлы
- `file_categories` - категории файлов

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




