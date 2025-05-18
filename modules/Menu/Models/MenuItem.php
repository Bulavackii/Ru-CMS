<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 📌 Модель MenuItem
 *
 * 🔹 Представляет пункт меню (может быть вложенным)
 * 🔹 Поддерживает привязку к страницам, категориям или URL
 */
class MenuItem extends Model
{
    // 🗂️ Название таблицы
    protected $table = 'menu_items';

    // ✅ Разрешённые для массового заполнения поля
    protected $fillable = [
        'menu_id',           // 🔗 ID меню, к которому относится пункт
        'parent_id',         // 🔗 ID родительского пункта (для вложенности)
        'title',             // 🏷️ Название пункта меню
        'type',              // 📌 Тип: url, page, category
        'url',               // 🌍 Внешняя или внутренняя ссылка (если type = url)
        'linked_id',         // 🔗 ID связанной сущности (если type = page/category)
        'order',             // 🔢 Порядок отображения
        'meta_title',        // 🧠 SEO: title
        'meta_description',  // 📝 SEO: description
        'meta_keywords',     // 🏷️ SEO: keywords
    ];

    /**
     * 📦 Связь с меню, к которому принадлежит пункт
     *
     * @return BelongsTo
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * 🔝 Родительский пункт (если есть)
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * 🔽 Дочерние пункты меню (вложенность)
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * 📄 Связь с привязанной страницей (если type = page)
     *
     * @return BelongsTo|null
     */
    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(\Modules\Menu\Models\Page::class, 'linked_id');
    }
}
