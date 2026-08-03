<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Что появляется в базе сразу после установки.
 *
 * Шаг выбора демо-данных убран — содержимое ставится всегда, и владелец
 * должен увидеть готовый сайт, а не пустой.
 */
class FreshInstallContentTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): void
    {
        User::factory()->create(['is_admin' => true]);

        $controller = app(\Modules\Install\Controllers\InstallController::class);
        $method = new \ReflectionMethod($controller, 'installDemoData');
        $method->setAccessible(true);
        $method->invoke($controller);
    }

    public function test_content_is_seeded_and_split_by_template(): void
    {
        $this->seedDemo();

        $expected = [
            'default'  => 5,  // статьи про саму CMS
            'products' => 6,  // товары
            'magazine' => 4,  // журнальные материалы
            'clinic'   => 6,  // услуги клиники
            'gaming'   => 6,  // игровые материалы
        ];

        foreach ($expected as $template => $count) {
            $this->assertSame(
                $count,
                News::where('template', $template)->count(),
                "Шаблон {$template}: ожидалось {$count} материалов"
            );
        }

        // Всё опубликовано: черновики после установки никому не нужны.
        $this->assertSame(0, News::where('published', false)->count());
    }

    public function test_products_have_price_and_picture(): void
    {
        $this->seedDemo();

        foreach (News::where('template', 'products')->get() as $product) {
            $this->assertGreaterThan(0, (float) $product->price, "У товара «{$product->title}» нет цены");
            $this->assertStringContainsString('/images/products/', $product->content);
        }
    }

    public function test_review_has_rating_and_is_not_a_product(): void
    {
        $this->seedDemo();

        $review = News::where('slug', 'igry-obzor')->first();

        $this->assertNotNull($review);
        $this->assertSame('8.5', (string) $review->rating);

        // Оценка жила в поле «Цена», и обзор игры превращался в товар
        // за 8,50 ₽ с кнопкой «В корзину».
        $this->assertNull($review->price, 'У обзора не должно быть цены');
    }

    public function test_menus_and_categories_are_ready(): void
    {
        $this->seedDemo();

        // Шапка и боковая панель показывают один набор разделов.
        $header = DB::table('menus')->where('position', 'header')->value('id');
        $sidebar = DB::table('menus')->where('position', 'sidebar')->value('id');

        $this->assertNotNull($header);
        $this->assertNotNull($sidebar);

        $titles = fn ($menuId) => DB::table('menu_items')->where('menu_id', $menuId)
            ->orderBy('order')->pluck('title')->sort()->values()->all();

        $this->assertSame($titles($header), $titles($sidebar));

        $this->assertGreaterThanOrEqual(3, DB::table('categories')->count());
    }
}
