<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Modules\Seo\Console\Commands\CleanOrphanSeoPagesCommand;
use Modules\Seo\Models\SeoPage;
use Tests\TestCase;

/**
 * Осиротевшие SEO-записи — описания материалов, которых больше нет.
 *
 * Источник дефекта закрыт (массовые действия переведены на модель, см.
 * BulkActionsEventsTest), но записи, накопленные до починки, остаются в базе
 * и продолжают показывать в разделе SEO и в карте сайта мёртвые адреса.
 *
 * ⚠️ Проверяем СТАТИЧЕСКИЙ метод, а не вызов через artisan: провайдер модуля
 * регистрируется только у активных модулей, а в тестах таблица `modules`
 * пуста — команды по имени там просто нет. Тот же приём, что в
 * OrderNewStatusTest с автоотменой заказов.
 */
class OrphanSeoPagesTest extends TestCase
{
    use RefreshDatabase;

    private function запись(string $слаг, ?string $тип, ?int $источник): SeoPage
    {
        $з = new SeoPage([
            'title' => 'Описание ' . $слаг,
            'source_type' => $тип,
            'source_id' => $источник,
        ]);

        // slug намеренно не в fillable — ставится явно (см. модель).
        $з->slug = $слаг;
        $з->save();

        return $з;
    }

    /** Запись без материала находится, запись с материалом — нет. */
    public function test_orphans_are_found_and_live_records_are_not(): void
    {
        $материал = News::create([
            'title' => 'Живой материал', 'slug' => 'zhivoy-material',
            'content' => '<p>x</p>', 'template' => 'default', 'published' => true,
        ]);

        // Событие модели уже завело описание — берём его, а не плодим второе.
        $живая = SeoPage::where('source_type', 'news')->where('source_id', $материал->id)->first()
            ?? $this->запись('/news/zhivoy-material', 'news', $материал->id);

        $сирота = $this->запись('/news/proba-sobytiy', 'news', 999999);

        $найдено = CleanOrphanSeoPagesCommand::найти()->pluck('id')->all();

        $this->assertContains($сирота->id, $найдено, 'Запись без материала не нашлась');
        $this->assertNotContains($живая->id, $найдено, 'Под чистку попала живая запись');
    }

    /**
     * Запись без источника не трогаем.
     *
     * Такие заводят руками под произвольный адрес — раздел, фильтр, лендинг.
     * Материала за ними не стоит ПО ЗАМЫСЛУ, и удалять их нельзя.
     */
    public function test_handmade_records_are_left_alone(): void
    {
        $ручная = $this->запись('/uslugi', null, null);

        $this->assertNotContains(
            $ручная->id,
            CleanOrphanSeoPagesCommand::найти()->pluck('id')->all(),
            'Под чистку попала запись, заведённая руками'
        );
    }

    /**
     * Удаление материала через модель описание уносит само.
     *
     * Это и есть настоящая починка; команда нужна только для того, что
     * накопилось до неё.
     */
    public function test_deleting_a_material_removes_its_seo_record(): void
    {
        $материал = News::create([
            'title' => 'На удаление', 'slug' => 'na-udalenie',
            'content' => '<p>x</p>', 'template' => 'default', 'published' => true,
        ]);

        $материал->delete();

        $this->assertEmpty(
            CleanOrphanSeoPagesCommand::найти()->all(),
            'После удаления материала осталась осиротевшая SEO-запись'
        );
    }
}
