<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class SeoPage extends Model
{
    use SoftDeletes;

    protected $table = 'seo_pages';

    /**
     * ВНИМАНИЕ: slug НЕ в $fillable — чтобы не затирать его массовым апдейтом.
     * Устанавливайте slug явно: $page->slug = '/path';
     */
    protected $fillable = [
        // базовые SEO-поля
        'title', 'h1', 'description', 'canonical','keywords',
        'robots_index', 'robots_follow',
        'og', 'jsonld',

        // служебные поля синка/источника
        'source_type', 'source_id',
        'manual_fields', 'locked', 'sync_hash',
        'created_by', 'updated_by',

        // допускаем entity_* только если есть в схеме (см. скоупы ниже)
        'entity_type', 'entity_id',
    ];

    protected $guarded = ['slug'];

    protected $casts = [
        'entity_id'     => 'integer',
        'source_id'     => 'integer',
        'robots_index'  => 'boolean',
        'robots_follow' => 'boolean',
        'locked'        => 'boolean',
        'og'            => 'array',
        'jsonld'        => 'array',
        'manual_fields' => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /** Дефолты значений при создании модели */
    protected $attributes = [
        'robots_index'  => true,
        'robots_follow' => true,
    ];

    /** Кеш наличия колонок, чтобы не дергать INFORMATION_SCHEMA каждый раз */
    protected static array $columnCache = [];

    protected static function booted(): void
    {
        // На всякий случай — проставим дефолты и нормализуем canonical перед insert
        static::creating(function (self $m) {
            if ($m->robots_index === null)  $m->robots_index = true;
            if ($m->robots_follow === null) $m->robots_follow = true;

            // пустая строка в canonical превращаем в NULL
            $can = trim((string) ($m->canonical ?? ''));
            $m->canonical = ($can === '') ? null : $can;
        });
    }

    /* ======================== Scopes ======================== */

    /** Поиск по нормализованному slug */
    public function scopeSlug($q, string $slug)
    {
        return $q->where('slug', static::normalizeSlug($slug));
    }

    /**
     * Привязка к произвольной сущности (тип + ID).
     * Применяется только если в схеме реально есть такие колонки.
     */
    public function scopeEntity($q, ?string $type, ?int $id)
    {
        $model = new static();
        if ($model->hasColumn('entity_type') && $model->hasColumn('entity_id')) {
            $q->where('entity_type', $type)->where('entity_id', $id);
        }
        return $q;
    }

    /** Привязка к источнику синхронизации (напр. news + id) */
    public function scopeSource($q, ?string $type, ?int $id)
    {
        if ($this->hasColumn('source_type') && $this->hasColumn('source_id')) {
            $q->where('source_type', $type)->where('source_id', $id);
        }
        return $q;
    }

    /** Только индексируемые страницы (для выборок; для sitemap мы фильтруем отдельно) */
    public function scopeIndexable($q)
    {
        return $q->where('robots_index', true)->where('robots_follow', true);
    }

    /* ===================== Mutators/Safety ===================== */

    /** Нормализуем slug при прямой установке: $model->slug = '...' */
    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = static::normalizeSlug((string) $value);
    }

    /** Лёгкая нормализация canonical */
    public function setCanonicalAttribute($value): void
    {
        $v = trim((string) $value);
        $this->attributes['canonical'] = ($v === '') ? null : rtrim($v);
    }

    /* ======================= Helpers ======================= */

    /** Отмечает поля как вручную изменённые — синк их не перезапишет */
    public function markManual(array $fields): void
    {
        $m = $this->manual_fields ?? [];
        $now = now()->toDateTimeString();
        foreach ($fields as $f) {
            $m[$f] = $m[$f] ?? $now;
        }
        $this->manual_fields = $m;
    }

    public function isManual(string $field): bool
    {
        $m = $this->manual_fields ?? [];
        return array_key_exists($field, $m);
    }

    /** Блокировка/разблокировка записи для автосоздания/пересоздания */
    public function lock(): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
    }

    /** Унифицированная нормализация slug (статическая) */
    public static function normalizeSlug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') return '/';

        // Если пришёл абсолютный URL — берём только path + query
        if (filter_var($slug, FILTER_VALIDATE_URL)) {
            $parts = parse_url($slug);
            $path  = $parts['path'] ?? '/';
            $slug  = $path . (!empty($parts['query']) ? '?' . $parts['query'] : '');
        }

        // Гарантируем ведущий слэш, и убираем хвостовой (кроме корня)
        $slug = '/' . ltrim($slug, '/');
        if (strlen($slug) > 1) {
            $slug = rtrim($slug, '/');
        }

        return $slug;
    }

    /* ======================= Internal ======================= */

    /** Быстрая проверка наличия колонки с кешем за процесс */
    protected function hasColumn(string $column): bool
    {
        $key = $this->table . ':' . $column;
        if (!array_key_exists($key, static::$columnCache)) {
            try {
                static::$columnCache[$key] = Schema::hasColumn($this->table, $column);
            } catch (\Throwable $e) {
                static::$columnCache[$key] = false;
            }
        }
        return static::$columnCache[$key];
    }
}
