# 📰 Модуль News (Новости)

**Версия:** 1.0.0  
**Приоритет:** 4

---

## Описание

Модуль управления новостями с поддержкой категорий, шаблонов, SEO-оптимизации и фильтрации.

## Основные возможности

- ✅ CRUD операции с новостями
- ✅ Фильтрация по категориям, шаблонам, статусу публикации
- ✅ Поиск по заголовку и содержимому
- ✅ SEO мета-теги (title, description, keywords, header)
- ✅ Связь с категориями (many-to-many)
- ✅ Версионирование контента
- ✅ Шаблоны для разных типов контента
- ✅ Поддержка цены и наличия (для товаров)
- ✅ Кэширование списка новостей

## Структура

```
modules/News/
├── Controllers/
│   ├── Admin/NewsController.php    # Админка
│   └── Frontend/NewsController.php # Фронтенд
├── Models/
│   └── News.php                    # Eloquent модель
├── Views/
│   ├── admin/                      # Админ-панель
│   └── frontend/                   # Публичная часть
├── Migrations/
│   └── *.php                       # Миграции БД
└── module.json
```

## Использование

### Получение опубликованных новостей

```php
use Modules\News\Models\News;

// Все опубликованные новости
$news = News::published()->get();

// С категориями
$news = News::with('categories')->published()->get();

// Поиск
$news = News::search('запрос')->get();
```

### Создание новости

```php
$news = News::create([
    'title' => 'Заголовок',
    'content' => 'Содержимое',
    'slug' => 'zagolovok',
    'published' => true,
    'template' => 'default',
]);

// Привязка категорий
$news->categories()->attach([1, 2, 3]);
```

### Фильтрация и поиск

```php
// По категории
$news = News::whereHas('categories', function($q) {
    $q->where('categories.id', 1);
})->get();

// По шаблону
$news = News::byTemplate('products')->get();

// Комбинированный поиск
$news = News::search('запрос')
    ->byTemplate('default')
    ->published()
    ->with('categories')
    ->paginate(10);
```

## Маршруты

### Админка
- `GET /admin/news` - список новостей
- `GET /admin/news/create` - создание
- `POST /admin/news` - сохранение
- `GET /admin/news/{id}/edit` - редактирование
- `PUT /admin/news/{id}` - обновление
- `DELETE /admin/news/{id}` - удаление

### Фронтенд
- `GET /news` - список опубликованных новостей
- `GET /news/{slug}` - просмотр новости

## Шаблоны

Поддерживаемые шаблоны:
- `default` - Новости
- `products` - Товары
- `ourworks` - Наши услуги
- `release` - Релизы
- `contacts` - Контакты
- `gallery` - Галерея
- `slideshow` - Слайдшоу
- `faq` - Вопросы
- `reviews` - Отзывы
- И другие...

## Связи

- **categories** (belongsToMany) - категории новости
- **creator** (belongsTo) - создатель новости
- **updater** (belongsTo) - последний редактор
- **slideshow** (hasOne) - связанное слайдшоу

## Events

- `NewsCreated` - при создании новости
- `NewsUpdated` - при обновлении новости
- `NewsDeleted` - при удалении новости

## Миграции

Таблицы:
- `news` - основная таблица новостей
- `news_category` - связь новостей и категорий

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




