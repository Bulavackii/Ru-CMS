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

        // Страница набирается ЦЕЛЫМИ группами, поэтому шаблоны разъезжаются
        // по страницам — собираем разметку со всех.
        $pages = [];

        for ($page = 1; $page <= 4; $page++) {
            $pages[$page] = $this->get('/news?page=' . $page)->assertOk()->getContent();
        }

        $all = implode('', $pages);

        // Материалы разбиты по шаблонам, а не свалены в одну ленту.
        $this->assertMatchesRegularExpression('~(gm|clinic|mag|nw)__title~', $all);

        // Выборка перечисляет колонки поимённо: без rating плашка оценки
        // не появлялась, без price — цена у товаров.
        preg_match_all('~gm-card__score">([^<]+)~u', $all, $scores);
        $this->assertNotEmpty($scores[1], 'Ни на одной странице новостей нет оценок у игровых карточек');

        foreach ($scores[1] as $score) {
            $this->assertMatchesRegularExpression('~^\d+\.\d$~', $score);
        }
    }

    /**
     * Группа шаблона не должна разрываться между страницами.
     *
     * Раньше постранично резался плоский список, а группировка делалась во
     * вьюхе — раздел из пяти материалов оказывался разложен на две страницы
     * (два и три), что читателю выглядело случайным разрывом.
     */
    public function test_news_pages_never_split_a_template_group(): void
    {
        User::factory()->create(['is_admin' => true]);

        $controller = app(\Modules\Install\Controllers\InstallController::class);
        $seed = new \ReflectionMethod($controller, 'installDemoData');
        $seed->setAccessible(true);
        $seed->invoke($controller);

        $bySlug = \Modules\News\Models\News::published()->get(['slug', 'template'])
            ->groupBy(fn ($n) => filled($n->template) ? $n->template : 'default');

        $pages = [];

        for ($page = 1; $page <= 6; $page++) {
            $pages[$page] = $this->get('/news?page=' . $page)->assertOk()->getContent();
        }

        foreach ($bySlug as $template => $items) {
            $found = [];

            foreach ($pages as $page => $html) {
                $count = $items->filter(fn ($n) => str_contains($html, '/news/' . $n->slug . '"'))->count();

                if ($count > 0) {
                    $found[$page] = $count;
                }
            }

            $this->assertNotEmpty($found, "Группа «{$template}» не выведена ни на одной странице");
            $this->assertCount(
                1,
                $found,
                "Группа «{$template}» разорвана между страницами: " . json_encode($found)
            );
            $this->assertSame(
                $items->count(),
                reset($found),
                "Группа «{$template}» выведена не полностью"
            );
        }
    }

    public function test_install_leaves_the_site_usable(): void
    {
        // Сверка чистой установки с боевым сайтом показала четыре пробела:
        // не было ни одной SEO-записи, настройки спецвозможностей не
        // создавались вовсе (виджет не появлялся), а все способы оплаты и
        // доставки приходили выключенными — заказ оформить было нечем.
        User::factory()->create(['is_admin' => true]);

        $controller = app(\Modules\Install\Controllers\InstallController::class);

        foreach ([
            'seedDefaultMenu', 'seedDefaultPaymentMethods', 'seedDefaultDeliveryMethods',
            'seedDefaultPages', 'installDemoData', 'seedDefaultCategories',
            'seedDefaultAccessibility', 'seedSeoPages',
        ] as $step) {
            $method = new \ReflectionMethod($controller, $step);
            $method->setAccessible(true);
            $method->invoke($controller);
        }

        // Оформить заказ есть чем.
        $this->assertGreaterThan(
            0,
            \Modules\Payments\Models\PaymentMethod::where('active', true)->count(),
            'После установки нет ни одного способа оплаты'
        );

        $this->assertGreaterThan(
            0,
            \Modules\Delivery\Models\DeliveryMethod::where('active', true)->count(),
            'После установки нет ни одного способа доставки'
        );

        // Ключи при этом не заведены ни у кого — репозиторий публичный.
        foreach (\Modules\Payments\Models\PaymentMethod::all() as $method) {
            foreach ((array) $method->settings as $key => $value) {
                $this->assertSame('', $value, "У метода {$method->code} заполнен ключ {$key}");
            }
        }

        // Виджет спецвозможностей включён, а не заводится лениво выключенным.
        $this->assertTrue(
            (bool) \Modules\Accessibility\Models\AccessibilitySetting::settings()->enabled,
            'Виджет спецвозможностей выключен после установки'
        );

        // Раздел SEO описывает созданное содержимое.
        $this->assertGreaterThan(
            0,
            \Modules\Seo\Models\SeoPage::count(),
            'После установки нет ни одной SEO-записи'
        );
    }
}
