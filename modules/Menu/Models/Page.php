<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 📄 Модель Page
 *
 * 🔹 Представляет статическую страницу
 * 🔹 Поддерживает SEO-поля и вывод на главной
 * 🔹 Может быть привязана к нескольким категориям
 */
class Page extends Model
{
    // 🗂️ Название таблицы в БД
    protected $table = 'pages';

    // ✅ Разрешённые для массового заполнения поля
    protected $fillable = [
        'title',             // 🏷️ Название страницы
        'slug',              // 🔗 URL-псевдоним
        'content',           // 📝 Основной HTML-контент
        'published',         // ✅ Флаг публикации
        'show_on_homepage',  // 🏠 Показ на главной странице
        'homepage_order',    // 🔢 Порядок отображения на главной
        'meta_title',        // 🧠 SEO: title
        'meta_description',  // 📝 SEO: description
        'meta_keywords',     // 🏷️ SEO: keywords
    ];

    /**
     * 🗂️ Категории, к которым привязана страница
     *
     * 💡 Таблица связей: page_category (page_id, category_id)
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Category::class, 'page_category');
    }
}
