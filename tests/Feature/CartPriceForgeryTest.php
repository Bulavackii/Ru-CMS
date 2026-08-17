<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * 🔴 Подмена цены при оформлении заказа.
 *
 * Оформление читало состав корзины из ЗАПРОСА — вместе с названием и ценой.
 * Значит, покупателю достаточно было отправить `items[0][price]=1`, чтобы
 * купить товар за рубль: и заказ, и сумма, и платёж в платёжной системе
 * считались по присланному числу. Браузерная форма не источник правды —
 * её содержимое подменяется из инструментов разработчика за секунду.
 *
 * Теперь из запроса берутся только идентификатор и количество, всё
 * остальное — из базы.
 */
class CartPriceForgeryTest extends TestCase
{
    use RefreshDatabase;

    private function обстановка(): array
    {
        $товар = News::create([
            'title' => 'Дорогой товар',
            'slug' => 'dorogoy-tovar',
            'content' => '<p>Описание</p>',
            'template' => 'products',
            'published' => true,
            'price' => 100000,
            'stock' => 3,
        ]);

        $оплата = PaymentMethod::create([
            'title' => 'Наличными', 'code' => 'cash', 'active' => true, 'commission' => 0,
        ]);

        $доставка = DeliveryMethod::create([
            'title' => 'Курьер', 'code' => 'courier_local',
            'active' => true, 'price' => 0, 'type' => 'courier',
        ]);

        return [$товар, $оплата, $доставка];
    }

    /** Присланная покупателем цена игнорируется — берётся цена из базы. */
    public function test_forged_price_is_ignored(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        [$товар, $оплата, $доставка] = $this->обстановка();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
            'items' => [
                ['id' => $товар->id, 'title' => 'Дорогой товар', 'price' => 1, 'qty' => 1],
            ],
        ]);

        $заказ = Order::latest('id')->first();
        $this->assertNotNull($заказ, 'Заказ не создался');

        $строка = $заказ->items()->first();

        $this->assertEqualsWithDelta(
            100000.0,
            (float) $строка->price,
            0.01,
            'В заказ попала цена из запроса — товар можно купить за рубль'
        );

        $this->assertEqualsWithDelta(
            100000.0,
            (float) $заказ->items_total,
            0.01,
            'Сумма заказа посчитана по присланной цене'
        );
    }

    /** Название товара тоже берётся из базы, а не из запроса. */
    public function test_forged_title_is_ignored(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        [$товар, $оплата, $доставка] = $this->обстановка();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
            'items' => [
                ['id' => $товар->id, 'title' => '<img src=x onerror=alert(1)>', 'price' => 100000, 'qty' => 1],
            ],
        ]);

        $строка = Order::latest('id')->first()->items()->first();

        $this->assertSame(
            'Дорогой товар',
            $строка->title,
            'В заказ попало название из запроса — в карточку заказа можно подсунуть чужую разметку'
        );
    }

    /** Снятый с публикации товар в заказ не проходит. */
    public function test_unpublished_product_cannot_be_ordered(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        [$товар, $оплата, $доставка] = $this->обстановка();
        $товар->update(['published' => false]);

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
            'items' => [['id' => $товар->id, 'price' => 100000, 'qty' => 1]],
        ]);

        $this->assertNull(Order::latest('id')->first(), 'Снятый с публикации товар удалось заказать');
        $this->assertSame(3, $товар->fresh()->stock, 'Остаток тронут у неопубликованного товара');
    }

    /** Отрицательное и нулевое количество не создают заказ. */
    public function test_bad_quantity_is_rejected(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        [$товар, $оплата, $доставка] = $this->обстановка();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
            'items' => [['id' => $товар->id, 'price' => 100000, 'qty' => -5]],
        ]);

        $this->assertNull(Order::latest('id')->first(), 'Заказ с отрицательным количеством создался');
        $this->assertSame(3, $товар->fresh()->stock, 'Остаток изменился от отрицательного количества');
    }
}
