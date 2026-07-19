<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Seo\Models\SeoPage;

class SeoSyncService
{
    /** @var array<string, true> */
    protected array $columns;

    public function __construct()
    {
        $this->columns = $this->getColumns();
    }

    /* ========================= Public API ========================= */

    /**
     * Апсерт из модуля Новостей.
     * @param  \Modules\News\Models\News|int $news   объект или id
     * @param  bool $force  если true — можно перезаписать ручные поля
     */
    public function upsertFromNews($news, bool $force = false): SeoPage
    {
        if (!class_exists(\Modules\News\Models\News::class)) {
            throw new \RuntimeException('News module not installed');
        }

        $n = is_object($news) ? $news : \Modules\News\Models\News::findOrFail((int)$news);

        $slugPart = $n->slug ?: Str::slug((string)($n->title ?? 'news')) ?: ('news-' . $n->id);
        $slug     = $this->normalizeSlug('/news/' . $slugPart);

        $title = trim((string)($n->seo_title ?? $n->meta_title ?? $n->title ?? ''));
        $desc  = $this->firstFilled($n, ['seo_description', 'meta_description', 'description', 'short', 'excerpt', 'content', 'body', 'text']);
        $desc  = $this->truncateDesc($desc);

        $kw    = $this->firstFilled($n, ['seo_keywords', 'meta_keywords', 'keywords', 'tags']);
        $kw    = $this->sanitizeKeywords($kw);

        $publishedRaw = $this->firstFilled($n, ['published', 'is_published', 'active'], 1);
        $published    = (bool)$publishedRaw;

        $payload = [
            'title'         => $title ?: null,
            'h1'            => $n->title ?? null,
            'description'   => $desc,
            'canonical'     => $this->absoluteUrl($slug),
            'robots_index'  => $published,
            'robots_follow' => true,
        ];
        if (isset($this->columns['keywords'])) {
            $payload['keywords'] = $kw;
        }

        $source = [
            'type' => 'news',
            'id'   => $n->id,
            'hash' => md5('news:' . $n->id . ';' . ($n->updated_at ?? '')),
        ];

        return $this->upsertByPayload($slug, $payload, $source, $force);
    }

    /**
     * Апсерт из Menu/Page (если модуль установлен).
     * @param  \Modules\Menu\Models\Page|int $page
     */
    public function upsertFromMenuPage($page, bool $force = false): SeoPage
    {
        if (!class_exists(\Modules\Menu\Models\Page::class)) {
            throw new \RuntimeException('Menu module not installed');
        }

        $p = is_object($page) ? $page : \Modules\Menu\Models\Page::findOrFail((int)$page);

        $slugRaw  = $p->slug ?? $p->path ?? null;
        $slugPart = $slugRaw ?: Str::slug((string)($p->title ?? 'page')) ?: ('page-' . $p->id);
        $slug     = $this->normalizeSlug('/' . $slugPart);

        $title = trim((string)($p->seo_title ?? $p->title ?? ''));
        $desc  = $this->firstFilled($p, ['seo_description', 'description', 'content', 'body', 'text']);
        $desc  = $this->truncateDesc($desc);

        $kw    = $this->firstFilled($p, ['seo_keywords', 'meta_keywords', 'keywords', 'tags']);
        $kw    = $this->sanitizeKeywords($kw);

        $publishedRaw = $this->firstFilled($p, ['published', 'is_published', 'active'], 1);
        $published    = (bool)$publishedRaw;

        $payload = [
            'title'         => $title ?: null,
            'h1'            => $p->title ?? null,
            'description'   => $desc,
            'canonical'     => $this->absoluteUrl($slug),
            'robots_index'  => $published,
            'robots_follow' => true,
        ];
        if (isset($this->columns['keywords'])) {
            $payload['keywords'] = $kw;
        }

        $source = [
            'type' => 'page',
            'id'   => $p->id,
            'hash' => md5('page:' . $p->id . ';' . ($p->updated_at ?? '')), // ✅ фикс: убрана лишняя скобка
        ];

        return $this->upsertByPayload($slug, $payload, $source, $force);
    }

