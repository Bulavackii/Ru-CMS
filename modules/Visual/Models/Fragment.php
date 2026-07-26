<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Fragment extends Model
{
    protected $table = 'visual_fragments';
    protected $fillable = ['slug','title','type','zone','schema','data','html_cached','css_inline','is_active','updated_by'];
    
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 📍 Зоны вывода: куда фрагмент попадает на страницах.
     *
     * Раньше валидация принимала только header|footer|custom, а выводились
     * фрагменты лишь в layouts/app.blade.php — лейауте трёх второстепенных
     * вьюх. Теперь зон шесть: по две у сайта и панели плюс полоса объявления
     * и блок под контентом. Старые header/footer оставлены ради совместимости
     * с системными site-header / site-footer.
     */
    public const ZONE_LABELS = [
        'frontend.topbar'         => 'Сайт · полоса над шапкой',
        'frontend.header'         => 'Сайт · под шапкой',
        'frontend.content.bottom' => 'Сайт · под содержимым',
        'frontend.footer'         => 'Сайт · в подвале',
        'admin.header'            => 'Панель · под шапкой',
        'admin.footer'            => 'Панель · в подвале',
        'header'                  => 'Устаревшая зона: шапка',
        'footer'                  => 'Устаревшая зона: подвал',
        'custom'                  => 'Без зоны (вставка вручную)',
    ];

    /** @return string[] */
    public static function zones(): array
    {
        return array_keys(self::ZONE_LABELS);
    }

    public function zoneLabel(): string
    {
        return self::ZONE_LABELS[$this->zone] ?? ($this->zone ?: 'Без зоны');
    }

    /** Системные фрагменты: их slug и зона закреплены */
    public function isSystem(): bool
    {
        return in_array($this->slug, ['site-header', 'site-footer'], true);
    }

    /** Ключ, в котором лежит версия кеша зон */
    private const CACHE_VERSION_KEY = 'fragments_cache_version';

    /**
     * 🔄 Правка фрагмента должна сразу отражаться на страницах.
     *
     * Ключи кеша зависят от зоны, поэтому в них подмешивается версия — здесь
     * она просто меняется (тот же приём, что у Theme).
     */
    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function cacheVersion(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::rememberForever(self::CACHE_VERSION_KEY, fn () => 1);
    }

    public static function flushCache(): void
    {
        \Illuminate\Support\Facades\Cache::forever(self::CACHE_VERSION_KEY, static::cacheVersion() + 1);
    }

    /**
     * 👤 Связь с пользователем, обновившим фрагмент
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 📜 Связь с ревизиями фрагмента
     */
    public function revisions(): HasMany
    {
        return $this->morphMany(Revision::class, 'target');
    }
}
