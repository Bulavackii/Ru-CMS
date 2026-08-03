<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Правка материала должна быть видна на сайте сразу.
 *
 * Главная держит каждый блок в кеше пять минут, а сохранение материала кеш
 * не сбрасывало: изменение доезжало с задержкой, и выглядело это как
 * «в панели сохранилось, а на сайте нет».
 */
class HomeCacheFreshnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_material_bumps_the_content_version(): void
    {
        $before = News::contentVersion();

        News::create([
            'title' => 'Обзор', 'content' => 'Текст.', 'slug' => 'obzor-cache',
            'template' => 'gaming', 'rating' => 7, 'published' => true,
        ]);

        $this->assertGreaterThan($before, News::contentVersion());
    }

    public function test_edited_rating_appears_on_the_site_immediately(): void
    {
        $news = News::create([
            'title' => '⭐ Обзор игры', 'content' => 'Текст обзора.', 'slug' => 'obzor-live',
            'template' => 'gaming', 'rating' => 7.0, 'published' => true,
        ]);

        // Первый заход кладёт блок в кеш.
        $this->get('/')->assertOk()->assertSee('>7<', false);

        $news->rating = 9.4;
        $news->save();

        // Без версии в ключе тут ещё пять минут показывалась бы семёрка.
        $this->get('/')->assertOk()->assertSee('>9.4<', false)->assertDontSee('>7<', false);
    }

    public function test_deleting_a_material_also_refreshes_the_site(): void
    {
        $news = News::create([
            'title' => 'Временная новость', 'content' => 'Текст.', 'slug' => 'vremennaya',
            'template' => 'default', 'published' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Временная новость', false);

        $news->delete();

        $this->get('/')->assertOk()->assertDontSee('Временная новость', false);
    }
}
