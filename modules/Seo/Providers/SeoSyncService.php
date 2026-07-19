<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Seo\Models\SeoPage;

class SeoSyncService
{
    /** Кеш списка колонок seo_pages */
    protected array $allowedCols = [];

    /* ===================== Публичные методы ===================== */

    /** Апсерт из сущности News */
    public function upsertFromNews(object $news): void
    {
        [$slug, $payload] = $this->mapNews($news);
        $this->upsert($slug, $payload);
    }

    /** Апсерт из сущности Menu/Page */
    public function upsertFromMenuPage(object $page): void
    {
        [$slug, $payload] = $this->mapMenuPage($page);
        $this->upsert($slug, $payload);
    }

    /**
     * Пересинк ровно одной SEO-записи из её источника.
     * $force=true — перезатирает всё, кроме slug; по умолчанию бережный режим (ручные поля не трогаем).
     */
    public function resyncOne(SeoPage $seo, bool $force = false): void
    {
        if (!$seo->source_type || !$seo->source_id) {
            // Источник не известен — просто нормализуем canonical
            $seo->canonical = $this->absolutizeCanonical($seo->canonical, $seo->slug);
            $seo->save();
            return;
        }

        switch ($seo->source_type) {
            case 'news':
                if (class_exists(\Modules\News\Models\News::class)) {
                    $news = \Modules\News\Models\News::find($seo->source_id);
                    if ($news) {
                        [, $payload] = $this->mapNews($news);
                        // slug не меняем из SEO формы
                        $this->upsert($seo->slug, $payload, $force);
                        return;
                    }
                }
                break;

            case 'page':
                if (class_exists(\Modules\Menu\Models\Page::class)) {
                    $page = \Modules\Menu\Models\Page::find($seo->source_id);
                    if ($page) {
                        [, $payload] = $this->mapMenuPage($page);
                        $this->upsert($seo->slug, $payload, $force);
                        return;
                    }
                }
                break;
        }

        // Если источник не найден — просто привели canonical
        $seo->canonical = $this->absolutizeCanonical($seo->canonical, $seo->slug);
        $seo->save();
    }

    /**
     * Заглушка обратной синхронизации — чтобы вызовы из контроллера не падали.
     * Реальную запись обратно в источники (news/page) можно реализовать позже.
     */
    public function pushBackToSource(SeoPage $page, array $changed = []): void
    {
        // no-op
    }

    /* ===================== Мапперы источников ===================== */

    protected function mapNews(object $n): array
    {
        $slugPart = $n->slug ?: Str::slug((string)($n->title ?? 'news')) ?: 'news-' . ($n->id ?? uniqid());
        $slug     = $this->normalizeSlug('/news/' . $slugPart);

        $title = trim((string)($n->seo_title ?? $n->meta_title ?? $n->title ?? ''));
        $desc  = $this->firstFilled($n, ['seo_description','meta_description','description','short','excerpt','content','body','text']);
        $desc  = $desc ? Str::limit(trim(strip_tags((string)$desc)), 255) : null;

        $publishedRaw = $this->firstFilled($n, ['published','is_published','active'], 1);
        $published    = (bool)$publishedRaw;

        $payload = [
            'title'         => $title ?: null,
            'h1'            => $n->title ?? null,
            'description'   => $desc,
            'canonical'     => rtrim(config('app.url'), '/') . $slug,
            'robots_index'  => $published,
            'robots_follow' => true,
            'source_type'   => 'news',
            'source_id'     => $n->id ?? null,
            'sync_hash'     => $this->calcHash([$title,$desc,$published]),
        ];

        return [$slug, $payload];
    }

