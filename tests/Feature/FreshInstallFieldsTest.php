<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\News\Controllers\Admin\NewsController;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Что доступно сразу после установки: колонки, шаблоны, файлы шаблонов.
 *
 * RefreshDatabase прогоняет те же миграции, что мастер установки вызывает
 * через Artisan::call('migrate'), — то есть состояние совпадает с чистой
 * установкой.
 */
class FreshInstallFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_table_has_all_content_columns(): void
    {
        foreach (['price', 'stock', 'is_promo', 'rating', 'template', 'slug'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('news', $column),
                "После установки у материалов нет колонки {$column}"
            );
        }
    }

    public function test_every_listed_template_has_a_file(): void
    {
        // Шаблон без файла молча не отрисуется на сайте: редактор выберет
        // его в форме, а блок не появится.
        foreach (NewsController::TEMPLATES as $key => $label) {
            $this->assertFileExists(
                resource_path("views/frontend/templates/{$key}.blade.php"),
                "Шаблон «{$label}» ({$key}) есть в списке, но файла нет"
            );
        }
    }

    public function test_template_validation_accepts_every_listed_template(): void
    {
        // Четвёртая копия списка уже расходилась и не пропускала новые
        // шаблоны — проверяем, что правило строится из той же константы.
        $rules = (new \App\Http\Requests\NewsRequest())->rules();

        foreach (array_keys(NewsController::TEMPLATES) as $key) {
            $this->assertStringContainsString(
                $key,
                $rules['template'],
                "Шаблон {$key} не проходит валидацию"
            );
        }
    }

    public function test_rating_field_is_editable_through_the_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/news/create')
            ->assertOk()
            ->assertSee('name="rating"', false);

        $news = News::create([
            'title' => 'Обзор', 'content' => 'Текст.', 'slug' => 'obzor-fresh',
            'template' => 'gaming', 'rating' => 7.5, 'published' => true,
        ]);

        $this->actingAs($admin)->get('/admin/news/' . $news->id . '/edit')
            ->assertOk()
            ->assertSee('name="rating"', false);
    }
}
