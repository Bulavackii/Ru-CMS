# 💬 Модуль Comments (Комментарии)

**Версия:** 1.0.0  
**Приоритет:** 60

---

## Описание

Система комментариев с модерацией, вложенными комментариями и интеграцией с каптчей для защиты от спама.

## Основные возможности

- ✅ Полиморфные комментарии (для любых моделей)
- ✅ Вложенные комментарии (ответы)
- ✅ Модерация комментариев
- ✅ Лайки и дизлайки
- ✅ Интеграция с модулем Captcha
- ✅ Автоматическое определение спама
- ✅ Автоматическое одобрение для авторизованных пользователей
- ✅ Фильтрация по статусу

## Структура

```
modules/Comments/
├── Controllers/
│   ├── Admin/CommentController.php    # Модерация
│   └── Frontend/CommentController.php # Публичная часть
├── Models/
│   ├── Comment.php                    # Модель комментария
│   └── CommentVote.php               # Модель голоса
├── Services/
│   └── CommentService.php            # Сервис управления комментариями
└── Views/
    ├── admin/                         # Админ-панель
    └── frontend/                      # Публичная часть
```

## Использование

### Добавление комментариев к модели

```php
use Modules\Comments\Models\Comment;

// В модели News
class News extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'model');
    }
    
    public function approvedComments()
    {
        return $this->morphMany(Comment::class, 'model')
            ->where('status', 'approved')
            ->whereNull('parent_id');
    }
}
```

### Создание комментария

```php
use Modules\Comments\Services\CommentService;

$commentService = app(CommentService::class);

$comment = $commentService->create([
    'model_type' => News::class,
    'model_id' => $news->id,
    'content' => 'Отличная статья!',
    'user_id' => auth()->id(), // или null для гостей
    'author_name' => 'Иван', // для гостей
    'author_email' => 'ivan@example.com', // для гостей
    'captcha' => 'captcha_value', // для гостей
]);
```

### Получение комментариев

```php
$comments = $commentService->getForModel(
    News::class,
    $news->id,
    true // только одобренные
);
```

## Статусы комментариев

- `pending` - Ожидает модерации
- `approved` - Одобрен
- `rejected` - Отклонен
- `spam` - Спам

## Использование в Blade

```blade
@php
    $commentService = app(\Modules\Comments\Services\CommentService::class);
    $comments = $commentService->getForModel(
        \Modules\News\Models\News::class,
        $news->id,
        true
    );
@endphp

@foreach($comments as $comment)
    <div class="comment">
        <p>{{ $comment->content }}</p>
        <small>{{ $comment->author_name }} - {{ $comment->created_at }}</small>
        
        @if($comment->replies->count())
            @foreach($comment->replies as $reply)
                <div class="reply">
                    {{ $reply->content }}
                </div>
            @endforeach
        @endif
    </div>
@endforeach
```

## API

### Получить комментарии
```
GET /api/comments?model_type=Modules\\News\\Models\\News&model_id=1
```

### Создать комментарий
```
POST /api/comments
{
    "model_type": "Modules\\News\\Models\\News",
    "model_id": 1,
    "content": "Текст комментария",
    "captcha": "captcha_value"
}
```

## Конфигурация

Файл: `modules/Comments/Config/comments.php`

```php
return [
    'auto_approve_users' => true,  // Автоодобрение для авторизованных
    'auto_approve_guests' => false, // Автоодобрение для гостей
    'max_depth' => 3,               // Максимальная глубина вложенности
];
```

## Миграции

Таблицы:
- `comments` - комментарии
- `comment_votes` - голоса (лайки/дизлайки)

## Зависимости

- **Captcha** - для защиты от спама (опционально)

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




