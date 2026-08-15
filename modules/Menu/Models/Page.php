<?php

namespace Modules\Menu\Models;

use App\Support\HasContentTranslations;

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
    use HasContentTranslations;

    /** Поля, которые можно перевести на другие языки (см. трейт) */
    public array $translatable = ['title', 'content', 'meta_title', 'meta_description'];

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
        return $this->belongsToMany(\Modules\Categories\Models\Category::class, 'page_category');
    }

    /**
     * ♻️ Автосброс кеша главной при изменении страниц.
     *
     * HomeController кеширует выборку страниц «на главной» ключом home_pages на час.
     * Сбрасывали его только слушатели новостей и категорий, но НЕ сама страница —
     * поэтому галочка «Показать на главной» (как и правка текста, снятие публикации
     * или удаление) до часа не давала видимого эффекта на сайте.
     */
    protected static function booted(): void
    {
        $flush = function () {
            \Illuminate\Support\Facades\Cache::forget('home_pages');

            // ⚠️ И кеш МЕНЮ тоже. Пункт типа «страница» показывается по
            // связи с этой записью, а список пунктов лежит в кеше на час:
            // удалённая страница продолжала висеть в меню до истечения
            // срока, даже когда её самой в базе уже не было.
            \Modules\Menu\Models\Menu::flushCache();
        };

        static::saved($flush);
        static::deleted($flush);
    }
}
