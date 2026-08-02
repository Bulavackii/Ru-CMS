<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Безопасность приёма уведомлений об оплате.
 *
 * Маршрут уведомлений публичный и без CSRF — иначе платёжная система до
 * него не достучится. Значит, единственная защита заказа от бесплатной
 * «оплаты» — это то, что статус переспрашивается у платёжной системы, а
 * не берётся из тела запроса.
 */
class PaymentWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    private function makeOrder(float $total = 1500): array
    {
        SeedDefaultPaymentMethodsCommand::seed();

        $method = PaymentMethod::where('code', 'yookassa')->first();
        $method->update(['active' => true, 'settings' => ['shop_id' => '1', 'secret_key' => 'k']]);

        $order = Order::create([
            'payment_method_id' => $method->id,
            'total' => $total,
            'items_total' => $total,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'customer_phone' => '+70000000000',
            'customer_email' => 'test@example.com',
        ]);

        return [$method, $order];
    }

    public function test_forged_notification_does_not_mark_the_order_paid(): void
    {
        [, $order] = $this->makeOrder();

        // Платёжная система такого платежа не знает.
        Http::fake(['api.yookassa.ru/*' => Http::response(['type' => 'error'], 404)]);

        $handled = $this->handle(['id' => 'подделка', 'metadata' => ['order_id' => $order->id]]);

        $this->assertFalse($handled);

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_notification_without_payment_id_is_ignored(): void
    {
        [, $order] = $this->makeOrder();
        Http::fake();

        $handled = $this->handle(['metadata' => ['order_id' => $order->id]]);

        $this->assertFalse($handled);

        $this->assertSame('pending', $order->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_underpaid_order_is_not_completed(): void
    {
        // Иначе можно было бы заплатить рубль за заказ на полторы тысячи.
        [, $order] = $this->makeOrder(1500);

        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-1', 'status' => 'succeeded', 'paid' => true,
            'amount' => ['value' => '1.00', 'currency' => 'RUB'],
            'metadata' => ['order_id' => $order->id],
        ], 200)]);

        $this->assertFalse($this->handle(['id' => 'pay-1', 'metadata' => ['order_id' => $order->id]]));

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_confirmed_payment_completes_the_order(): void
    {
        [, $order] = $this->makeOrder(1500);

        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-ok', 'status' => 'succeeded', 'paid' => true,
            'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
            'metadata' => ['order_id' => $order->id],
        ], 200)]);

        $this->assertTrue($this->handle(['id' => 'pay-ok', 'metadata' => ['order_id' => $order->id]]));

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertSame('pay-ok', $order->payment_id);
    }

    public function test_status_is_taken_from_the_provider_not_from_the_body(): void
    {
        // Тело говорит «оплачено», система — «ожидает». Верить надо системе.
        [, $order] = $this->makeOrder(1500);

        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-2', 'status' => 'pending', 'paid' => false,
            'amount' => ['value' => '1500.00', 'currency' => 'RUB'],
            'metadata' => ['order_id' => $order->id],
        ], 200)]);

        $this->assertFalse($this->handle(['id' => 'pay-2', 'metadata' => ['order_id' => $order->id]]));

        $this->assertSame('pending', $order->fresh()->status);
    }

    /**
     * Прогнать уведомление через драйвер так, как это делает контроллер.
     *
     * Не через HTTP: в тестовой среде маршруты модуля грузятся иначе, чем
     * на боевой, и запрос уходит на логин. Защита от подделки живёт в
     * драйвере, поэтому проверяется прямо там.
     */
    private function handle(array $object): bool
    {
        return app(\Modules\Payments\Services\PaymentGatewayService::class)
            ->handleWebhook('yookassa', ['event' => 'payment.succeeded', 'object' => $object]);
    }
}
