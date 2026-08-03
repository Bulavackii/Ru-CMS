<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Страница корзины.
 */
class CartPageTest extends TestCase
{
    use RefreshDatabase;

    private function product(): News
    {
        return News::create([
            'title' => 'Кружка', 'content' => 'Описание.', 'slug' => 'kruzhka-cart',
            'template' => 'products', 'price' => 690, 'stock' => 10, 'published' => true,
        ]);
    }

    /** Способы оплаты и доставки: без них списки в корзине не рендерятся. */
    private function methods(): void
    {
        PaymentMethod::create([
            'title' => 'Наличные', 'code' => 'cash', 'type' => 'offline', 'active' => true,
        ]);

        DeliveryMethod::create([
            'title' => 'Самовывоз', 'code' => 'pickup', 'price' => 0, 'active' => true,
        ]);
    }

    private function cartWith(News $product, int $qty = 2): array
    {
        return ['cart' => [$product->id => [
            'id' => $product->id, 'title' => $product->title,
            'price' => $product->price, 'qty' => $qty,
        ]]];
    }

    public function test_delete_button_skips_form_validation(): void
    {
        // Кнопка удаления лежит внутри формы оформления, где способ оплаты и
        // доставки помечены required. Без formnovalidate браузер требовал
        // выбрать их, чтобы... удалить товар из корзины.
        $product = $this->product();

        $this->withSession($this->cartWith($product))
            ->get('/cart')
            ->assertOk()
            ->assertSee('formnovalidate', false);
    }

    public function test_item_can_be_removed(): void
    {
        $product = $this->product();

        $this->withSession($this->cartWith($product))
            ->post('/cart/remove', ['id' => $product->id])
            ->assertRedirect();

        $this->assertEmpty(session('cart', []));
    }

    public function test_selects_and_script_reference_the_same_elements(): void
    {
        // Однажды разметку перевели на радиокнопки, а скрипт продолжал искать
        // <select id="delivery-method">. Переменная была null, первый же вызов
        // падал с TypeError, скрипт умирал целиком — и «Всего к оплате»
        // навсегда оставалось 0,00 при непустой корзине. Проверяем, что
        // разметка и скрипт снова говорят об одних и тех же элементах.
        $product = $this->product();
        $this->methods();

        $html = $this->withSession($this->cartWith($product))->get('/cart')->assertOk()->getContent();

        foreach (['payment-method', 'delivery-method'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "В разметке нет select #{$id}");
            $this->assertStringContainsString("getElementById('" . $id . "')", $html, "Скрипт не читает #{$id}");
        }
    }

    public function test_goods_total_is_rendered_by_the_server(): void
    {
        $product = $this->product();

        // 690 x 2 = 1380 — сумма не должна зависеть от того, отработал ли JS.
        $this->withSession($this->cartWith($product))
            ->get('/cart')
            ->assertOk()
            ->assertSee('1 380,00', false);
    }

    public function test_empty_cart_opens(): void
    {
        $this->get('/cart')->assertOk();
    }
}
