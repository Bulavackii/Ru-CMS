<?php

namespace Modules\Seo\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Http\Requests\SeoPageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\SeoPageCreated;
use App\Events\SeoPageUpdated;
use App\Events\SeoPageDeleted;
use Modules\Seo\Models\SeoPage;

class PagesController extends Controller
{
    public function index(Request $r)
    {
        $q       = trim((string)$r->get('q', ''));
        $perPage = (int) min(max($r->integer('per_page', 10), 1), 100);

        $query = SeoPage::query();

        // Поиск (используем scope)
        if ($q !== '') {
            $query->search($q);
        }

        // Фильтр по source_type
        if ($r->filled('source_type')) {
            $query->bySourceType($r->input('source_type'));
        }

        // Фильтр по locked
        if ($r->filled('locked')) {
            $query->locked($r->boolean('locked'));
        }

        // Фильтры index/follow. Раньше они были только клиентскими: JS прятал
        // строки ТЕКУЩЕЙ страницы выдачи, поэтому «noindex» показывал лишь то,
        // что случайно попало в первую десятку.
        foreach (['index' => 'robots_index', 'follow' => 'robots_follow'] as $param => $column) {
            $value = $r->input($param, $r->input($column));
            if ($value === '0' || $value === '1') {
                $query->where($column, $value === '1');
            }
        }

        // Фильтры по наличию мета-данных (тоже были клиентскими)
        foreach (array_filter(explode(',', (string) $r->input('meta'))) as $flag) {
            match ($flag) {
                'canonical' => $query->whereNotNull('canonical')->where('canonical', '!=', ''),
                'og'        => $query->whereNotNull('og'),
                'jsonld'    => $query->whereNotNull('jsonld'),
                default     => null,
            };
        }

        // Сортировка
        $sortBy = $r->input('sort_by', 'updated_at');
        $sortOrder = $r->input('sort_order', 'desc');
        
        $allowedSortFields = ['id', 'slug', 'title', 'h1', 'created_at', 'updated_at', 'source_type'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('updated_at');
        }

        $items = $query->paginate($perPage)->withQueryString();

        // Сводка по всей таблице, а не по текущей странице выдачи
        $stats = [
            'total'     => SeoPage::count(),
            'noindex'   => SeoPage::where('robots_index', false)->count(),
            'locked'    => SeoPage::where('locked', true)->count(),
            'problems'  => SeoPage::query()
                ->where(function ($qq) {
                    $qq->whereNull('title')->orWhere('title', '')
                       ->orWhereNull('description')->orWhere('description', '');
                })
                ->count(),
        ];

        return view('seo::admin.index', compact('items', 'q', 'perPage', 'stats'));
    }

    public function create()
    {
        return view('seo::admin.create');
    }

    public function store(SeoPageRequest $r)
    {
        $data = $this->prepareData($r->validated(), false);
        $data['slug'] = $this->normalizeSlug($data['slug']);
        $data['canonical'] = $this->absolutizeCanonical($data['canonical'] ?? null, $data['slug']);
        $data['created_by'] = auth()->id();

        $data = $this->filterColumns($data);

        $page = new SeoPage();
        $page->slug = $data['slug'];
        $page->fill($data);
        $page->save();

        if (method_exists($page, 'markManual')) {
            $page->markManual(array_keys($data));
            $page->save();
        }

        // Очистка кэша
        Cache::forget('seo_page_' . md5($page->slug));

        // Событие
        SeoPageCreated::dispatch($page);

        // безопасный пуш-бэк в источник (если включено)
        $this->pushBackToSourceSafe($page);

        $this->rebuildSitemapsSafe();
        $this->pushIndexNowSafe([$page->canonical ?: $page->slug]);

        return redirect()->route('seo.pages.index')->with('status', 'Создано');
    }

    public function edit($id)
    {
        $item = SeoPage::query()->whereKey((int)$id)->first();

        if (!$item && is_string($id)) {
            $slug = $this->normalizeSlug($id);
            $item = SeoPage::query()->where('slug', $slug)->first();
        }

        if (!$item) {
            return redirect()->route('seo.pages.index')
                ->with('status', "Запись не найдена (id/slug: {$id}).");
        }

        return view('seo::admin.edit', compact('item'));
    }

