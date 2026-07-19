# 🖼️ Модуль Slideshow (Слайдшоу)

**Версия:** 1.2.0  
**Приоритет:** 9

---

## Описание

Модуль управления слайдшоу с поддержкой изображений и видео, настраиваемыми позициями и эффектами переходов.

## Основные возможности

- ✅ Создание слайдшоу для разных позиций (top/bottom)
- ✅ Поддержка изображений и видео
- ✅ Настраиваемые эффекты переходов
- ✅ Автоплей с настраиваемой задержкой
- ✅ Пагинация и навигация
- ✅ Настраиваемые позиции текста
- ✅ Цвета текста и фона
- ✅ Связь с новостями
- ✅ SEO оптимизация (alt-текст)

## Структура

```
modules/Slideshow/
├── Controllers/
│   ├── Admin/SlideshowController.php  # Админка
│   └── PublicController.php            # Публичная часть
├── Models/
│   ├── Slideshow.php                   # Модель слайдшоу
│   └── SlideshowItem.php              # Модель слайда
└── Views/
    ├── admin/                          # Админ-панель
    └── public/                         # Публичная часть
```

## Использование

### Создание слайдшоу

```php
use Modules\Slideshow\Models\Slideshow;

$slideshow = Slideshow::create([
    'title' => 'Главное слайдшоу',
    'position' => 'top',
    'published' => true,
    'autoplay_delay' => 5000,
    'transition_effect' => 'slide',
    'show_pagination' => true,
    'show_navigation' => true,
]);

// Добавление слайда
$slideshow->items()->create([
    'file_path' => 'slides/slide1.jpg',
    'media_type' => 'image',
    'caption' => 'Заголовок слайда',
    'link' => '/page',
    'order' => 1,
    'text_position' => 'bottom-center',
    'text_color' => '#ffffff',
    'background_color' => '#000000',
]);
```

### Получение слайдшоу

```php
// По позиции
$slideshow = Slideshow::where('position', 'top')
    ->where('published', true)
    ->with('items')
    ->first();
```

## Позиции

- `top` - Верх страницы (header)
- `bottom` - Низ страницы (footer)

## Эффекты переходов

- `slide` - Слайд (по умолчанию)
- `fade` - Плавное затухание
- Другие эффекты Swiper.js

## Использование в Blade

```blade
@php
    $slideshow = \Modules\Slideshow\Models\Slideshow::where('position', 'top')
        ->where('published', true)
        ->with('items')
        ->first();
@endphp

@if($slideshow)
    @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
@endif
```

## Миграции

Таблицы:
- `slideshows` - слайдшоу
- `slideshow_items` - слайды

---

**Версия:** 1.2.0  
**Последнее обновление:** 2025-01-28