    /**
     * Пересинхронизация одной записи SEO (по source_type/source_id, если есть).
     */
    public function resyncOne(SeoPage $page, bool $force = false): SeoPage
    {
        $type = $page->source_type ?? ($page->entity_type ?? null);
        $id   = $page->source_id   ?? ($page->entity_id   ?? null);

        if ($type === 'news' && class_exists(\Modules\News\Models\News::class) && $id) {
            $news = \Modules\News\Models\News::find($id);
            if ($news) return $this->upsertFromNews($news, $force);
        }

        if ($type === 'page' && class_exists(\Modules\Menu\Models\Page::class) && $id) {
            $p = \Modules\Menu\Models\Page::find($id);
            if ($p) return $this->upsertFromMenuPage($p, $force);
        }

        // Если нет источника — хотя бы нормализуем canonical и сохраним.
        return $this->upsertByPayload(
            $page->slug,
            ['canonical' => $this->absoluteUrl($page->slug)],
            ['type' => null, 'id' => null],
            $force
        );
    }

    /**
     * 🔁 Жёсткий push-back: применить правки из SEO к источнику.
     * H1 из SEO всегда записываем в title новости; meta — мягко, если заданы.
     */
    public function pushBackFromSeo(SeoPage $page): void
    {
        $type = (string)($page->source_type ?? ($page->entity_type ?? ''));
        $id   = (int)($page->source_id   ?? ($page->entity_id   ?? 0));

        if ($type !== 'news' || !class_exists(\Modules\News\Models\News::class)) {
            return;
        }

        $news = $id ? \Modules\News\Models\News::find($id) : null;
        if (!$news) {
            $slugPart = trim((string)Str::after((string)$page->slug, '/news/'), '/');
            if ($slugPart !== '') {
                $news = \Modules\News\Models\News::where('slug', $slugPart)->first();
            }
        }
        if (!$news) return;

        $changed = false;

        $h1 = trim((string)($page->h1 ?? ''));
        if ($h1 !== '' && $news->title !== $h1) {
            $news->title = $h1;
            $changed = true;
        }
        if (trim((string)$page->title) !== '' && $news->meta_title !== $page->title) {
            $news->meta_title = (string)$page->title;
            $changed = true;
        }
        if (trim((string)$page->description) !== '' && $news->meta_description !== $page->description) {
            $news->meta_description = (string)$page->description;
            $changed = true;
        }
        if (isset($page->keywords) && trim((string)$page->keywords) !== '') {
            $kw = $this->sanitizeKeywords($page->keywords);
            if ($news->meta_keywords !== $kw) {
                $news->meta_keywords = $kw;
                $changed = true;
            }
        }

        if ($changed) {
            try {
                $news->save();
            } catch (\Throwable $e) {
                Log::debug('SEO push-back save failed', ['id' => $news->id ?? null, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Push-back через маппинг (оставлено для совместимости).
     */
    public function pushBackToSource(SeoPage $page): void
    {
        if (!config('seo.features.push_back_to_sources', true)) {
            return;
        }

        $type = $page->source_type ?? ($page->entity_type ?? null);
        $id   = $page->source_id   ?? ($page->entity_id   ?? null);
        if (!$type || !$id) {
            return;
        }

        $mapAll = (array) config('seo.sync.fields_map', []);
        $map    = (array) ($mapAll[$type] ?? []);

        $allowChangeTitle = (bool) config('seo.features.push_back_change_title', false);

        $model = null;
        if ($type === 'news' && class_exists(\Modules\News\Models\News::class)) {
            $model = \Modules\News\Models\News::find($id);
        } elseif ($type === 'page' && class_exists(\Modules\Menu\Models\Page::class)) {
            $model = \Modules\Menu\Models\Page::find($id);
        }
        if (!$model) return;

        $table = $model->getTable();

        $keys = ['title', 'description', 'h1', 'keywords'];
        foreach ($keys as $key) {
            $dst = $map[$key] ?? null;
            if ($dst === null || $dst === '') continue;

            if ($key === 'h1' && !$allowChangeTitle && $dst === 'title') {
                continue;
            }

            $val = $page->{$key} ?? null;
            if ($key === 'keywords') {
                $val = $this->sanitizeKeywords($val);
            }

            try {
                if (!Schema::hasColumn($table, $dst)) continue;
            } catch (\Throwable $e) {
                continue;
            }

            if ($model->{$dst} !== $val) {
                $model->{$dst} = $val;
            }
        }

        try {
            $model->save();
        } catch (\Throwable $e) {
            Log::debug('SEO push-back failed: ' . $e->getMessage());
        }
    }

    /* =================== Core upsert logic =================== */

    /**
     * Базовый апсерт по slug с защитой ручных правок.
     */
    public function upsertByPayload(string $slug, array $payload, array $source, bool $force = false): SeoPage
    {
        $slug = $this->normalizeSlug($slug);

        $filtered = array_intersect_key($payload, $this->columns);
        $model = SeoPage::withTrashed()->where('slug', $slug)->first();

        if (!$model) {
            $model = new SeoPage();

            $model->slug = $slug;

            if (isset($this->columns['source_type']) && array_key_exists('type', $source)) {
                $model->source_type = $source['type'];
            }
            if (isset($this->columns['source_id']) && array_key_exists('id', $source)) {
                $model->source_id = (int) $source['id'];
            }
            if (isset($this->columns['sync_hash']) && array_key_exists('hash', $source)) {
                $model->sync_hash = (string) $source['hash'];
            }

            $model->fill($filtered);
            $model->save();

            return $model;
        }

        if (method_exists($model, 'trashed') && $model->trashed()) {
            $model->restore();
        }

        unset($filtered['slug']);

        $manual = is_array($model->manual_fields ?? null) ? $model->manual_fields : [];
        if (!$force && $manual) {
            foreach (array_keys($filtered) as $k) {
                if (array_key_exists($k, $manual) && $model->{$k}) {
                    unset($filtered[$k]);
                }
            }
        }

        if (isset($this->columns['source_type']) && array_key_exists('type', $source)) $filtered['source_type'] = $source['type'];
        if (isset($this->columns['source_id'])   && array_key_exists('id', $source))   $filtered['source_id']   = (int)$source['id'];
        if (isset($this->columns['sync_hash'])   && array_key_exists('hash', $source)) $filtered['sync_hash']   = (string)$source['hash'];

        if (!empty($filtered)) {
            $model->fill($filtered)->save();
        }

        return $model;
    }

    /* ========================= Helpers ========================= */

    protected function getColumns(): array
    {
        try {
            $cols = Schema::getColumnListing('seo_pages');
        } catch (\Throwable $e) {
            $cols = [];
        }
        return array_flip($cols);
    }

    protected function truncateDesc($value): ?string
    {
        if ($value === null || $value === '') return null;
        return Str::limit(trim(strip_tags((string)$value)), 255);
    }

    protected function sanitizeKeywords($value): ?string
    {
        if ($value === null || $value === '') return null;
        $s = trim((string)$value);
        $s = preg_replace('/\s*,\s*/u', ', ', $s);
        $s = preg_replace('/\s{2,}/u', ' ', $s);
        return Str::limit($s, 255, '');
    }

    protected function firstFilled(object|array $row, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            $val = data_get($row, $k);
            if (!is_null($val) && $val !== '') return $val;
        }
        return $default;
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

    protected function absoluteUrl(string $slug): string
    {
        $base = rtrim((string)config('app.url'), '/');
        if ($base === '') $base = rtrim(request()->getSchemeAndHttpHost(), '/');
        return $base . $this->normalizeSlug($slug);
    }
}
