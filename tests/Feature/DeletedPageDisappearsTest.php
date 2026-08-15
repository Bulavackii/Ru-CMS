<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Models\Page;
use Tests\TestCase;

/**
 * Удалённая страница пропадает с сайта сразу.
 *
 * Владелец сообщил: «удалил страницу в админке, а она на фронте до сих пор
 * висит». Разбор дал две независимые причины, и каждой хватало по
 * отдельности:
 *
 *   1. Пункт меню типа «страница» связан с ней через linked_id. Страницы
 *      нет — frontendUrl() отдаёт '#', но САМ ПУНКТ продолжал рисоваться:
 *      живой на вид, со старым названием и никуда не ведущий.
 *   2. Список пунктов лежит в кеше ЧАС, а сбрасывали его только правки
 *      меню. Правка или удаление страницы кеш не трогали вовсе.
 *
 * ⚠️ Пункт при этом НЕ удаляется: его завёл владелец, и решать, убрать его
 * или перенаправить, тоже ему. Скрывается только показ на сайте.
 */
class DeletedPageDisappearsTest extends TestCase
{
    use RefreshDatabase;

    private function менюСоСтраницей(Page $страница): array
    {
        $menu = Menu::create([
            'title' => 'Главное меню', 'position' => 'header', 'active' => true,
        ]);

        $пункт = MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'Ссылка на страницу',
            'type' => 'page', 'linked_id' => $страница->id,
            'order' => 0, 'active' => true,
        ]);

        return [$menu, $пункт];
    }

    private function страница(array $поля = []): Page
    {
        return Page::create(array_merge([
            'title' => 'О проекте', 'slug' => 'o-proekte-proba',
            'content' => '<p>Текст</p>', 'published' => true,
        ], $поля));
    }

    /** Пункт, ведущий на удалённую страницу, на сайте не показывается. */
    public function test_menu_item_of_a_deleted_page_is_hidden(): void
    {
        $страница = $this->страница();
        $this->менюСоСтраницей($страница);

        $до = Menu::cachedByPosition('header')->first();
        $this->assertCount(1, $до->items, 'Пункт должен быть виден, пока страница на месте');

        $страница->delete();

        $после = Menu::cachedByPosition('header')->first();
        $this->assertCount(0, $после->items,
            'Пункт удалённой страницы остался в меню — на сайте висит мёртвая ссылка');
    }

    /**
     * Снятая с публикации страница — то же самое.
     *
     * Черновик посетителю недоступен, значит и пункт на него вести не должен.
     */
    public function test_menu_item_of_an_unpublished_page_is_hidden(): void
    {
        $страница = $this->страница();
        $this->менюСоСтраницей($страница);

        $страница->update(['published' => false]);

        $меню = Menu::cachedByPosition('header')->first();
        $this->assertCount(0, $меню->items);
    }

    /**
     * 🔴 Кеш меню сбрасывается правкой САМОЙ страницы.
     *
     * Без этого предыдущие проверки проходили бы только на пустом кеше, а у
     * владельца список пунктов уже лежал прогретым — и удалённая страница
     * висела бы ещё час.
     */
    public function test_touching_a_page_flushes_the_menu_cache(): void
    {
        $страница = $this->страница();
        $this->менюСоСтраницей($страница);

        // ⚠️ Принудительно НЕ-taggable стор. В тестах стор `array`, он
        // поддерживает теги, и cachedByPosition кладёт список под тегом —
        // по голому ключу menu.header его там нет, и проверка «прогрелся»
        // падала на пустом месте. У владельца стор `file`, тегов не
        // поддерживает: проверяем ровно тот путь, что работает у него.
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => sys_get_temp_dir() . '/cms-page-cache-test',
        ]);
        Cache::purge('file');
        Cache::flush();

        // Греем кеш, как это делает первый же посетитель сайта.
        Menu::cachedByPosition('header');
        $this->assertNotNull(Cache::get('menu.header'), 'Кеш меню не прогрелся');

        $страница->delete();

        $this->assertNull(Cache::get('menu.header'),
            'Удаление страницы не сбросило кеш меню');
    }

    /** Кеш блока страниц на главной сбрасывается так же. */
    public function test_touching_a_page_flushes_the_home_block(): void
    {
        $страница = $this->страница(['show_on_homepage' => true]);

        Cache::put('home_pages', collect([$страница]), 3600);

        $страница->delete();

        $this->assertNull(Cache::get('home_pages'));
    }

    /**
     * 🔴 Слушатель сброса кеша не должен обрывать остальных.
     *
     * Слушатель был написан стрелочной функцией и потому ВОЗВРАЩАЛ результат
     * Cache::forget() — то есть false, когда ключа в кеше не было. Диспетчер
     * событий прекращает обход на первом же false, и все слушатели, стоящие
     * дальше, молча не выполнялись. В их числе — синхронизация SEO: у
     * страницы, сохранённой при холодном кеше, SEO-запись просто не
     * заводилась.
     *
     * В бою это проявлялось через раз: первое сохранение ключ находило (сайт
     * посещали) и цепочка отрабатывала, а следующее — уже нет, потому что
     * первое же его и стёрло.
     *
     * Проверяем не косвенно, а прямо: вешаем свой слушатель ПОСЛЕ и требуем,
     * чтобы он выполнился при заведомо пустом кеше.
     */
    public function test_flush_listener_does_not_break_the_chain(): void
    {
        Cache::forget('home_pages');

        $дошло = false;
        Page::saved(function () use (&$дошло) { $дошло = true; });

        $this->страница(['slug' => 'cepochka-proba']);

        $this->assertTrue($дошло,
            'Слушатель сброса кеша оборвал цепочку — следующие за ним не выполнились');
    }

    /** Обычные ссылки фильтр не трогает — они ни с чем не связаны. */
    public function test_plain_url_items_are_untouched(): void
    {
        $menu = Menu::create(['title' => 'Подвал', 'position' => 'footer', 'active' => true]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'Главная', 'type' => 'url',
            'url' => '/', 'order' => 0, 'active' => true,
        ]);

        $меню = Menu::cachedByPosition('footer')->first();

        $this->assertCount(1, $меню->items);
    }

    /** Вложенный пункт на удалённую страницу тоже скрывается. */
    public function test_nested_item_is_hidden_too(): void
    {
        $родитель = $this->страница(['slug' => 'roditel-proba']);
        $ребёнок = $this->страница(['slug' => 'rebenok-proba', 'title' => 'Вложенная']);

        [$menu, $пункт] = $this->менюСоСтраницей($родитель);

        MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => $пункт->id,
            'title' => 'Вложенная', 'type' => 'page', 'linked_id' => $ребёнок->id,
            'order' => 0, 'active' => true,
        ]);

        $ребёнок->delete();

        $меню = Menu::cachedByPosition('header')->first();
        $this->assertCount(1, $меню->items);
        $this->assertCount(0, $меню->items->first()->activeChildren,
            'Вложенный пункт удалённой страницы остался в меню');
    }
}
