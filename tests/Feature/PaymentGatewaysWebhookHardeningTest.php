<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Services\PaymentGatewayService;
use Tests\TestCase;

/**
 * Уведомления остальных платёжных систем.
 *
 * Та же дыра, что была у ЮKassa: маршрут уведомлений публичный, а драйвер
 * верил телу запроса. Здесь проверяется, что подделка больше не проходит
 * ни у одной системы — в том числе у тех, где подтвердить оплату пока
 * нечем и статус не меняется вовсе.
 */
class PaymentGatewaysWebhookHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    private function orderFor(string $code, array $settings = [], float $total = 1500): Order
    {
        SeedDefaultPaymentMethodsCommand::seed();

        $method = PaymentMethod::where('code', $code)->first();
        $method->update(['active' => true, 'settings' => $settings]);

        return Order::create([
            'payment_method_id' => $method->id,
            'total' => $total,
            'items_total' => $total,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'customer_phone' => '+70000000000',
            'customer_email' => 'test@example.com',
        ]);
    }

    private function handle(string $code, array $data): bool
    {
        return app(PaymentGatewayService::class)->handleWebhook($code, $data);
    }

    public function test_sbp_notification_never_completes_an_order(): void
    {
        // Драйвер СБП не умеет подтверждать оплату у банка, поэтому
        // уведомление не должно менять статус вовсе.
        $order = $this->orderFor('sbp', ['merchant_id' => '1', 'account' => '2']);

        $handled = $this->handle('sbp', ['status' => 'paid', 'order_id' => $order->id]);

        $this->assertFalse($handled);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_sberbank_notification_without_bank_order_id_is_ignored(): void
    {
        Http::fake();

        $order = $this->orderFor('sberpay', ['user_name' => 'u', 'password' => 'p']);

        $handled = $this->handle('sberpay', ['orderNumber' => $order->id, 'status' => 2]);

        $this->assertFalse($handled);
        $this->assertSame('pending', $order->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_sberbank_trusts_the_bank_not_the_request_body(): void
    {
        // Тело говорит «оплачен», банк — «не оплачен».
        Http::fake(['*sberbank.ru/*' => Http::response(['orderStatus' => 0], 200)]);

        $order = $this->orderFor('sberpay', ['user_name' => 'u', 'password' => 'p']);

        $handled = $this->handle('sberpay', [
            'orderNumber' => $order->id,
            'orderId' => 'bank-1',
            'status' => 2,
        ]);

        $this->assertFalse($handled);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_tbank_rejects_a_payment_for_the_wrong_amount(): void
    {
        // Подпись подтверждает отправителя, но не сумму: без сверки можно
        // было заплатить рубль за заказ на полторы тысячи.
        $order = $this->orderFor('tbank', ['terminal_key' => 't', 'secret_key' => 's', 'password' => 's']);

        $gateway = app(PaymentGatewayService::class)->getGateway(
            PaymentMethod::where('code', 'tbank')->first()
        );

        $data = [
            'TerminalKey' => 't',
            'OrderId' => (string) $order->id,
            'Status' => 'CONFIRMED',
            'Amount' => 100, // 1 рубль в копейках
            'Success' => true,
        ];

        // Подпись считается тем же способом, что и в драйвере, чтобы
        // проверялась именно сверка суммы, а не отказ по подписи.
        $reflection = new \ReflectionMethod($gateway, 'generateToken');
        $reflection->setAccessible(true);
        $data['Token'] = $reflection->invoke($gateway, $data, 's');

        $this->assertFalse($gateway->handleWebhook($data));
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_tbank_rejects_a_forged_signature(): void
    {
        $order = $this->orderFor('tbank', ['terminal_key' => 't', 'secret_key' => 's', 'password' => 's']);

        $handled = $this->handle('tbank', [
            'OrderId' => (string) $order->id,
            'Status' => 'CONFIRMED',
            'Amount' => 150000,
            'Token' => 'подделка',
        ]);

        $this->assertFalse($handled);
        $this->assertSame('pending', $order->fresh()->status);
    }
}
