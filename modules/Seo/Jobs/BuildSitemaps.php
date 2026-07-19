<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Services\SitemapBuilder;
use Modules\Seo\Services\NewsSitemapBuilder;
use Modules\Seo\Services\ImagesSitemapBuilder;

class BuildSitemaps implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<int, array{loc:string, title:string, publication_date:string|\DateTimeInterface, publication_name?:string, genres?:string}> $news
     * @param array<int, array{loc:string, images: array<int, array{url:string, caption?:string, title?:string}>}> $images
     * @param string|null $outputDir Куда класть файлы (если null — возьмётся из config('seo.sitemaps.output_dir'))
     */
    public function __construct(
        public array $news = [],
        public array $images = [],
        public ?string $outputDir = null
    ) {}

    /** Защита от одновременных сборок */
    public int $uniqueFor = 600; // 10 минут

    /** Повторы и бэкофф на случай сбоев диска/IO */
    public int $tries = 3;
    public function backoff(): array { return [30, 120, 300]; }

    /** Уникальность — по каталогу вывода */
    public function uniqueId(): string
    {
        $dir = rtrim((string)($this->outputDir ?? config('seo.sitemaps.output_dir', public_path('sitemaps'))), '/');
        return 'sitemap:build:' . md5($dir);
    }

    public function handle(): void
    {
        $dir = rtrim((string)($this->outputDir ?? config('seo.sitemaps.output_dir', public_path('sitemaps'))), '/');

        // Готовим каталог
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            Log::error('Sitemap build failed: output dir is not writable', ['dir' => $dir]);
            return;
        }

        $meta = [
            'built_at' => now()->toIso8601String(),
            'dir'      => $dir,
            'base'     => rtrim((string)config('app.url'), '/'),
            'parts'    => [],
            'errors'   => [],
        ];

        /* ---------- Базовый sitemap ---------- */
        try {
            $base = (new SitemapBuilder())->build($dir);
            // ['sitemap'=>path,'count'=>int,'parts'=>?array,'indexed'=>bool]
            $meta['parts']['base'] = [
                'path'   => $base['sitemap'] ?? null,
                'count'  => $base['count'] ?? 0,
                'indexed'=> (bool)($base['indexed'] ?? false),
                'chunks' => array_values($base['parts'] ?? []),
            ];
            Log::info('Sitemap built', ['dir' => $dir, 'count' => $base['count'] ?? null]);
        } catch (\Throwable $e) {
            $meta['errors'][] = 'base: '.$e->getMessage();
            Log::error('Sitemap build failed', ['error' => $e->getMessage()]);
        }

        /* ---------- News sitemap (если включено) ---------- */
        if (config('seo.features.news_sitemap')) {
            try {
                $newsItems = $this->news;
                if (empty($newsItems)) {
                    $newsItems = $this->collectNewsItemsFor48h(); // мягкий автосбор
                }

                if (!empty($newsItems)) {
                    $path = (new NewsSitemapBuilder())->build($newsItems, $dir);
                    $meta['parts']['news'] = [
                        'path'  => $path,
                        'count' => count($newsItems),
                    ];
                    Log::info('News sitemap built', ['dir' => $dir, 'count' => count($newsItems)]);
                } else {
                    Log::info('News sitemap skipped: no items');
                }
            } catch (\Throwable $e) {
                $meta['errors'][] = 'news: '.$e->getMessage();
                Log::error('News sitemap build failed', ['error' => $e->getMessage()]);
            }
        }

        /* ---------- Images sitemap (если включено и есть данные) ---------- */
        if (config('seo.features.images_sitemap')) {
            try {
                if (!empty($this->images)) {
                    $path = (new ImagesSitemapBuilder())->build($this->images, $dir);
                    $meta['parts']['images'] = [
                        'path'  => $path,
                        'pages' => count($this->images),
                    ];
                    Log::info('Images sitemap built', ['dir' => $dir, 'pages' => count($this->images)]);
                } else {
                    Log::info('Images sitemap skipped: no pages passed');
                }
            } catch (\Throwable $e) {
                $meta['errors'][] = 'images: '.$e->getMessage();
                Log::error('Images sitemap build failed', ['error' => $e->getMessage()]);
            }
        }

        /* ---------- Кэш и JSON-снапшот для админки ---------- */
        try {
            Cache::put('seo:sitemaps:last', $meta, now()->addHours(6));

            $snapDir = storage_path('app/seo/sitemaps');
            if (!is_dir($snapDir)) @mkdir($snapDir, 0775, true);
            $file = $snapDir.'/last.json';
            File::put($file, json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            @chmod($file, 0644);
        } catch (\Throwable $e) {
            Log::warning('Sitemap meta persist failed', ['error' => $e->getMessage()]);
        }
    }

    /* ================= helpers ================= */

    /**
     * Мягкий автосбор новостей за последние 48 часов, если список не передан.
     * Работает только при наличии модуля News. Ошибки — в лог, без фатала.
     * Возвращает массив элементов в формате NewsSitemapBuilder::build().
     */
    protected function collectNewsItemsFor48h(): array
    {
        if (!class_exists(\Modules\News\Models\News::class)) {
            return [];
        }

        try {
            $now    = now();
            $cutoff = $now->copy()->subHours(48);

            $q = \Modules\News\Models\News::query();

            // Пытаемся фильтровать по «дате публикации», если такая колонка есть
            $dateCol = null;
            foreach (['published_at','publish_at','created_at','updated_at'] as $col) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('news', $col)) {
                    $dateCol = $col; break;
                }
            }
            if ($dateCol) {
                $q->where($dateCol, '>=', $cutoff);
                $q->orderByDesc($dateCol);
            } else {
                $q->orderByDesc('id');
            }

            // Фильтр по «опубликовано», если колонка есть
            foreach (['published','is_published','active'] as $flag) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('news', $flag)) {
                    $q->where($flag, 1);
                    break;
                }
            }

            $rows = $q->limit(1000)->get();

            $base = rtrim((string)config('app.url'), '/');
            if ($base === '') $base = rtrim(request()->getSchemeAndHttpHost(), '/');

            $items = [];
            foreach ($rows as $n) {
                $slug = $this->newsSlug($n);
                if (!$slug) continue;

                $items[] = [
                    'loc'              => $base . $slug,
                    'title'            => (string)($n->title ?? ''),
                    'publication_date' => (string)($n->{$dateCol ?? 'created_at'}),
                    'publication_name' => config('app.name', 'Site'),
                ];
            }
            return $items;
        } catch (\Throwable $e) {
            Log::debug('collectNewsItemsFor48h failed: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Пытаемся построить slug новости: /news/{slug|title|id}
     */
    protected function newsSlug(object $n): ?string
    {
        $slugPart = $n->slug ?? null;
        if (!$slugPart && isset($n->title) && $n->title !== '') {
            $slugPart = \Illuminate\Support\Str::slug((string)$n->title) ?: null;
        }
        if (!$slugPart) {
            $slugPart = 'news-' . ($n->id ?? '0');
        }

        $slug = '/news/' . ltrim((string)$slugPart, '/');
        // убираем хвостовой слэш
        if (strlen($slug) > 1) $slug = rtrim($slug, '/');
        return $slug;
    }
}
