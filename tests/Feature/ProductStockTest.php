<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Tests\TestCase;

/**
 * Остаток товара после покупки — в базе, в панели и на сайте.
 *
 * Проверка по прямому вопросу владельца: «если купил, корректно ли
 * корректируются остатки». Тестов по остаткам не было ни одного, хотя
 * ошибка здесь означает проданный дважды товар или потерянный склад.
 *
 * Нашлось две поломки, обе тихие:
 *
 *   1. `decrement()` НЕ поднимал версию кеша содержимого. Eloquent при
 *      увеличении/уменьшении поля дёргает только `updating`/`updated`, а
 *      модель слушала `saved` — блок главной жил своей жизнью ещё пять
 *      минут, и раскупленный товар показывался как «в наличии».
 *   2. Отмена заказа НЕ возвращала товар на склад: возврат жил только в
 *      удалении. Отменённый заказ списывал товар навсегда.
 */
class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    private function товар(int $остаток = 5): News
    {
        return News::create([
            'title'     => 'Наушники',
            'slug'      => 'naushniki-proba',
            'content'   => '<p>Описание</p>',
            'template'  => 'products',
            'published' => true,
            'price'     => 2990,
            'stock'     => $остаток,
        ]);
    }

    /**
     * Заказу нужны способ оплаты и доставки — колонки NOT NULL.
     *
     * Сидер `PaymentDeliverySeeder` здесь звать нельзя: он вставляет около
     * двадцати настоящих записей и ломает подсчёты в соседних тестах (это
     * уже ловилось раньше). Заводим ровно по одной штуке.
     */
    private function заказ(News $товар, int $кол): Order
    {
        $оплата = \Modules\Payments\Models\PaymentMethod::firstOrCreate(
            ['code' => 'proba-cash'],
            ['title' => 'Наличные', 'type' => 'offline', 'active' => true]
        );

        $доставка = \Modules\Delivery\Models\DeliveryMethod::firstOrCreate(
            ['code' => 'proba-pickup'],
            ['title' => 'Самовывоз', 'type' => 'pickup', 'price' => 0, 'active' => true]
        );

        $order = Order::create([
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'total' => $товар->price * $кол, 'items_total' => $товар->price * $кол,
            'delivery_price' => 0, 'commission' => 0, 'status' => 'pending', 'is_new' => true,
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $товар->id,
            'title' => $товар->title, 'price' => $товар->price, 'qty' => $кол,
        ]);

        return $order->fresh('items');
    }

    /**
     * Списание остатка обязано сбросить кеш блоков главной.
     *
     * ⚠️ Этот тест на прежнем коде падал. Ключ кеша главной собирается из
     * `News::contentVersion()`, а `decrement()` его не двигал — сайт до пяти
     * минут показывал остаток, которого уже нет.
     */
    public function test_decrement_invalidates_the_content_cache(): void
    {
        $товар = $this->товар(5);

        $до = News::contentVersion();
        $товар->decrement('stock', 2);

        $this->assertSame(3, $товар->fresh()->stock);
        $this->assertNotSame($до, News::contentVersion(),
            'Списание остатка не сбросило кеш — на сайте останется прежнее число');
    }

    /** Возврат — та же история: увеличение поля тоже мимо `saved`. */
    public function test_increment_invalidates_the_content_cache(): void
    {
        $товар = $this->товар(5);

        $до = News::contentVersion();
        $товар->increment('stock', 2);

        $this->assertSame(7, $товар->fresh()->stock);
        $this->assertNotSame($до, News::contentVersion());
    }

    /** Страница товара показывает то, что в базе, а не что было. */
    public function test_product_page_shows_the_current_stock(): void
    {
        $товар = $this->товар(5);

        $this->get('/news/' . $товар->slug)->assertOk()->assertSee('>5<', false);

        $товар->decrement('stock', 4);

        $ответ = $this->get('/news/' . $товар->slug);
        $ответ->assertOk();
        $ответ->assertSee('>1<', false);
        $ответ->assertDontSee('>5<', false);
    }

    /**
     * Отмена заказа возвращает товар на склад.
     *
     * ⚠️ На прежнем коде остаток так и оставался списанным.
     */
    public function test_cancelling_an_order_returns_the_stock(): void
    {
        $админ = \App\Models\User::factory()->create(['is_admin' => true]);
        $товар = $this->товар(5);

        $товар->decrement('stock', 2);
        $заказ = $this->заказ($товар, 2);

        $this->assertSame(3, $товар->fresh()->stock);

        $this->actingAs($админ)
            ->put(route('admin.orders.update.status', $заказ->id), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame(5, $товар->fresh()->stock, 'Отмена не вернула товар на склад');
        $this->assertNotNull($заказ->fresh()->stock_returned_at);
    }

    /**
     * Возврат разовый: отменить, потом удалить — вернуть ОДИН раз.
     *
     * Без отметки склад вырос бы из ниоткуда: до починки возврат стоял в
     * удалении безусловно, и связка «отмена + удаление» дала бы +4 к пяти.
     */
    public function test_stock_is_returned_only_once(): void
    {
        $админ = \App\Models\User::factory()->create(['is_admin' => true]);
        $товар = $this->товар(5);

        $товар->decrement('stock', 2);
        $заказ = $this->заказ($товар, 2);

        $this->actingAs($админ)
            ->put(route('admin.orders.update.status', $заказ->id), ['status' => 'cancelled']);
        $this->assertSame(5, $товар->fresh()->stock);

        $this->actingAs($админ)->delete(route('admin.orders.destroy', $заказ->id));

        $this->assertSame(5, $товар->fresh()->stock,
            'Остаток вернулся дважды — склад вырос из ниоткуда');
    }

    /** Удаление заказа без отмены возвращает товар как раньше. */
    public function test_deleting_an_order_still_returns_the_stock(): void
    {
        $админ = \App\Models\User::factory()->create(['is_admin' => true]);
        $товар = $this->товар(5);

        $товар->decrement('stock', 2);
        $заказ = $this->заказ($товар, 2);

        $this->actingAs($админ)->delete(route('admin.orders.destroy', $заказ->id));

        $this->assertSame(5, $товар->fresh()->stock);
    }

    /**
     * Пустой остаток — это «не считаем», а не «ноль».
     *
     * У услуг и обычных материалов склада нет, и возврат не должен
     * превращать NULL в число: товар без учёта остатка стал бы товаром с
     * остатком, и на сайте появилась бы плашка «осталось N».
     */
    public function test_null_stock_is_left_alone(): void
    {
        $админ = \App\Models\User::factory()->create(['is_admin' => true]);

        $услуга = News::create([
            'title' => 'Установка', 'slug' => 'ustanovka-proba',
            'content' => '<p>Текст</p>', 'template' => 'ourworks',
            'published' => true, 'price' => 9000, 'stock' => null,
        ]);

        $заказ = $this->заказ($услуга, 1);

        $this->actingAs($админ)
            ->put(route('admin.orders.update.status', $заказ->id), ['status' => 'cancelled']);

        $this->assertNull($услуга->fresh()->stock);
    }
}