    public function update(SeoPageRequest $r, $id)
    {
        $item = SeoPage::findOrFail($id);
        $data = $this->prepareData($r->validated(), true);

        // slug
        if (array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== '') {
            $data['slug'] = $this->normalizeSlug($data['slug']);
            $slugForCanonical = $data['slug'];
        } else {
            unset($data['slug']);
            $slugForCanonical = $item->slug;
        }

        // canonical
        if (array_key_exists('canonical', $data)) {
            $data['canonical'] = $this->absolutizeCanonical($data['canonical'] ?? null, $slugForCanonical);
        }

        $data = $this->filterColumns($data);

        // какие поля реально изменились
        $changed = [];
        foreach ($data as $k => $v) {
            if ($item->{$k} !== $v) $changed[] = $k;
        }

        // применяем
        if (isset($data['slug'])) {
            $item->slug = $data['slug'];
            unset($data['slug']);
        }
        $item->fill($data);

        if ($changed && method_exists($item, 'markManual')) {
            $item->markManual($changed);
        }

        $data['updated_by'] = auth()->id();
        $item->save();

        // Очистка кэша
        Cache::forget('seo_page_' . md5($item->slug));

        // Событие
        SeoPageUpdated::dispatch($item);

        // --- НОВОЕ: надёжный пуш-бэк в источник (без ожидания сервиса) ---
        // Если источник — новость, и поля реально менялись, аккуратно обновим News.
        try {
            if (($item->source_type ?? null) === 'news' && ($item->source_id ?? null)) {
                if (class_exists(\Modules\News\Models\News::class)) {
                    $news = \Modules\News\Models\News::find((int)$item->source_id);
                    if ($news) {
                        $didChange = false;

                        if (in_array('h1', $changed, true)) {
                            // используем meta_header как H1 (на фронте именно его обычно выводят)
                            if (property_exists($news, 'meta_header') || isset($news->meta_header)) {
                                $news->meta_header = $item->h1;
                                $didChange = true;
                            }
                        }
                        if (in_array('title', $changed, true)) {
                            if (property_exists($news, 'meta_title') || isset($news->meta_title)) {
                                $news->meta_title = $item->title;
                                $didChange = true;
                            }
                        }
                        if (in_array('description', $changed, true)) {
                            if (property_exists($news, 'meta_description') || isset($news->meta_description)) {
                                $news->meta_description = $item->description;
                                $didChange = true;
                            }
                        }
                        if (in_array('keywords', $changed, true)) {
                            if (property_exists($news, 'meta_keywords') || isset($news->meta_keywords)) {
                                $news->meta_keywords = $item->keywords;
                                $didChange = true;
                            }
                        }

                        if ($didChange) {
                            $news->save();
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // тихо логируем, но не ломаем сохранение SEO
            Log::debug('SEO push-back (inline) skipped', ['seo_id' => $item->id, 'error' => $e->getMessage()]);
        }
        // --- /НОВОЕ ---

        // безопасный пуш-бэк через сервис (если включён — пусть тоже работает)
        $this->pushBackToSourceSafe($item);

        $this->rebuildSitemapsSafe();
        $this->pushIndexNowSafe([$item->canonical ?: $item->slug]);

        return redirect()->route('seo.pages.index')->with('status', 'Сохранено');
    }

    public function destroy($id)
    {
        $page = SeoPage::findOrFail($id);
        SeoPageDeleted::dispatch($page);
        $page->delete();
        
        // Очистка кэша
        Cache::forget('seo_page_' . md5($page->slug));
        
        $this->rebuildSitemapsSafe();
        return back()->with('status', 'Удалено');
    }

    /**
     * 🔒 Заблокировать страницу от перезаписи из источников (locked = true)
     */
    public function lock($id)
    {
        $item = SeoPage::findOrFail($id);
        $item->locked = true;
        $item->updated_by = auth()->id();
        $item->save();

        Cache::forget('seo_page_' . md5($item->slug));
        SeoPageUpdated::dispatch($item);

        return back()->with('status', 'Поля заблокированы: запись не будет перезаписываться из источников.');
    }

    /**
     * 🔓 Разблокировать страницу (locked = false)
     */
    public function unlock($id)
    {
        $item = SeoPage::findOrFail($id);
        $item->locked = false;
        $item->updated_by = auth()->id();
        $item->save();

        Cache::forget('seo_page_' . md5($item->slug));
        SeoPageUpdated::dispatch($item);

        return back()->with('status', 'Поля разблокированы: снова можно синхронизировать из источников.');
    }

    /**
     * 📦 Массовые действия
     */
    public function bulkAction(Request $r)
    {
        $r->validate([
            'action'     => 'required|in:delete,lock,unlock,sync,index,noindex',
            'selected'   => 'required|array',
            'selected.*' => 'integer|exists:seo_pages,id',
        ], [
            'action.required'   => 'Выберите действие.',
            'action.in'         => 'Неизвестное действие.',
            'selected.required' => 'Отметьте хотя бы одну страницу.',
        ]);

        $ids = $r->input('selected');

        // index/noindex пачкой — самое частое массовое действие в SEO,
        // а раньше его не было вовсе
        if (in_array($r->action, ['index', 'noindex'], true)) {
            $value = $r->action === 'index';
            SeoPage::whereIn('id', $ids)->update([
                'robots_index' => $value,
                'updated_by'   => auth()->id(),
            ]);
            $this->clearCacheForIds($ids);
            SeoPage::bumpMetaCacheVersion();

            return back()->with('status', ($value ? 'Разрешено индексировать: ' : 'Закрыто от индексации: ') . count($ids));
        }

        if ($r->action === 'delete') {
            $pages = SeoPage::whereIn('id', $ids)->get();
            foreach ($pages as $page) {
                Cache::forget('seo_page_' . md5($page->slug));
                SeoPageDeleted::dispatch($page);
            }
            SeoPage::whereIn('id', $ids)->delete();
            $this->rebuildSitemapsSafe();
            return back()->with('status', 'Выбранные страницы удалены.');
        }

        if ($r->action === 'lock') {
            SeoPage::whereIn('id', $ids)->update([
                'locked' => true,
                'updated_by' => auth()->id()
            ]);
            $this->clearCacheForIds($ids);
            return back()->with('status', 'Выбранные страницы заблокированы.');
        }

        if ($r->action === 'unlock') {
            SeoPage::whereIn('id', $ids)->update([
                'locked' => false,
                'updated_by' => auth()->id()
            ]);
            $this->clearCacheForIds($ids);
            return back()->with('status', 'Выбранные страницы разблокированы.');
        }

        if ($r->action === 'sync') {
            $count = 0;
            foreach ($ids as $id) {
                try {
                    $this->refresh($id);
                    $count++;
                } catch (\Throwable $e) {
                    // Пропускаем ошибки
                }
            }
            return back()->with('status', "Синхронизировано страниц: {$count}");
        }

        return back()->with('status', 'Выберите действие.');
    }

    protected function clearCacheForIds(array $ids): void
    {
        $pages = SeoPage::whereIn('id', $ids)->get(['slug']);
        foreach ($pages as $page) {
            Cache::forget('seo_page_' . md5($page->slug));
        }
    }

    /**
     * Синхронизация (Новости + Страницы) + авто-чистка сирот
     */
    public function sync(Request $r)
    {
        $created = $updated = $failed = 0;
        $errorBag = [];

        // -------- Новости --------
        if (class_exists(\Modules\News\Models\News::class)) {
            $news = \Modules\News\Models\News::query()->get(); // soft-deleted тут и так не приходят
            foreach ($news as $n) {
                try {
                    $slugPart = $n->slug ?: Str::slug((string)($n->title ?? 'news')) ?: 'news-' . $n->id;
                    $slug     = $this->normalizeSlug('/news/' . $slugPart);
                    $exists   = SeoPage::withTrashed()->where('slug', $slug)->exists();

                    $isPublished = (bool)($n->published ?? $n->is_published ?? $n->active ?? 1);
                    if (!$isPublished && !$exists) {
                        continue;
                    }

                    // если уже существует и locked — пропускаем
                    $locked = SeoPage::where('slug', $slug)->value('locked');
                    if ($locked) { $updated++; continue; }

                    app(\Modules\Seo\Services\SeoSyncService::class)->upsertFromNews($n, /* force */ false);
                    $exists ? $updated++ : $created++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errorBag[] = 'News #' . $n->id . ' (' . ($n->slug ?? '') . '): ' . $e->getMessage();
                }
            }
        }

        // -------- Страницы (Menu/Page) --------
        if (class_exists(\Modules\Menu\Models\Page::class)) {
            $pages = \Modules\Menu\Models\Page::query()->get();
            foreach ($pages as $p) {
                try {
                    $slugRaw  = $p->slug ?? $p->path ?? null;
                    $slugPart = $slugRaw ?: Str::slug((string)($p->title ?? 'page')) ?: 'page-' . $p->id;
                    // Реальный адрес страницы — /page/{slug} (см. SeoSyncService)
                    $slug     = $this->normalizeSlug('/page/' . ltrim((string) $slugPart, '/'));
                    $exists   = SeoPage::withTrashed()->where('slug', $slug)->exists();

                    $isPublished = (bool)($p->published ?? $p->is_published ?? $p->active ?? 1);
                    if (!$isPublished && !$exists) {
                        continue;
                    }

                    $locked = SeoPage::where('slug', $slug)->value('locked');
                    if ($locked) { $updated++; continue; }

                    app(\Modules\Seo\Services\SeoSyncService::class)->upsertFromMenuPage($p, /* force */ false);
                    $exists ? $updated++ : $created++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errorBag[] = 'Page #' . $p->id . ' (' . ($p->slug ?? ($p->path ?? '')) . '): ' . $e->getMessage();
                }
            }
        }

        // --- НОВОЕ: авто-чистка «сиротских» записей ---
        $orphansDeleted = $this->deleteOrphanSeo();
        // --- /НОВОЕ ---

        $this->rebuildSitemapsSafe();

        return redirect()->route('seo.pages.index')->with([
            'status'      => "Синхронизация завершена: создано {$created}, обновлено {$updated}, ошибок: {$failed}, удалено сиротских: {$orphansDeleted}",
            'sync_errors' => $errorBag,
        ]);
    }

    public function refresh($id)
    {
        $page = SeoPage::findOrFail($id);

        try {
            if (class_exists(\Modules\Seo\Services\SeoSyncService::class)) {
                app(\Modules\Seo\Services\SeoSyncService::class)->resyncOne($page, /* force */ false);
            }

            $page->canonical = $this->absolutizeCanonical($page->canonical, $page->slug);
            $page->save();

            $this->rebuildSitemapsSafe();
            $this->pushIndexNowSafe([$page->canonical ?: $page->slug]);

            return back()->with('status', 'Пересинхронизировано');
        } catch (\Throwable $e) {
            return back()->with('status', 'Пересинхронизация не выполнена: ' . $e->getMessage());
        }
    }

    public function cleanOrphans(Request $r)
    {
        $deleted = $this->deleteOrphanSeo();
        return back()->with('status', "Удалено сиротских SEO: {$deleted}");
    }

    // ----------------- helpers -----------------

    /**
     * Подготовка данных из Request для сохранения
     */
    protected function prepareData(array $validated, bool $isUpdate): array
    {
        $out = [];

        // Базовые поля
        foreach (['slug', 'title', 'h1', 'description', 'canonical', 'keywords'] as $key) {
            if (!$isUpdate || array_key_exists($key, $validated)) {
                $out[$key] = $validated[$key] ?? null;
            }
        }

        // Robots
        if ($isUpdate) {
            if (array_key_exists('robots_index', $validated)) {
                $out['robots_index'] = (bool)($validated['robots_index'] ?? true);
            }
            if (array_key_exists('robots_follow', $validated)) {
                $out['robots_follow'] = (bool)($validated['robots_follow'] ?? true);
            }
        } else {
            $out['robots_index'] = (bool)($validated['robots_index'] ?? true);
            $out['robots_follow'] = (bool)($validated['robots_follow'] ?? true);
        }

        // OG данные
        $og = [];
        $ogMap = [
            'og:title' => 'og_title',
            'og:description' => 'og_description',
            'og:image' => 'og_image',
            'og:type' => 'og_type',
            'og:url' => 'og_url',
        ];
        foreach ($ogMap as $prop => $formKey) {
            if (isset($validated[$formKey]) && !empty($validated[$formKey])) {
                $og[$prop] = $validated[$formKey];
            }
        }
        if (!empty($og)) {
            $out['og'] = $og;
        }

        // Twitter данные
        $twitter = [];
        if (isset($validated['twitter_card'])) {
            $twitter['twitter:card'] = $validated['twitter_card'];
        }
        if (isset($validated['twitter_title'])) {
            $twitter['twitter:title'] = $validated['twitter_title'];
        }
        if (isset($validated['twitter_description'])) {
            $twitter['twitter:description'] = $validated['twitter_description'];
        }
        if (isset($validated['twitter_image'])) {
            $twitter['twitter:image'] = $validated['twitter_image'];
        }
        if (!empty($twitter)) {
            $og = $out['og'] ?? [];
            $out['og'] = array_merge($og, $twitter);
        }

        // JSON-LD
        if (isset($validated['jsonld_raw']) && !empty($validated['jsonld_raw'])) {
            $jsonld = json_decode($validated['jsonld_raw'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $out['jsonld'] = $jsonld;
            }
        }

        return $out;
    }


    protected function normalizeSlug(string $slug): string
    {
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


    protected function filterColumns(array $data): array
    {
        static $allowed = null;
        if ($allowed === null) {
            try {
                $cols = Schema::getColumnListing('seo_pages');
            } catch (\Throwable $e) {
                $cols = [];
            }
            $allowed = array_flip($cols);
        }
        return array_intersect_key($data, $allowed);
    }


    protected function rebuildSitemapsSafe(): void
    {
        if (class_exists(\Modules\Seo\Jobs\BuildSitemaps::class)) {
            try {
                dispatch(new \Modules\Seo\Jobs\BuildSitemaps());
            } catch (\Throwable $e) {
                Log::debug('Sitemaps rebuild skipped: ' . $e->getMessage());
            }
        }
    }

    protected function pushIndexNowSafe(array $urls): void
    {
        if (!class_exists(\Modules\Seo\Jobs\PushIndexNow::class)) return;
        try {
            dispatch(new \Modules\Seo\Jobs\PushIndexNow($urls));
        } catch (\Throwable $e) {
            Log::debug('IndexNow push skipped: ' . $e->getMessage());
        }
    }

    protected function pushBackToSourceSafe(SeoPage $page): void
    {
        $enabled = (bool) config('seo.features.push_back_to_sources', false)
                || (bool) config('seo.sync.push_back_to_source', false);

        if (!$enabled) return;
        if (!class_exists(\Modules\Seo\Services\SeoSyncService::class)) return;

        try {
            $svc = app(\Modules\Seo\Services\SeoSyncService::class);

            if (method_exists($svc, 'pushBackFromSeo')) {
                $svc->pushBackFromSeo($page);
            } elseif (method_exists($svc, 'pushBackToSource')) {
                $svc->pushBackToSource($page);
            } elseif (method_exists($svc, 'syncToSource')) {
                $svc->syncToSource($page);
            }
        } catch (\Throwable $e) {
            Log::debug('SEO push-back skipped', ['id' => $page->id, 'error' => $e->getMessage()]);
        }
    }

    protected function deleteOrphanSeo(): int
    {
        $deleted = 0;

        $hasNews = class_exists(\Modules\News\Models\News::class);
        $hasPage = class_exists(\Modules\Menu\Models\Page::class);

        SeoPage::query()
            ->whereIn('source_type', ['news', 'page'])
            ->whereNotNull('source_id')
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$deleted, $hasNews, $hasPage) {
                foreach ($chunk as $sp) {
                    $manual = is_array($sp->manual_fields ?? null) ? $sp->manual_fields : [];
                    if (!empty($manual)) {
                        continue;
                    }

                    $sid   = (int)($sp->source_id ?? 0);
                    $stype = (string)($sp->source_type ?? '');

                    $exists = false;
                    $isSoftDeleted = false;

                    try {
                        if ($stype === 'news' && $hasNews) {
                            $row = \Modules\News\Models\News::withTrashed()->find($sid);
                            if ($row) {
                                $exists = true;
                                $isSoftDeleted = property_exists($row, 'deleted_at') && !is_null($row->deleted_at);
                            }
                        } elseif ($stype === 'page' && $hasPage) {
                            $row = \Modules\Menu\Models\Page::withTrashed()->find($sid);
                            if ($row) {
                                $exists = true;
                                $isSoftDeleted = property_exists($row, 'deleted_at') && !is_null($row->deleted_at);
                            }
                        }
                    } catch (\Throwable $e) {
                        try {
                            if ($stype === 'news' && $hasNews) {
                                $exists = (bool)\Modules\News\Models\News::find($sid);
                            } elseif ($stype === 'page' && $hasPage) {
                                $exists = (bool)\Modules\Menu\Models\Page::find($sid);
                            }
                        } catch (\Throwable $e2) {
                            $exists = false;
                        }
                    }

                    if (!$exists || $isSoftDeleted) {
                        try {
                            $sp->delete();
                            $deleted++;
                        } catch (\Throwable $e) {
                        }
                    }
                }
            });

        return $deleted;
    }
}
