<?php

namespace Modules\Menu\Models;

use App\Support\HasContentTranslations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    use HasContentTranslations;

    /** Поля, которые можно перевести на другие языки (см. трейт) */
    public array $translatable = ['title'];

    protected $table = 'menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'type',
        'url',
        'linked_id',
        'order',
        'active',
        'icon',
        'icon_image',
        'css_class',
        'target',
        'rel',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('active', true)
            ->orderBy('order');
    }

    /**
     * Получить глубину вложенности пункта меню
     * 
     * @return int Глубина (0 = корневой уровень)
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        
        return $depth;
    }

    /**
     * Проверить, можно ли добавить дочерний элемент
     * (максимум 3 уровня вложенности)
     * 
     * @return bool
     */
    /**
     * Адрес картинки попадает в JSON вместе с пунктом.
     *
     * Редактор дерева отдаёт пункты в браузер через @json($items) и там же
     * открывает модалку правки. Без этого поля модалка не знала бы, что у
     * пункта уже есть картинка, и показать её было бы нечем.
     */
    protected $appends = ['icon_image_url'];

    public function getIconImageUrlAttribute(): ?string
    {
        return $this->iconImageUrl();
    }

    /**
     * Адрес загруженной картинки значка или null.
     *
     * Собирать путь в шаблонах нельзя: их четыре (подвал сайта, подвал
     * панели, узел меню, карточка списка), и они уже расходились в мелочах.
     */
    public function iconImageUrl(): ?string
    {
        $path = trim((string) $this->icon_image);

        if ($path === '') {
            return null;
        }

        if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    }

    public function canHaveChildren(): bool
    {
        return $this->getDepth() < 2; // 0, 1, 2 = максимум 3 уровня
    }

    /**
     * Scope для активных пунктов
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(\Modules\Menu\Models\Page::class, 'linked_id');
    }

    public function linkedCategory(): BelongsTo
    {
        return $this->belongsTo(\Modules\Categories\Models\Category::class, 'linked_id');
    }

    /**
     * Готовая ссылка пункта для фронтенда (url / страница / категория).
     * Общая для всех позиций меню (header/footer/sidebar) — чтобы не дублировать
     * логику по партиалам.
     */
    public function frontendUrl(): string
    {
        return match ($this->type) {
            'url'      => $this->url ?: '#',
            'page'     => optional($this->linkedPage)?->slug
                            ? route('frontend.pages.show', $this->linkedPage->slug)
                            : '#',
            'category' => url('/?category=' . $this->linked_id),
            default    => '#',
        };
    }

    /**
     * Имя иконки для показа: своя, либо дефолтная по типу (валидные имена Lucide).
     */
    public function displayIcon(): string
    {
        return $this->icon ?: match ($this->type) {
            'page'     => 'file-text',
            'category' => 'tag',
            default    => 'link',
        };
    }

    /* Автосброс кеша при изменениях пунктов меню */
    protected static function booted()
    {
        $flush = fn() => \Modules\Menu\Models\Menu::flushCache();
        static::saved($flush);
        static::deleted($flush);
    }
}
