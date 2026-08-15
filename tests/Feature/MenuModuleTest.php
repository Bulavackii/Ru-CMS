<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Modules\Categories\Models\Category;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Models\Page;
use Tests\TestCase;

/**
 * Покрытие модуля Menu (меню / пункты / страницы). До этого теста по модулю
 * не было ни одного — именно поэтому целый пласт багов (нерабочий destroy-роут,
 * order=0 у новых пунктов, пропажа 3-го уровня, no-op массовых действий,
 * чисто клиентский поиск страниц, выборка категорий по несуществующей колонке
 * name, ненормализованные чекбоксы) дожил до ручной проверки в браузере.
 */
class MenuModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);

        // admin_categories_list кешируется статическим ключом, а array-стор в
        // тестах живёт весь процесс PHPUnit и между тестами не сбрасывается —
        // чистим, чтобы один тест не заражал другой устаревшим списком.
        Cache::flush();
    }

    private function makeMenu(array $attrs = []): Menu
    {
        return Menu::create(array_merge([
            'title'    => 'Главное меню',
            'position' => 'header',
            'active'   => true,
        ], $attrs));
    }

    private function makeItem(Menu $menu, array $attrs = []): MenuItem
    {
        return $menu->items()->create(array_merge([
            'title'  => 'Пункт',
            'type'   => 'url',
            'url'    => '/',
            'active' => true,
        ], $attrs));
    }

    /* ── Меню ─────────────────────────────────────────────────── */

    public function test_admin_can_view_menus_index(): void
    {
        $this->makeMenu();

        $this->actingAs($this->admin)
            ->get(route('admin.menus.index'))
            ->assertStatus(200)
            ->assertViewIs('Menu::admin.menu.index');
    }

    public function test_admin_can_view_menu_edit(): void
    {
        $menu = $this->makeMenu();
        $this->makeItem($menu);

        $this->actingAs($this->admin)
            ->get(route('admin.menus.edit', $menu))
            ->assertStatus(200)
            ->assertViewIs('Menu::admin.menu.edit');
    }

    public function test_menu_save_and_toggle_work_on_non_taggable_cache_store(): void
    {
        // Живой 500 на проде: PATCH /admin/menus/{id}/toggle →
        // BadMethodCallException «This cache store does not support tagging».
        // flushCache()/cachedByPosition() звали Cache::tags(['menus']), а боевой
        // стор (file/database) теги НЕ поддерживает. В тестах стор array
        // (taggable), поэтому баг ловился только вживую — здесь принудительно
        // переключаемся на file-стор (не taggable) во временный каталог ОС.
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => sys_get_temp_dir() . '/cms-menu-cache-test',
        ]);
        Cache::purge('file');
        $this->assertFalse(
            Cache::getStore() instanceof \Illuminate\Cache\TaggableStore,
            'Для теста нужен НЕ taggable-стор'
        );

        // save() → booted(saved) → flushCache(): раньше падало здесь
        $menu = $this->makeMenu(['active' => true]);

        // тот самый роут, что упал вживую
        $this->actingAs($this->admin)
            ->patch(route('admin.menus.toggle', $menu))
            ->assertRedirect();
        // модель Menu без boolean-каста (в отличие от MenuItem) — сравниваем с 0
        $this->assertEquals(0, $menu->fresh()->active);

        // и чтение кеша меню по позиции на не-taggable сторе тоже не должно падать
        $this->assertNotNull(Menu::cachedByPosition('header'));
    }

    public function test_admin_can_create_menu(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.menus.store'), [
                'title'    => 'Футер',
                'position' => 'footer',
                'active'   => '1',
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menus', ['title' => 'Футер', 'position' => 'footer']);
    }

    public function test_menus_destroy_route_points_to_menus_prefix(): void
    {
        // Регрессия: раньше admin.menus.destroy указывал на /admin/{menu}
        // вместо /admin/menus/{menu}. Проверяем и сам URL, и что именованный
        // роут реально удаляет меню вместе с его пунктами.
        $menu  = $this->makeMenu();
        $child = $this->makeItem($menu);

        $this->assertStringContainsString(
            "/admin/menus/{$menu->id}",
            route('admin.menus.destroy', $menu)
        );

        $this->actingAs($this->admin)
            ->delete(route('admin.menus.destroy', $menu))
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        $this->assertDatabaseMissing('menu_items', ['id' => $child->id]);
    }

    /* ── Пункты меню ──────────────────────────────────────────── */

    public function test_new_item_gets_next_order_at_end_of_level(): void
    {
        // Две регрессии сразу:
        // 1) добавление КОРНЕВОГО пункта (без parent_id) отдавало 500 —
        //    $validated['parent_id'] читался при отсутствующем ключе; поэтому
        //    проверяем именно редирект, а не молчаливое отсутствие пункта
        //    (assertEquals(0, null) прошёл бы по слабому сравнению и замаскировал
        //    500 — на этом тест и попался при разработке);
        // 2) order нового пункта оставался 0 по дефолту схемы, и пункт вставал
        //    среди первых, а не последним. Теперь nextOrder().
        $menu = $this->makeMenu();

        $this->actingAs($this->admin)->post(route('admin.menu_items.store', $menu), [
            'title' => 'Первый', 'type' => 'url', 'url' => '/a', 'active' => '1',
        ])->assertRedirect(route('admin.menus.edit', $menu));
        $this->actingAs($this->admin)->post(route('admin.menu_items.store', $menu), [
            'title' => 'Второй', 'type' => 'url', 'url' => '/b', 'active' => '1',
        ])->assertRedirect(route('admin.menus.edit', $menu));

        $first  = MenuItem::where('title', 'Первый')->first();
        $second = MenuItem::where('title', 'Второй')->first();
        $this->assertNotNull($first, 'Корневой пункт «Первый» должен создаться (а не упасть 500)');
        $this->assertNotNull($second, 'Корневой пункт «Второй» должен создаться');
        $this->assertEquals(0, $first->order);
        $this->assertEquals(1, $second->order);
    }

    public function test_child_item_can_be_created_under_parent(): void
    {
        $menu   = $this->makeMenu();
        $parent = $this->makeItem($menu, ['title' => 'Родитель']);

        $this->actingAs($this->admin)
            ->post(route('admin.menu_items.store', $menu), [
                'title'     => 'Ребёнок',
                'type'      => 'url',
                'url'       => '/c',
                'parent_id' => $parent->id,
                'active'    => '1',
            ])
            ->assertRedirect(route('admin.menus.edit', $menu));

        $this->assertDatabaseHas('menu_items', [
            'title'     => 'Ребёнок',
            'parent_id' => $parent->id,
            'menu_id'   => $menu->id,
        ]);
    }

    public function test_third_level_item_is_eager_loaded_in_edit_tree(): void
    {
        // Регрессия: edit() грузил только 'children' (один уровень), из-за чего
        // пункты 3-го уровня существовали в БД, но не попадали в дерево
        // редактора. Теперь грузится 'children.children'. Проверяем именно
        // данные вьюхи, а не HTML: @json($items) \u-экранирует кириллицу.
        $menu = $this->makeMenu();
        $l0 = $this->makeItem($menu, ['title' => 'Уровень 0']);
        $l1 = $this->makeItem($menu, ['title' => 'Уровень 1', 'parent_id' => $l0->id]);
        $l2 = $this->makeItem($menu, ['title' => 'Уровень 2', 'parent_id' => $l1->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.edit', $menu));
        $response->assertStatus(200);

        $items = $response->viewData('items');
        $this->assertCount(1, $items, 'Должен быть один корневой пункт');

        $root = $items->first();
        $this->assertTrue($root->relationLoaded('children'));
        $firstChild = $root->children->first();
        $this->assertNotNull($firstChild, 'Потомок 1-го уровня должен подгрузиться');
        $this->assertTrue($firstChild->relationLoaded('children'));
        $this->assertEquals(
            $l2->id,
            optional($firstChild->children->first())->id,
            'Пункт 3-го уровня должен попасть в дерево редактора'
        );
    }

    public function test_bulk_deactivate_then_activate_items(): void
    {
        // Регрессия: массовые действия были no-op (фейковый тост + reload).
        $menu = $this->makeMenu();
        $a = $this->makeItem($menu, ['title' => 'A', 'active' => true]);
        $b = $this->makeItem($menu, ['title' => 'B', 'active' => true]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.menu_items.bulk', $menu), [
                'action' => 'deactivate',
                'ids'    => [$a->id, $b->id],
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 2]);

        $this->assertFalse($a->fresh()->active);
        $this->assertFalse($b->fresh()->active);

        $this->actingAs($this->admin)
            ->postJson(route('admin.menu_items.bulk', $menu), [
                'action' => 'activate',
                'ids'    => [$a->id],
            ])
            ->assertStatus(200);

        $this->assertTrue($a->fresh()->active);
        $this->assertFalse($b->fresh()->active, 'Не выбранный пункт трогать нельзя');
    }

    public function test_bulk_delete_removes_item_with_its_children(): void
    {
        $menu   = $this->makeMenu();
        $parent = $this->makeItem($menu, ['title' => 'Родитель']);
        $child  = $this->makeItem($menu, ['title' => 'Ребёнок', 'parent_id' => $parent->id]);
        $other  = $this->makeItem($menu, ['title' => 'Другой']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.menu_items.bulk', $menu), [
                'action' => 'delete',
                'ids'    => [$parent->id],
            ])
            ->assertStatus(200);

        $this->assertDatabaseMissing('menu_items', ['id' => $parent->id]);
        $this->assertDatabaseMissing('menu_items', ['id' => $child->id]);
        $this->assertDatabaseHas('menu_items', ['id' => $other->id]);
    }

    public function test_bulk_rejects_invalid_action(): void
    {
        $menu = $this->makeMenu();
        $a = $this->makeItem($menu);

        $this->actingAs($this->admin)
            ->postJson(route('admin.menu_items.bulk', $menu), [
                'action' => 'nuke',
                'ids'    => [$a->id],
            ])
            ->assertStatus(422);
    }

    public function test_bulk_does_not_touch_items_of_another_menu(): void
    {
        // Проверка изоляции по menu_id: пункт чужого меню не должен затрагиваться.
        $menu    = $this->makeMenu();
        $other   = $this->makeMenu(['title' => 'Другое меню', 'position' => 'footer']);
        $foreign = $this->makeItem($other, ['active' => true]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.menu_items.bulk', $menu), [
                'action' => 'deactivate',
                'ids'    => [$foreign->id],
            ])
            ->assertStatus(404);

        $this->assertTrue($foreign->fresh()->active);
    }

    public function test_changing_parent_recomputes_order_to_end_of_new_siblings(): void
    {
        // Регрессия: при смене родителя пункт сохранял старый order (относился
        // к прежнему уровню). Теперь встаёт в конец новых братьев/сестёр.
        $menu    = $this->makeMenu();
        $parentA = $this->makeItem($menu, ['title' => 'Родитель A']);
        $parentB = $this->makeItem($menu, ['title' => 'Родитель B']);
        $this->makeItem($menu, ['title' => 'B1', 'parent_id' => $parentB->id, 'order' => 0]);
        $this->makeItem($menu, ['title' => 'B2', 'parent_id' => $parentB->id, 'order' => 1]);
        $moving = $this->makeItem($menu, ['title' => 'Переносимый', 'parent_id' => $parentA->id, 'order' => 0]);

        $this->actingAs($this->admin)
            ->put(route('admin.menu_items.update', [$menu, $moving]), [
                'title'     => 'Переносимый',
                'type'      => 'url',
                'url'       => '/x',
                'parent_id' => $parentB->id,
                'active'    => '1',
            ])
            ->assertRedirect(route('admin.menus.edit', $menu));

        $fresh = $moving->fresh();
        $this->assertEquals($parentB->id, $fresh->parent_id);
        $this->assertEquals(2, $fresh->order, 'Должен встать в конец детей B (после order 0 и 1)');
    }

    /* ── Страницы ─────────────────────────────────────────────── */

    public function test_admin_can_view_pages_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.pages.index'))
            ->assertStatus(200)
            ->assertViewIs('Menu::admin.pages.index');
    }

    public function test_admin_can_view_pages_create_form_with_categories(): void
    {
        // Регрессия: create() выбирал Category::select('id','name',...), но
        // колонки name нет (есть title) — запрос ронял форму. Проверяем 200 и
        // что категория (по title) реально доступна форме.
        Category::create(['title' => 'Услуги']);

        $response = $this->actingAs($this->admin)->get(route('admin.pages.create'));
        $response->assertStatus(200)->assertViewIs('Menu::admin.pages.create');
        $response->assertSee('Услуги');
    }

    public function test_pages_search_filters_on_the_server(): void
    {
        // Регрессия: поиск был чисто клиентским (прятал только текущие 10
        // строк), серверный ?q= не вызывался. Теперь index() фильтрует по ?q=.
        Page::create(['title' => 'Альфа страница', 'slug' => 'alpha-' . uniqid()]);
        Page::create(['title' => 'Бета страница',  'slug' => 'beta-'  . uniqid()]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.pages.index', ['q' => 'Альфа']));

        $response->assertStatus(200);
        $response->assertSee('Альфа страница');
        $response->assertDontSee('Бета страница');
    }

    public function test_storing_page_without_published_saves_it_unpublished(): void
    {
        // Нормализация чекбоксов в store(): снятая галочка = явный false.
        $this->actingAs($this->admin)
            ->post(route('admin.pages.store'), [
                'title'      => 'Черновик',
                '_submitted' => '1',
                // published / show_on_homepage НЕ передаём — галочки сняты
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = Page::where('title', 'Черновик')->first();
        $this->assertNotNull($page);
        $this->assertFalse((bool) $page->published);
        $this->assertFalse((bool) $page->show_on_homepage);
    }

    public function test_storing_page_with_published_saves_it_published(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.pages.store'), [
                'title'            => 'Опубликованная',
                'published'        => '1',
                'show_on_homepage' => '1',
                '_submitted'       => '1',
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = Page::where('title', 'Опубликованная')->first();
        $this->assertNotNull($page);
        $this->assertTrue((bool) $page->published);
        $this->assertTrue((bool) $page->show_on_homepage);
    }

    public function test_page_is_created_with_empty_homepage_order(): void
    {
        // Форма шлёт незаполненное поле пустой строкой, глобальный посредник
        // превращает её в null, а колонка объявлена NOT NULL: умолчание базы
        // тут не спасает, оно срабатывает только когда столбца нет в запросе
        // вовсе. Создание страницы падало с ошибкой ограничения — и прежние
        // тесты этого не ловили ровно потому, что поле не отправляли.
        $this->actingAs($this->admin)
            ->post(route('admin.pages.store'), [
                'title'          => 'Без порядка',
                'homepage_order' => '',
                '_submitted'     => '1',
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = Page::where('title', 'Без порядка')->first();

        $this->assertNotNull($page, 'Страница не создалась');
        $this->assertSame(0, (int) $page->homepage_order);
    }

    public function test_page_survives_editing_with_empty_homepage_order(): void
    {
        $page = Page::create(['title' => 'Правка', 'slug' => 'pravka', 'homepage_order' => 5]);

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'          => 'Правка',
                'slug'           => 'pravka',
                'homepage_order' => '',
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->assertSame(0, (int) $page->fresh()->homepage_order);
    }

    // ── Соцсети отдельной позицией ────────────────────────────────────────

    /**
     * Ссылки на соцсети берутся из меню, а не из конфига.
     *
     * Раньше адреса лежали в config/app.php и правились только в файле —
     * добавить новую сеть без разработчика было нельзя.
     */
    public function test_social_links_come_from_the_menu(): void
    {
        $menu = Menu::create(['title' => 'Соцсети', 'position' => 'social', 'active' => true]);

        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'ВКонтакте', 'url' => 'https://vk.com/example',
            'type' => 'url', 'icon' => 'vk', 'order' => 1, 'active' => true,
        ]);

        Menu::flushCache();

        $links = social_links();

        $this->assertCount(1, $links, 'Меню должно перебивать список из конфига.');
        $this->assertSame('vk', $links[0]['key'], 'Знакомая сеть узнаётся по домену.');
        $this->assertSame('https://vk.com/example', $links[0]['href']);
    }

    /**
     * Незнакомая сеть получает СВОЙ значок из пункта меню.
     *
     * Фирменных глифов у нас четыре; без значка из пункта добавленная сеть
     * выводилась бы в подвале пустым квадратом.
     */
    public function test_unknown_network_keeps_its_own_icon(): void
    {
        $menu = Menu::create(['title' => 'Соцсети', 'position' => 'social', 'active' => true]);

        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'Телеграм', 'url' => 'https://t.me/example',
            'type' => 'url', 'icon' => 'send', 'order' => 1, 'active' => true,
        ]);

        Menu::flushCache();

        $links = social_links();

        $this->assertSame('link', $links[0]['key'], 'Незнакомый домен не притворяется брендом.');
        $this->assertSame('send', $links[0]['icon'], 'Значок пункта обязан дойти до подвала.');
    }

    /** Без меню список берётся из конфига — обновление с прежней версии. */
    public function test_config_is_used_while_the_menu_does_not_exist(): void
    {
        config(['app.social.vk' => 'https://vk.com/from-config']);

        Menu::flushCache();

        $links = collect(social_links())->firstWhere('key', 'vk');

        $this->assertNotNull($links, 'Без меню адреса обязаны браться из конфига.');
        $this->assertSame('https://vk.com/from-config', $links['href']);
    }

    /**
     * Сброс кеша охватывает ВСЕ позиции, включая новые.
     *
     * Список позиций был переписан от руки в четырёх местах, и, добавляя
     * social, я обновил три: кеш новой позиции не сбрасывался, и добавленная
     * ссылка не появлялась на сайте до истечения часа. Теперь список один.
     */
    public function test_cache_is_dropped_for_every_position(): void
    {
        foreach (Menu::POSITIONS as $position) {
            Cache::forever('menu.' . $position, 'устаревшее');
        }

        Menu::flushCache();

        foreach (Menu::POSITIONS as $position) {
            $this->assertNull(
                Cache::get('menu.' . $position),
                "Кеш позиции «{$position}» обязан сбрасываться."
            );
        }

        $this->assertContains('social', Menu::POSITIONS);
    }

    // ── Своя картинка значка ──────────────────────────────────────────────

    private function socialItem(): MenuItem
    {
        $menu = Menu::create(['title' => 'Соцсети', 'position' => 'social', 'active' => true]);

        return MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'MAX', 'url' => 'https://max.ru/example',
            'type' => 'url', 'icon' => 'max', 'order' => 1, 'active' => true,
        ]);
    }

    public function test_menu_item_accepts_its_own_icon_image(): void
    {
        Storage::fake('public');

        $item = $this->socialItem();

        $this->actingAs($this->admin)->put(
            route('admin.menu_items.update', [$item->menu_id, $item->id]),
            [
                'title' => $item->title, 'type' => 'url', 'url' => $item->url, 'active' => 1,
                'icon_image' => UploadedFile::fake()->image('logo.png', 64, 64),
            ]
        )->assertSessionHasNoErrors();

        $path = $item->fresh()->icon_image;

        $this->assertNotNull($path, 'Картинка обязана сохраниться.');
        Storage::disk('public')->assertExists($path);
    }

    public function test_removing_the_image_deletes_the_file(): void
    {
        Storage::fake('public');

        $item = $this->socialItem();

        $this->actingAs($this->admin)->put(
            route('admin.menu_items.update', [$item->menu_id, $item->id]),
            ['title' => $item->title, 'type' => 'url', 'url' => $item->url, 'active' => 1,
             'icon_image' => UploadedFile::fake()->image('logo.png', 64, 64)]
        );

        $path = $item->fresh()->icon_image;

        $this->actingAs($this->admin)->put(
            route('admin.menu_items.update', [$item->menu_id, $item->id]),
            ['title' => $item->title, 'type' => 'url', 'url' => $item->url, 'active' => 1,
             'remove_icon_image' => 1]
        )->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->icon_image);

        // Файл тоже обязан уйти: иначе storage копил бы картинки, на которые
        // уже никто не ссылается.
        Storage::disk('public')->assertMissing($path);
    }

    public function test_svg_is_rejected_as_an_icon_image(): void
    {
        Storage::fake('public');

        $item = $this->socialItem();

        // SVG исполняет скрипт и отдаётся с домена сайта — тот же запрет,
        // что действует в медиатеке.
        $this->actingAs($this->admin)->put(
            route('admin.menu_items.update', [$item->menu_id, $item->id]),
            ['title' => $item->title, 'type' => 'url', 'url' => $item->url, 'active' => 1,
             'icon_image' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')]
        )->assertSessionHasErrors('icon_image');

        $this->assertNull($item->fresh()->icon_image);
    }

    public function test_uploaded_image_reaches_the_footer(): void
    {
        $item = $this->socialItem();
        $item->update(['icon_image' => 'menu-icons/own.png']);

        Menu::flushCache();

        $link = collect(social_links())->firstWhere('key', 'max');

        $this->assertNotNull($link);
        $this->assertNotNull($link['image'], 'Адрес картинки обязан дойти до подвала.');
        $this->assertStringContainsString('own.png', $link['image']);
    }

    // ── Контакты подвала ──────────────────────────────────────────────────

    public function test_contacts_come_from_the_menu_with_labels_by_scheme(): void
    {
        $menu = Menu::create(['title' => 'Контакты', 'position' => 'contacts', 'active' => true]);

        foreach ([
            ['mail@example.com', 'mailto:mail@example.com', 'mail'],
            ['+7 000 000-00-00', 'tel:+70000000000', 'phone'],
            ['Москва, Тверская, 7', 'https://maps.example/x', 'address'],
        ] as $i => [$title, $url, $kind]) {
            MenuItem::create([
                'menu_id' => $menu->id, 'title' => $title, 'url' => $url,
                'type' => 'url', 'order' => $i + 1, 'active' => true,
            ]);
        }

        Menu::flushCache();

        $links = contact_links();

        $this->assertCount(3, $links);

        // Подпись выводится из СХЕМЫ адреса, а не хранится отдельным полем.
        $this->assertSame('mail', $links[0]['kind']);
        $this->assertSame('phone', $links[1]['kind']);
        $this->assertSame('address', $links[2]['kind']);

        // Значок «уходит на чужой сайт» уместен только у адреса: почта и
        // телефон открываются приложением, а не новой вкладкой.
        $this->assertFalse($links[0]['external']);
        $this->assertFalse($links[1]['external']);
        $this->assertTrue($links[2]['external']);
    }

    /**
     * iPad в альбомной ориентации — ровно 1024 пикселя в ширину, и он
     * сенсорный. Пороги вьюхи стояли min-width:1024px (выпадашка по
     * наведению) и max-width:1023px (дерево раскрыто сразу), поэтому такой
     * планшет попадал в ветку НАВЕДЕНИЯ — а наведения на сенсорном экране
     * нет вовсе. Замер на трёхуровневом меню показывал 13 недоступных
     * пунктов из 13: подменю существовали в разметке и никогда не
     * показывались.
     *
     * Проверяем сам порог, а не отрисовку: браузера в тестах нет, а
     * ошибка была именно в границе — на единицу.
     */
    public function test_touch_breakpoint_covers_tablet_landscape(): void
    {
        // Вьюха молчит, пока в меню нет пунктов (гард isNotEmpty), поэтому
        // одно живое меню завести обязательно — иначе тест проверял бы
        // пустую строку и проходил при любых порогах.
        $menu = Menu::create(['title' => 'Шапка', 'position' => 'header', 'active' => true]);
        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'Главная', 'url' => '/',
            'type' => 'url', 'active' => true, 'order' => 0,
        ]);
        Menu::flushCache();

        $css = view('Menu::frontend.header')->render();
        $this->assertNotSame('', trim($css));

        // Сенсорная ветка обязана включать 1024 и низкий экран.
        $this->assertStringContainsString('(max-width:1024px), (max-height:500px)', $css);

        // Ветка наведения начинается строго ЗА сенсорной.
        $this->assertStringContainsString('(min-width:1025px) and (min-height:501px)', $css);

        // Старых границ остаться не должно — они и были ошибкой.
        $this->assertStringNotContainsString('(max-width:1023px)', $css);
        $this->assertStringNotContainsString('@media (min-width:1024px)', $css);
    }

    public function test_contacts_fall_back_while_the_menu_does_not_exist(): void
    {
        Menu::flushCache();

        // Пустой список — знак подвалу показать прежние три строки: обновление
        // с предыдущей версии не должно стирать контакты.
        $this->assertSame([], contact_links());
    }
}
