<?php
namespace Modules\Seo\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Seo\Models\SeoPage;

class EmbedController extends Controller
{
    /**
     * Сохранение SEO из встроенного виджета на формах других модулей (Новости, Страницы и т.д.).
     * Правила:
     * - slug нормализуем и назначаем ЯВНО ($model->slug = ...), т.к. он guarded.
     * - если есть удалённая (soft delete) запись по entity_type/entity_id — восстанавливаем.
     * - проверяем уникальность slug (с учётом текущей записи).
     * - отмечаем изменённые поля как ручные (manual_fields), чтобы автосинк их не затирал.
     * - дублируем entity_* в source_* (для push-back в новости/страницы).
     * - поддержка поля keywords (если колонка существует).
     */
    public function save(Request $r)
    {
        $data = $r->validate([
            'seo.entity_type'           => 'required|string',
            'seo.entity_id'             => 'nullable|integer',
            'seo.slug'                  => 'required|string|max:1024',
            'seo.title'                 => 'nullable|string|max:255',
            'seo.description'           => 'nullable|string|max:255',
            'seo.canonical'             => 'nullable|string|max:1024',
            'seo.robots_index'          => 'nullable|boolean',
            'seo.robots_follow'         => 'nullable|boolean',
            'seo.keywords'              => 'nullable|string|max:255',
            // OG / Twitter
            'seo.og_title'              => 'nullable|string|max:255',
            'seo.og_description'        => 'nullable|string|max:512',
            'seo.og_image'              => 'nullable|string|max:1024',
            'seo.twitter_card'          => 'nullable|string|max:50',
            'seo.twitter_title'         => 'nullable|string|max:255',
            'seo.twitter_description'   => 'nullable|string|max:512',
            'seo.twitter_image'         => 'nullable|string|max:1024',
            // JSON-LD
            'seo.jsonld_raw'            => 'nullable|string',
        ]);

        // Нормализуем и подчищаем вход
        $p = collect($data['seo'])->map(fn($v) => is_string($v) ? trim($v) : $v)->all();

        // --- slug: допускаем полный URL — приводим к path+query и нормализуем как в модели
        $slug = (string)($p['slug'] ?? '/');
        if (filter_var($slug, FILTER_VALIDATE_URL)) {
            $parts = parse_url($slug);
            $path  = $parts['path'] ?? '/';
            $slug  = $path . (!empty($parts['query']) ? ('?' . $parts['query']) : '');
        }
        $slug = SeoPage::normalizeSlug($slug);

        // --- canonical: если не абсолютный — делаем абсолютным (или null)
        $canonical = $p['canonical'] ?? null;
        if ($canonical !== null && $canonical !== '') {
            if (!Str::startsWith($canonical, ['http://','https://'])) {
                $canonical = rtrim((string)config('app.url'), '/') . $slug;
            }
        } else {
            $canonical = null;
        }

        // --- OG/Twitter -> компактный массив без пустых значений
        $og = array_filter([
            'og:title'            => $p['og_title']            ?? null,
            'og:description'      => $p['og_description']      ?? null,
            'og:image'            => $p['og_image']            ?? null,
            'twitter:card'        => $p['twitter_card']        ?? null,
            'twitter:title'       => $p['twitter_title']       ?? null,
            'twitter:description' => $p['twitter_description'] ?? null,
            'twitter:image'       => $p['twitter_image']       ?? null,
        ], static fn($x) => !is_null($x) && $x !== '');

        // --- JSON-LD — сохраняем только валидный JSON
        $jsonld = null;
        if (!empty($p['jsonld_raw'])) {
            $decoded = json_decode($p['jsonld_raw'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $jsonld = $decoded;
            }
        }

        // --- Булевы: по умолчанию TRUE
        $robotsIndex  = array_key_exists('robots_index',  $p) ? (bool)$p['robots_index']  : true;
        $robotsFollow = array_key_exists('robots_follow', $p) ? (bool)$p['robots_follow'] : true;

        // --- Keywords (если колонка есть)
        $keywordsColExists = Schema::hasColumn('seo_pages', 'keywords');
        $keywords = $keywordsColExists
            ? (($p['keywords'] ?? null) !== '' ? (string)$p['keywords'] : null)
            : null;

        $entityType = (string)($p['entity_type'] ?? '');
        $entityId   = isset($p['entity_id']) ? (int)$p['entity_id'] : null;

        /** @var SeoPage $savedPage */
        $savedPage = null;

        DB::transaction(function () use (
            $entityType, $entityId, $slug, $canonical, $robotsIndex, $robotsFollow, $og, $jsonld, $p,
            $keywordsColExists, $keywords, &$savedPage
        ) {
            // ищем с учётом soft-deletes
            $page = SeoPage::withTrashed()
                ->where('entity_type', $entityType)
                ->where('entity_id',   $entityId)
                ->first();

            if ($page && method_exists($page, 'trashed') && $page->trashed()) {
                $page->restore();
            }

            if (!$page) {
                $page = new SeoPage();
                $page->entity_type = $entityType;
                $page->entity_id   = $entityId;
            }

            // Проверка уникальности slug против других записей
            $dup = SeoPage::withTrashed()
                ->where('slug', $slug)
                ->when($page->exists, fn($q) => $q->where('id', '!=', $page->id))
                ->first();
            if ($dup) {
                throw ValidationException::withMessages([
                    'seo.slug' => 'Этот slug уже используется другой SEO-записью.',
                ]);
            }

            // Для сравнения изменений возьмём «старые» значения
            $old = $page->getAttributes();

            // Назначаем slug ЯВНО (guarded)
            $page->slug = $slug;

            // Дублируем в source_* (для push-back, например для новостей/страниц)
            $page->source_type = $entityType;
            $page->source_id   = $entityId;

            // Остальные поля через fill
            $page->fill([
                'title'         => $p['title']        ?? null,
                'description'   => $p['description']  ?? null,
                'canonical'     => $canonical,
                'robots_index'  => $robotsIndex,
                'robots_follow' => $robotsFollow,
                'og'            => $og ?: null,
                'jsonld'        => $jsonld,
            ]);

            // keywords задаём явно (обходит mass-assignment, если поле не в $fillable)
            if ($keywordsColExists) {
                $page->keywords = $keywords;
            }

            $page->save();

            // Отмечаем реально изменённые поля как «ручные»
            if (method_exists($page, 'markManual')) {
                $changed = [];
                foreach (['slug','title','description','canonical','robots_index','robots_follow','og','jsonld'] as $k) {
                    $new = $page->{$k};
                    $oldVal = $old[$k] ?? null;
                    $eq = is_array($new) || is_array($oldVal)
                        ? json_encode($new) === json_encode($oldVal)
                        : $new === $oldVal;
                    if (!$eq) $changed[] = $k;
                }
                // keywords тоже помечаем, если колонка есть
                if ($keywordsColExists) {
                    $oldKw = $old['keywords'] ?? null;
                    if (($page->keywords ?? null) !== $oldKw) {
                        $changed[] = 'keywords';
                    }
                }
                if ($changed) {
                    $page->markManual($changed);
                    $page->save();
                }
            }

            $savedPage = $page;
        });

        // Пересобираем sitemap (без паники, если очереди нет)
        try {
            if (class_exists(\Modules\Seo\Jobs\BuildSitemaps::class)) {
                dispatch(new \Modules\Seo\Jobs\BuildSitemaps());
            }
        } catch (\Throwable $e) {
            \Log::debug('Sitemaps rebuild skipped (embed): '.$e->getMessage());
        }

        // Пингуем IndexNow (если включено и настроено)
        try {
            if (config('seo.features.indexnow') && config('seo.indexnow.key')) {
                $absUrl = rtrim((string)config('app.url'), '/') . $slug;
                if (class_exists(\Modules\Seo\Jobs\PushIndexNow::class)) {
                    \Modules\Seo\Jobs\PushIndexNow::dispatch([$absUrl]);
                }
            }
        } catch (\Throwable $e) {
            // не блокируем сохранение
            report($e);
        }

        // ✅ Пуш-бэк правок из SeoPage в источник (Новости/Страницы), если включено в конфиге
        try {
            if ($savedPage && class_exists(\Modules\Seo\Services\SeoSyncService::class)) {
                app(\Modules\Seo\Services\SeoSyncService::class)->pushBackFromSeo($savedPage);
            }
        } catch (\Throwable $e) {
            \Log::debug('SEO push-back skipped (embed): '.$e->getMessage());
        }

        return back()->with('status', 'SEO сохранено');
    }
}
