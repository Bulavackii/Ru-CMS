<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Корзина в шапке показывается, только когда на сайте есть что купить.
 *
 * ⚠️ Признак считался в ДВУХ местах (шапка сайта и фрагмент оформления) и в
 * обоих БЕЗ проверки публикации: черновик товара — материал, который
 * посетитель открыть не может, — всё равно зажигал корзину, и она вела в
 * пустой магазин. Теперь признак один: site_has_products().
 */
class CartVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function товар(bool $опубликован): News
    {
        return News::create([
            'title'     => 'Настольная лампа',
            'slug'      => 'lampa-' . ($опубликован ? 'pub' : 'draft'),
            'content'   => '<p>Три уровня яркости.</p>',
            'template'  => 'products',
            'price'     => 2790,
            'published' => $опубликован,
        ]);
    }

    public function test_cart_is_hidden_without_products(): void
    {
        News::create([
            'title' => 'Обычная новость', 'slug' => 'novost',
            'content' => '<p>Текст.</p>', 'template' => 'default', 'published' => true,
        ]);

        $this->assertFalse(site_has_products());

        $this->get('/')->assertOk()->assertDontSee(route('cart.index'), false);
    }

    public function test_cart_appears_with_a_published_product(): void
    {
        $this->товар(true);

        $this->assertTrue(site_has_products());

        $this->get('/')->assertOk()->assertSee(route('cart.index'), false);
    }

    /** Черновик покупателю недоступен — значит и корзине неоткуда взяться. */
    public function test_draft_product_does_not_light_the_cart(): void
    {
        $this->товар(false);

        $this->assertFalse(
            site_has_products(),
            'Неопубликованный товар зажёг корзину — купить в ней нечего'
        );
    }
}
