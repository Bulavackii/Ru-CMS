<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * 🔴 Массовые действия обязаны поднимать события модели.
 *
 * `News::whereIn('id', $ids)->delete()` и `->update()` — это запросы
 * построителя: события модели они НЕ поднимают. А на них держатся сброс
 * версии кеша содержимого и синхронизация SEO.
 *
 * Что это давало в жизни:
 *   • у трёх материалов, удалённых пачкой, SEO-записи остались — раздел SEO
 *     и карта сайта копили мёртвые адреса (найдено обходом ссылок в базе
 *     владельца: `/news/proba-sobytiy` вёл в 404);
 *   • опубликованная пачкой новость не появлялась на главной до пяти минут,
 *     потому что версия ключа кеша не менялась.
 */
class BulkActionsEventsTest extends TestCase
{
    use RefreshDatabase;

    private function материал(string $слаг): News
    {
        return News::create([
            'title' => 'Материал ' . $слаг,
            'slug' => $слаг,
            'content' => '<p>Текст</p>',
            'template' => 'default',
            'published' => false,
        ]);
    }

    /** Массовое удаление меняет версию кеша содержимого. */
    public function test_bulk_delete_bumps_content_version(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $первый = $this->материал('massovoe-udalenie-1');
        $второй = $this->материал('massovoe-udalenie-2');

        $версияДо = News::contentVersion();

        $this->post(route('admin.news.bulk'), [
            'action' => 'delete',
            'selected' => [$первый->id, $второй->id],
        ]);

        $this->assertNull(News::find($первый->id), 'Материал не удалился');
        $this->assertNull(News::find($второй->id), 'Материал не удалился');

        $this->assertGreaterThan(
            $версияДо,
            News::contentVersion(),
            'Версия кеша не изменилась — удалённые материалы останутся на главной'
        );
    }

    /** Массовая публикация меняет версию кеша содержимого. */
    public function test_bulk_publish_bumps_content_version(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $первый = $this->материал('massovaya-publikaciya-1');
        $второй = $this->материал('massovaya-publikaciya-2');

        $версияДо = News::contentVersion();

        $this->post(route('admin.news.bulk'), [
            'action' => 'publish',
            'selected' => [$первый->id, $второй->id],
        ]);

        $this->assertTrue((bool) $первый->fresh()->published, 'Материал не опубликовался');
        $this->assertTrue((bool) $второй->fresh()->published, 'Материал не опубликовался');

        $this->assertGreaterThan(
            $версияДо,
            News::contentVersion(),
            'Версия кеша не изменилась — материал не появится на главной сразу'
        );
    }

    /** Массовое снятие с публикации — то же самое в обратную сторону. */
    public function test_bulk_unpublish_bumps_content_version(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $материал = $this->материал('massovoe-snyatie');
        $материал->update(['published' => true]);

        $версияДо = News::contentVersion();

        $this->post(route('admin.news.bulk'), [
            'action' => 'unpublish',
            'selected' => [$материал->id],
        ]);

        $this->assertFalse((bool) $материал->fresh()->published);
        $this->assertGreaterThan($версияДо, News::contentVersion());
    }

    /**
     * Страница массовой правки без выбранных материалов не падает.
     *
     * Раньше `explode(',', '')` давал массив с пустой строкой, запрос уходил
     * как `where id in ('')`, и база отвечала «неверный синтаксис для типа
     * bigint» — прямой заход на адрес отдавал пятисотку.
     */
    public function test_bulk_edit_without_selection_redirects(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('admin.news.bulk.edit'))
            ->assertRedirect(route('admin.news.index'));
    }
}
