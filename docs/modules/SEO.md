# 🔍 Модуль SEO

**Версия:** 1.0.0  
**Приоритет:** 120

---

## Описание

SEO модуль, оптимизированный для России и СНГ (Yandex-first). Управление мета-тегами, sitemap, robots.txt и другими SEO настройками.

## Основные возможности

- ✅ Управление мета-тегами для страниц
- ✅ Автоматическая генерация sitemap.xml
- ✅ Управление robots.txt
- ✅ Редиректы (301, 302)
- ✅ SEO-анализ страниц
- ✅ Интеграция с Яндекс.Вебмастер
- ✅ Управление каноническими ссылками
- ✅ Open Graph и Twitter Cards

## Структура

```
modules/Seo/
├── Controllers/
│   ├── Admin/
│   │   ├── SeoPageController.php      # Управление SEO страницами
│   │   ├── RedirectController.php     # Управление редиректами
│   │   ├── SitemapController.php      # Управление sitemap
│   │   └── RobotsController.php      # Управление robots.txt
│   └── Frontend/
│       └── SeoController.php         # Публичная часть
├── Models/
│   ├── SeoPage.php                   # SEO настройки страницы
│   └── RedirectRule.php              # Правила редиректов
├── Services/
│   ├── SitemapService.php            # Генерация sitemap
│   ├── RobotsService.php             # Генерация robots.txt
│   └── SeoAnalyzerService.php        # SEO анализ
└── Jobs/
    └── GenerateSitemapJob.php       # Фоновая генерация sitemap
```

## Использование

### Создание SEO страницы

```php
use Modules\Seo\Models\SeoPage;

$seoPage = SeoPage::create([
    'url' => '/news/article',
    'title' => 'Заголовок страницы',
    'description' => 'Описание страницы',
    'keywords' => 'ключевые, слова',
    'canonical' => 'https://example.com/news/article',
    'og_title' => 'OG заголовок',
    'og_description' => 'OG описание',
    'og_image' => '/images/og-image.jpg',
]);
```

### Создание редиректа

```php
use Modules\Seo\Models\RedirectRule;

RedirectRule::create([
    'from_url' => '/old-page',
    'to_url' => '/new-page',
    'status_code' => 301, // 301 или 302
    'is_active' => true,
]);
```

### Генерация sitemap

```php
use Modules\Seo\Services\SitemapService;

$sitemapService = app(SitemapService::class);
$sitemapService->generate();
```

## Автоматическая генерация sitemap

Sitemap автоматически генерируется каждый день в 3:00 через задачу в расписании:

```php
// В app/Console/Kernel.php или bootstrap/app.php
$schedule->job(new \Modules\Seo\Jobs\GenerateSitemapJob())
    ->dailyAt('03:00');
```

## Маршруты

### Админка
- `GET /admin/seo/pages` - список SEO страниц
- `GET /admin/seo/pages/create` - создание
- `POST /admin/seo/pages` - сохранение
- `GET /admin/seo/redirects` - список редиректов
- `GET /admin/seo/sitemap` - управление sitemap
- `GET /admin/seo/robots` - управление robots.txt

### Публичные
- `GET /sitemap.xml` - sitemap
- `GET /robots.txt` - robots.txt

## Использование в Blade

```blade
@php
    $seoPage = \Modules\Seo\Models\SeoPage::where('url', request()->path())->first();
@endphp

@if($seoPage)
    <title>{{ $seoPage->title }}</title>
    <meta name="description" content="{{ $seoPage->description }}">
    <meta name="keywords" content="{{ $seoPage->keywords }}">
    
    @if($seoPage->canonical)
        <link rel="canonical" href="{{ $seoPage->canonical }}">
    @endif
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $seoPage->og_title ?? $seoPage->title }}">
    <meta property="og:description" content="{{ $seoPage->og_description ?? $seoPage->description }}">
    @if($seoPage->og_image)
        <meta property="og:image" content="{{ url($seoPage->og_image) }}">
    @endif
@endif
```

## Миграции

Таблицы:
- `seo_pages` - SEO настройки страниц
- `redirect_rules` - правила редиректов

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




