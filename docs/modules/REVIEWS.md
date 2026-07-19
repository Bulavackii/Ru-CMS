# 📝 Модуль Reviews (Отзывы)

**Версия:** 1.0.0  
**Приоритет:** 50

---

## Описание

Система отзывов и оценок с модерацией. Позволяет пользователям оставлять отзывы о товарах, услугах и контенте.

## Основные возможности

- ✅ Отзывы с оценками (звезды)
- ✅ Модерация отзывов
- ✅ Полиморфные отзывы (для любых моделей)
- ✅ Ответы на отзывы
- ✅ Фильтрация по оценке
- ✅ Средняя оценка

## Структура

```
modules/Reviews/
├── Controllers/
│   ├── Admin/ReviewController.php
│   └── Frontend/ReviewController.php
├── Models/
│   └── Review.php                   # Модель отзыва
├── Services/
│   └── ReviewService.php           # Сервис управления отзывами
└── Views/
    ├── admin/                        # Админ-панель
    └── frontend/                     # Публичная часть
```

## Использование

### Добавление отзывов к модели

```php
use Modules\Reviews\Models\Review;

// В модели News или Product
class News extends Model
{
    public function reviews()
    {
        return $this->morphMany(Review::class, 'model');
    }
    
    public function approvedReviews()
    {
        return $this->morphMany(Review::class, 'model')
            ->where('status', 'approved');
    }
    
    public function averageRating()
    {
        return $this->approvedReviews()->avg('rating');
    }
}
```

### Создание отзыва

```php
use Modules\Reviews\Services\ReviewService;

$reviewService = app(ReviewService::class);

$review = $reviewService->create([
    'model_type' => News::class,
    'model_id' => $news->id,
    'user_id' => auth()->id(),
    'rating' => 5, // от 1 до 5
    'comment' => 'Отличный товар!',
]);
```

### Получение отзывов

```php
$reviews = $news->approvedReviews()
    ->with('user')
    ->orderByDesc('created_at')
    ->paginate(10);
```

## Статусы отзывов

- `pending` - Ожидает модерации
- `approved` - Одобрен
- `rejected` - Отклонен

## Использование в Blade

```blade
@php
    $reviews = $news->approvedReviews()->with('user')->get();
    $averageRating = $news->averageRating();
@endphp

<div class="reviews">
    <div class="rating">
        Средняя оценка: {{ number_format($averageRating, 1) }} / 5
    </div>
    
    @foreach($reviews as $review)
        <div class="review">
            <div class="rating">
                @for($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= $review->rating ? 'filled' : '' }}">★</span>
                @endfor
            </div>
            <p>{{ $review->comment }}</p>
            <small>{{ $review->user->name }} - {{ $review->created_at }}</small>
        </div>
    @endforeach
</div>
```

## Миграции

Таблицы:
- `reviews` - отзывы

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




