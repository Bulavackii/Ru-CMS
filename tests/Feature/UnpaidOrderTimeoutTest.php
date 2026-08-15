<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Console\Commands\CancelUnpaidOrdersCommand;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Отмена заказов, за которые не заплатили в срок.
 *
 * Товар списывается со склада в момент оформления, а не оплаты. Брошенный
 * заказ держал его за собой бесконечно: покупатель ушёл с платёжной
 * страницы, а позиция на сайте так и числилась проданной, пока владелец не
 * найдёт заказ в панели руками.
 *
 * Самое опасное здесь — не «не сработало», а «сработало не на том»: отмена
 * заказа с оплатой наличными через десять минут после оформления была бы
 * куда хуже исходной беды. Поэтому охват проверяется отдельными тестами с
 * обеих сторон.
 */
class UnpaidOrderTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private function товар(int $остаток = 5): News
    {
        return News::create([
            'title' => 'Наушники', 'slug' => 'naushniki-srok',
            'content' => '<p>Описание</p>', 'template' => 'products',
            'published' => true, 'price' => 2990, 'stock' => $остаток,
        ]);
    }

    private function способОплаты(string $тип): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['code' => 'proba-' . $тип],
            ['title' => 'Проба ' . $тип, 'type' => $тип, 'active' => true]
        );
    }

    /**
     * Заказ в состоянии «оформлен, но не оплачен».
     *
     * @param int $минутНазад сколько минут назад он создан
     */
    private function заказ(News $товар, string $типОплаты, int $минутНазад, int $кол = 2): Order
    {
        $доставка = DeliveryMethod::firstOrCreate(
            ['code' => 'proba-pickup'],
            ['title' => 'Самовывоз', 'type' => 'pickup', 'price' => 0, 'active' => true]
        );

        $order = Order::create([
            'payment_method_id' => $this->способОплаты($типОплаты)->id,
            'delivery_method_id' => $доставка->id,
            'total' => $товар->price * $кол, 'items_total' => $товар->price * $кол,
            'delivery_price' => 0, 'commission' => 0,
            'status' => 'pending', 'is_new' => true,
            'customer_email' => 'pokupatel@example.test',
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $товар->id,
            'title' => $товар->title, 'price' => $товар->price, 'qty' => $кол,
        ]);

        // Товар списан оформлением заказа — воспроизводим это состояние.
        $товар->decrement('stock', $кол);

        // ⚠️ Дату ставим запросом, а не через модель: у Order обновление
        // трогает updated_at и подняло бы событие смены статуса.
        Order::withoutEvents(fn () => Order::whereKey($order->id)
            ->update(['created_at' => now()->subMinutes($минутНазад)]));

        return $order->fresh('items');
    }

    /** Просроченный заказ отменяется, товар возвращается в продажу. */
    public function test_unpaid_order_is_cancelled_and_stock_returned(): void
    {
        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'online', 11);

        $this->assertSame(3, $товар->fresh()->stock);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $заказ->refresh();
        $this->assertSame('cancelled', $заказ->status);
        $this->assertSame(CancelUnpaidOrdersCommand::REASON, $заказ->cancel_reason);
        $this->assertNotNull($заказ->stock_returned_at);
        $this->assertSame(5, $товар->fresh()->stock, 'Товар не вернулся в продажу');
    }

    /** Свежий заказ не трогаем: покупатель ещё на странице оплаты. */
    public function test_fresh_order_is_left_alone(): void
    {
        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'online', 3);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $this->assertSame('pending', $заказ->fresh()->status);
        $this->assertSame(3, $товар->fresh()->stock);
    }

    /**
     * 🔴 Наличные и счёт по таймеру НЕ отменяются.
     *
     * «В ожидании» у них значит «ждём покупателя», а не «ждём платёжную
     * систему»: такой заказ живёт днями и это нормально. Отмена его через
     * десять минут была бы хуже той беды, ради которой всё затевалось.
     */
    public function test_offline_payment_is_never_cancelled_by_timeout(): void
    {
        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'offline', 600);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $this->assertSame('pending', $заказ->fresh()->status);
        $this->assertSame(3, $товар->fresh()->stock);
    }

    /** Оплаченный заказ таймер не трогает — статус уже не «в ожидании». */
    public function test_paid_order_is_left_alone(): void
    {
        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'online', 600);

        Order::withoutEvents(fn () => Order::whereKey($заказ->id)->update(['status' => 'paid']));

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $this->assertSame('paid', $заказ->fresh()->status);
        $this->assertSame(3, $товар->fresh()->stock);
    }

    /**
     * Письма, ушедшие покупателю (а не админам).
     *
     * ⚠️ Считать ВСЕ письма покупателю нельзя, и первая версия теста на
     * этом споткнулась: создание заказа само по себе шлёт покупателю
     * подтверждение. В ящике законно лежат два письма — «заказ оформлен» и
     * «заказ отменён», — поэтому отбираем по теме, а не по адресату.
     *
     * ⚠️ Транспорт `array` отдаёт SentMessage, а не Email: адрес и тему
     * достаёт getOriginalMessage().
     */
    private function письмаОбОтмене(string $адрес): array
    {
        return collect(Mail::mailer('array')->getSymfonyTransport()->messages())
            ->map(fn ($m) => $m->getOriginalMessage())
            ->filter(fn ($m) => collect($m->getTo())->contains(
                fn ($a) => $a->getAddress() === $адрес))
            ->filter(fn ($m) => str_contains($m->getSubject(), 'отменён'))
            ->values()
            ->all();
    }

    /**
     * Покупателю уходит письмо, и оно объясняет ПРИЧИНУ.
     *
     * ⚠️ Mail::fake() здесь бесполезен: он записывает только объекты
     * Mailable, а проект шлёт письма через Mail::send('вьюха', …) — фейк
     * проглатывает их молча. Проверяем транспортом `array`, как уже
     * приходилось делать с письмами заказов.
     */
    public function test_customer_gets_an_email_explaining_why(): void
    {
        config(['mail.default' => 'array']);

        $товар = $this->товар(5);
        $this->заказ($товар, 'online', 11);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        // Письма уходят через after_response() — в консоли ответа нет,
        // поэтому доводим приложение до завершения сами.
        $this->app->terminate();

        $письма = $this->письмаОбОтмене('pokupatel@example.test');
        $this->assertCount(1, $письма, 'Покупателю не ушло письмо об отмене');

        $письмо = $письма[0];
        $тело = $письмо->getHtmlBody() ?? $письмо->getBody()->toString();

        $this->assertStringContainsString('оплата не поступила', $письмо->getSubject());
        $this->assertStringContainsString('вернули его в продажу', $тело);
        // Срок назван словами — покупатель должен понять, сколько у него было.
        $this->assertStringContainsString('10 минут', $тело);
        // Общий блок «Ваш заказ отменён» показываться не должен: он ничего
        // не объясняет и подталкивал бы звонить и разбираться.
        //
        // ⚠️ Проверяем именно эту строку, а не «свяжитесь с нами»: последняя
        // стоит в ПОДВАЛЕ письма, одинаковом для всех статусов, и тест на
        // неё падал, хотя нужный блок был выбран верно.
        $this->assertStringNotContainsString('Ваш заказ отменён.', $тело);
    }

    /** Гостю без почты отмена всё равно происходит — просто молча. */
    public function test_order_without_email_is_still_cancelled(): void
    {
        config(['mail.default' => 'array']);

        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'online', 11);
        Order::withoutEvents(fn () => Order::whereKey($заказ->id)->update(['customer_email' => null]));

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();
        $this->app->terminate();

        $this->assertSame('cancelled', $заказ->fresh()->status);
        $this->assertSame(5, $товар->fresh()->stock);
        // Подтверждение о создании заказа ушло раньше и это нормально —
        // здесь важно, что письма ОБ ОТМЕНЕ нет: адреса уже нет в записи.
        $this->assertCount(0, $this->письмаОбОтмене('pokupatel@example.test'));
    }

    /**
     * 🔴 Несколько заказов за один прогон — письма уходят по всем.
     *
     * Здесь всплыла настоящая поломка, к таймеру отношения не имевшая:
     * шаблон письма объявлял ОБЫЧНУЮ функцию `getStatusText()`
     * в блоке разметки. Скомпилированная вьюха — это подключаемый PHP-файл, и
     * второй раз в одном процессе он падал с «Cannot redeclare function».
     *
     * В запросе письмо всегда одно, поэтому беда пролежала незамеченной. А
     * отмена по сроку шлёт письма пачкой в одном прогоне команды: второй
     * заказ ронял разбор целиком, и остальные оставались неотменёнными.
     */
    public function test_several_orders_in_one_run(): void
    {
        config(['mail.default' => 'array']);

        $первый = $this->товар(5);
        $второй = News::create([
            'title' => 'Часы', 'slug' => 'chasy-srok', 'content' => '<p>x</p>',
            'template' => 'products', 'published' => true, 'price' => 5000, 'stock' => 4,
        ]);

        $з1 = $this->заказ($первый, 'online', 11);
        $з2 = $this->заказ($второй, 'online', 12, 1);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();
        $this->app->terminate();

        $this->assertSame('cancelled', $з1->fresh()->status);
        $this->assertSame('cancelled', $з2->fresh()->status, 'Второй заказ остался неотменённым');
        $this->assertSame(5, $первый->fresh()->stock);
        $this->assertSame(4, $второй->fresh()->stock);

        $this->assertCount(2, $this->письмаОбОтмене('pokupatel@example.test'),
            'Письмо ушло не по каждому отменённому заказу');
    }

    /** Ноль в настройке полностью выключает отмену по сроку. */
    public function test_zero_timeout_disables_the_whole_thing(): void
    {
        config(['payments.unpaid_timeout' => 0]);

        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'online', 600);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $this->assertSame('pending', $заказ->fresh()->status);
    }

    /** Пробный прогон ничего не меняет. */
    public function test_dry_run_changes_nothing(): void
    {
        $товар = $this->товар(5);
        $заказ = $this->заказ($товар, 'online', 11);

        $this->artisan('orders:cancel-unpaid --dry-run')->assertSuccessful();

        $this->assertSame('pending', $заказ->fresh()->status);
        $this->assertSame(3, $товар->fresh()->stock);
    }

    /**
     * Повторный прогон не возвращает товар второй раз.
     *
     * Команда идёт по расписанию каждую минуту, поэтому «выполнилась
     * дважды» — не исключение, а обычный ход событий при наложении.
     */
    public function test_second_run_does_not_return_stock_again(): void
    {
        $товар = $this->товар(5);
        $this->заказ($товар, 'online', 11);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();
        $this->assertSame(5, $товар->fresh()->stock);

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();
        $this->assertSame(5, $товар->fresh()->stock, 'Товар вернулся дважды');
    }
}
