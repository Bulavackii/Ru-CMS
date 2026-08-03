<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/** Сквозная проверка: чистая база -> сидер -> живой рендер главной. */
class FreshInstallRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_is_ready_right_after_install(): void
    {
        User::factory()->create(['is_admin' => true]);

        $controller = app(\Modules\Install\Controllers\InstallController::class);
        $seed = new \ReflectionMethod($controller, 'installDemoData');
        $seed->setAccessible(true);
        $seed->invoke($controller);

        $html = $this->get('/')->assertOk()->getContent();

        // Оценки: плашка у каждого игрового материала, всегда с точкой.
        preg_match_all('~gm-card__score">([^<]+)~u', $html, $scores);

        $this->assertCount(
            News::where('template', 'gaming')->count(),
            $scores[1],
            'Плашка оценки есть не у всех игровых карточек'
        );

        foreach ($scores[1] as $score) {
            $this->assertMatchesRegularExpression('~^\d+\.\d$~', $score, "Оценка «{$score}» без десятых");
        }

        // Остальные блоки тоже на месте.
        $this->assertStringContainsString('gm-card', $html, 'Нет блока «Игры»');
        $this->assertStringContainsString('clinic-card', $html, 'Нет блока «Клиника»');
        $this->assertStringContainsString('mag-card', $html, 'Нет блока «Журнал»');
        $this->assertStringContainsString('/images/products/', $html, 'Нет картинок товаров');
    }

    public function test_news_page_groups_materials_and_keeps_template_fields(): void
    {
        User::factory()->create(['is_admin' => true]);

        $controller = app(\Modules\Install\Controllers\InstallController::class);
        $seed = new \ReflectionMethod($controller, 'installDemoData');
        $seed->setAccessible(true);
        $seed->invoke($controller);

        $html = $this->get('/news')->assertOk()->getContent();

        // Материалы разбиты по шаблонам, а не свалены в одну ленту.
        $this->assertMatchesRegularExpression('~(gm|clinic|mag|nw)__title~', $html);

        // Выборка перечисляет колонки поимённо: без rating плашка оценки
        // не появлялась, без price — цена у товаров.
        preg_match_all('~gm-card__score">([^<]+)~u', $html, $scores);
        $this->assertNotEmpty($scores[1], 'На странице новостей нет оценок у игровых карточек');

        foreach ($scores[1] as $score) {
            $this->assertMatchesRegularExpression('~^\d+\.\d$~', $score);
        }
    }
}