    protected function mapMenuPage(object $p): array
    {
        $slugRaw  = $p->slug ?? $p->path ?? null;
        $slugPart = $slugRaw ?: Str::slug((string)($p->title ?? 'page')) ?: 'page-' . ($p->id ?? uniqid());
        $slug     = $this->normalizeSlug('/' . $slugPart);

        $title = trim((string)($p->seo_title ?? $p->title ?? ''));
        $desc  = $this->firstFilled($p, ['seo_description','meta_description','description','content','body','text']);
        $desc  = $desc ? Str::limit(trim(strip_tags((string)$desc)), 255) : null;

        $publishedRaw = $this->firstFilled($p, ['published','is_published','active'], 1);
        $published    = (bool)$publishedRaw;

        $payload = [
            'title'         => $title ?: null,
            'h1'            => $p->title ?? null,
            'description'   => $desc,
            'canonical'     => rtrim(config('app.url'), '/') . $slug,
            'robots_index'  => $published,
            'robots_follow' => true,
            'source_type'   => 'page',
            'source_id'     => $p->id ?? null,
            'sync_hash'     => $this->calcHash([$title,$desc,$published]),
        ];

        return [$slug, $payload];
    }

    /* ===================== Внутренний upsert ===================== */

    /**
     * ВАЖНО: при создании мы ВСЕГДА добавляем ['slug' => $slug], чтобы не было 1364.
     * При апдейте: slug не трогаем; ручные поля не перезаписываем (если $force=false).
     */
    protected function upsert(string $slug, array $payload, bool $force = false): void
    {
        $slug = $this->normalizeSlug($slug);

        // фильтруем по существующим колонкам
        $filtered = $this->filterAllowed($payload);

        $model = SeoPage::withTrashed()->where('slug', $slug)->first();

        if ($model) {
            // если удаляли вручную и стоит locked — ничего не делаем
            if ($model->deleted_at && ($model->locked ?? false)) {
                return;
            }

            if (method_exists($model, 'trashed') && $model->trashed()) {
                $model->restore();
            }

            // не трогаем slug
            unset($filtered['slug']);

            // не перезатираем ручные поля, если не force
            if (!$force) {
                $manual = is_array($model->manual_fields ?? null) ? $model->manual_fields : [];
                foreach (array_keys($filtered) as $k) {
                    if (array_key_exists($k, $manual) && $model->{$k} !== null && $model->{$k} !== '') {
                        unset($filtered[$k]);
                    }
                }
            }

            if (!empty($filtered)) {
                $model->fill($filtered)->save();
            }
        } else {
            // ←←← фикс: всегда мерджим slug
            $data = array_merge($filtered, ['slug' => $slug]);
            $data = $this->filterAllowed($data); // на случай если slug внезапно отфильтровали (не должно)
            SeoPage::create($data);
        }
    }

    /* ===================== Утилиты ===================== */

    protected function filterAllowed(array $data): array
    {
        if (!$this->allowedCols) {
            try {
                $cols = Schema::getColumnListing('seo_pages');
            } catch (\Throwable $e) {
                $cols = [];
            }
            $this->allowedCols = array_flip($cols);
        }
        return array_intersect_key($data, $this->allowedCols);
    }

    protected function normalizeSlug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') return '/';

        if (filter_var($slug, FILTER_VALIDATE_URL)) {
            $parts = parse_url($slug);
            $path  = $parts['path'] ?? '/';
            $slug  = $path . (!empty($parts['query']) ? '?' . $parts['query'] : '');
        }

        $slug = '/' . ltrim($slug, '/');
        if (strlen($slug) > 1) $slug = rtrim($slug, '/');
        return $slug;
    }

    protected function absolutizeCanonical(?string $canonical, string $slug): ?string
    {
        if (!$canonical) return null;
        if (Str::startsWith($canonical, ['http://', 'https://'])) return $canonical;
        return rtrim(config('app.url'), '/') . $slug;
    }

    protected function firstFilled(object|array $row, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            $val = data_get($row, $k);
            if (!is_null($val) && $val !== '') return $val;
        }
        return $default;
    }

    protected function calcHash(array $parts): string
    {
        return md5(json_encode($parts, JSON_UNESCAPED_UNICODE));
    }
}
