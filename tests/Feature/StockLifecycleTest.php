<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Склад по всему пути заказа: списание, возврат и повторное списание.
 *
 * Инвариант один и простой: **на складе плюс в живых заказах всегда одно и то
 * же количество**. Любой переход статуса, повторённый сколько угодно раз, не
 * должен его нарушать.
 */
class StockLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private News $товар;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            \Modules\Payments\Events\OrderCreated::class,
            \Modules\Payments\Events\OrderStatusChanged::class,
        ]);

        $this->товар = News::create([
            'title' => 'Товар', 'slug' => 'tovar-sklad',
            'content' => '<p>x</p>', 'template' => 'products',
            'published' => true, 'price' => 1000, 'stock' => 10,
        ]);
    }

    /** Заказ с одной строкой на $количество штук; товар уже списан. */
    private function заказ(int $количество = 3, string $статус = 'pending'): Order
    {
        $способ = PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['title' => 'Наличными', 'type' => 'offline', 'active' => true, 'commission' => 0],
        );

        $заказ = Order::create([
            'payment_method_id' => $способ->id,
            'status' => $статус,
            'customer_name' => 'Иван',
            'items_total' => 1000 * $количество, 'delivery_price' => 0,
            'commission' => 0, 'total' => 1000 * $количество,
        ]);

        OrderItem::create([
            'order_id' => $заказ->id, 'product_id' => $this->товар->id,
            'title' => 'Товар', 'qty' => $количество, 'price' => 1000,
        ]);

        $this->товар->decrement('stock', $количество);

        return $заказ;
    }

    private function остаток(): int
    {
        return (int) $this->товар->fresh()->stock;
    }

    /** Отмена возвращает товар в продажу. */
    public function test_cancelling_returns_stock(): void
    {
        $заказ = $this->заказ(3);
        $this->assertSame(7, $this->остаток());

        $заказ->update(['status' => 'cancelled']);

        $this->assertSame(10, $this->остаток(), 'Отмена не вернула товар');
    }

    /** Дважды подряд — товар возвращается ОДИН раз. */
    public function test_cancelling_twice_returns_stock_once(): void
    {
        $заказ = $this->заказ(3);

        $заказ->update(['status' => 'cancelled']);
        $заказ->fresh()->update(['status' => 'cancelled']);
        $заказ->fresh()->returnStockOnce();

        $this->assertSame(10, $this->остаток(), 'Товар вернулся дважды — склад раздулся');
    }

    /**
     * 🔴 Заказ ожил после отмены — товар снова за ним.
     *
     * Так бывает чаще, чем кажется: покупатель дооплатил после автоотмены по
     * сроку, банк прислал уведомление с задержкой, владелец вернул заказ в
     * работу руками. Раньше товар оставался на складе свободным и продавался
     * второй раз, хотя числился за живым заказом.
     */
    public function test_reviving_a_cancelled_order_takes_stock_back(): void
    {
        $заказ = $this->заказ(3);

        $заказ->update(['status' => 'cancelled']);
        $this->assertSame(10, $this->остаток());

        // Деньги пришли позже — заказ снова живой
        $заказ->fresh()->update(['status' => 'completed']);

        $this->assertSame(7, $this->остаток(), 'Оживший заказ не забрал товар обратно');
        $this->assertNull($заказ->fresh()->stock_returned_at, 'Отметка возврата осталась стоять');
    }

    /**
     * Качели «отменить → вернуть → отменить» склад не сдвигают.
     *
     * Это и есть проверка инварианта: сколько бы раз статус ни менялся,
     * остаток зависит только от того, живой заказ сейчас или нет.
     */
    public function test_status_ping_pong_does_not_drift_the_stock(): void
    {
        $заказ = $this->заказ(3);

        foreach (['cancelled', 'completed', 'cancelled', 'processing', 'cancelled'] as $статус) {
            $заказ->fresh()->update(['status' => $статус]);
        }

        $this->assertSame(10, $this->остаток(), 'Заказ отменён — товар должен быть в продаже');

        $заказ->fresh()->update(['status' => 'paid']);

        $this->assertSame(7, $this->остаток(), 'Заказ живой — товар должен числиться за ним');
    }

    /** Живой заказ, которого не отменяли, склад не трогает при смене статуса. */
    public function test_status_change_of_a_live_order_does_not_touch_stock(): void
    {
        $заказ = $this->заказ(3);

        $заказ->update(['status' => 'paid']);
        $заказ->fresh()->update(['status' => 'processing']);
        $заказ->fresh()->update(['status' => 'completed']);

        $this->assertSame(7, $this->остаток(), 'Смена статуса живого заказа сдвинула склад');
    }

    /**
     * ⚠️ Пустой остаток (услуга) не превращается в число.
     *
     * `NULL` здесь значит «склад не ведём», а не «ноль». Иначе у услуги
     * появилась бы плашка «осталось N».
     */
    public function test_service_without_stock_is_left_alone(): void
    {
        $услуга = News::create([
            'title' => 'Услуга', 'slug' => 'usluga',
            'content' => '<p>x</p>', 'template' => 'ourworks',
            'published' => true, 'price' => 5000, 'stock' => null,
        ]);

        $способ = PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['title' => 'Наличными', 'type' => 'offline', 'active' => true, 'commission' => 0],
        );

        $заказ = Order::create([
            'payment_method_id' => $способ->id, 'status' => 'pending',
            'customer_name' => 'Иван',
            'items_total' => 5000, 'delivery_price' => 0, 'commission' => 0, 'total' => 5000,
        ]);

        OrderItem::create([
            'order_id' => $заказ->id, 'product_id' => $услуга->id,
            'title' => 'Услуга', 'qty' => 1, 'price' => 5000,
        ]);

        $заказ->update(['status' => 'cancelled']);
        $this->assertNull($услуга->fresh()->stock, 'У услуги появился остаток после отмены');

        $заказ->fresh()->update(['status' => 'completed']);
        $this->assertNull($услуга->fresh()->stock, 'У услуги появился остаток после оживления');
    }

    /**
     * 🔴 Оформление блокирует строку товара.
     *
     * Проверяется не сама гонка (её в один поток не воспроизвести), а то, что
     * запрос за товаром идёт с блокировкой: без неё двое покупателей на
     * последний экземпляр проходят проверку остатка одновременно, и товар
     * продаётся дважды.
     */
    public function test_checkout_locks_the_product_row(): void
    {
        $исходник = file_get_contents(
            base_path('modules/Payments/Controllers/Frontend/CartController.php')
        );

        // ⚠️ Ищем ТОЧНОЕ выражение по всему файлу, а не в окне от
        // `DB::transaction`. Окно уже дважды ломалось от роста списка
        // переменных в замыкании — проверка падала, хотя блокировка на месте.
        $this->assertStringContainsString(
            "News::whereKey(\$item['id'])->lockForUpdate()",
            $исходник,
            'Товар выбирается без блокировки — двое покупателей купят последний экземпляр'
        );

        $this->assertStringNotContainsString(
            'News::findOrFail($item[\'id\'])',
            $исходник,
            'Вернулась выборка без блокировки'
        );
    }

    /** Удаление заказа тоже возвращает товар — и тоже один раз. */
    public function test_deleting_an_order_returns_stock_once(): void
    {
        $заказ = $this->заказ(3);

        $заказ->update(['status' => 'cancelled']);
        $this->assertSame(10, $this->остаток());

        $заказ->fresh()->delete();

        $this->assertSame(10, $this->остаток(), 'Удаление отменённого заказа вернуло товар второй раз');
    }

    /**
     * Удаление ЖИВОГО заказа возвращает товар — из любого места кода.
     *
     * Возврат перенесён в событие модели: раньше он жил только в карточке
     * заказа, и удаление из массового действия, чистки или своей интеграции
     * оставляло товар списанным навсегда.
     */
    public function test_deleting_a_live_order_returns_stock(): void
    {
        $заказ = $this->заказ(3);
        $this->assertSame(7, $this->остаток());

        // Прямое удаление модели, минуя контроллер панели
        $заказ->delete();

        $this->assertSame(10, $this->остаток(), 'Удаление живого заказа не вернуло товар');
    }
}
