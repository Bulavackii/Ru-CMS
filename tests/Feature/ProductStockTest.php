<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Остаток товара с учётом корзины.
 *
 * Корзина склад не трогает — он списывается только при оформлении заказа.
 * Поэтому «сырой» остаток из базы после добавления в корзину не менялся, и
 * покупатель мог набрать больше, чем есть, узнав об этом уже на оформлении.
 */
class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    private function product(?int $stock = 10): News
    {
        return News::create([
            'title' => 'Кружка', 'content' => 'Описание.', 'slug' => 'kruzhka-test',
            'template' => 'products', 'price' => 690, 'stock' => $stock, 'published' => true,
        ]);
    }

    public function test_stock_is_reduced_by_what_is_already_in_the_cart(): void
    {
        $product = $this->product(10);

        $this->getJson("/product/{$product->id}/stock")->assertOk()->assertJson(['stock' => 10]);

        $this->withSession(['cart' => [$product->id => ['qty' => 3]]])
            ->getJson("/product/{$product->id}/stock")
            ->assertOk()
            ->assertJson(['stock' => 7]);
    }

    public function test_stock_never_goes_negative(): void
    {
        $product = $this->product(2);

        $this->withSession(['cart' => [$product->id => ['qty' => 99]]])
            ->getJson("/product/{$product->id}/stock")
            ->assertOk()
            ->assertJson(['stock' => 0]);
    }

    public function test_product_without_stock_is_always_available(): void
    {
        // Остаток не задан — товар не должен вдруг оказаться распроданным.
        $product = $this->product(null);

        $this->withSession(['cart' => [$product->id => ['qty' => 5]]])
            ->getJson("/product/{$product->id}/stock")
            ->assertOk()
            ->assertJson(['stock' => null]);
    }

    public function test_another_product_in_the_cart_does_not_affect_this_one(): void
    {
        $product = $this->product(10);
        $other = News::create([
            'title' => 'Блокнот', 'content' => 'Описание.', 'slug' => 'bloknot-test',
            'template' => 'products', 'price' => 850, 'stock' => 5, 'published' => true,
        ]);

        $this->withSession(['cart' => [$other->id => ['qty' => 4]]])
            ->getJson("/product/{$product->id}/stock")
            ->assertOk()
            ->assertJson(['stock' => 10]);
    }
}
