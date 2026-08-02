<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Services\PaymentGatewayService;
use Tests\TestCase;

/**
 * Оплата заказа: создание платежа и адреса возврата.
 *
 * До этой правки заказ создавался, а в платёжную систему не уходило
 * ничего — покупатель попадал на страницу подтверждения, так и не
 * заплатив. Тесты закрепляют, что онлайн-метод теперь ведёт на оплату,
 * а офлайновый по-прежнему нет.
 */
class PaymentCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создание заказа поднимает событие OrderCreated, а оно уходит в
        // броадкастер. Здесь проверяется оплата, а не рассылка, поэтому
        // события глушим — иначе тест падал бы на отсутствующем Pusher.
        \Illuminate\Support\Facades\Event::fake();
    }

    private function order(PaymentMethod $method): Order
    {
        return Order::create([
            'payment_method_id' => $method->id,
            'total' => 1500,
            'items_total' => 1500,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'customer_phone' => '+70000000000',
            'customer_email' => 'test@example.com',
        ]);
    }

    public function test_webhook_route_name_used_by_gateways_exists(): void
    {
        // AbstractPaymentGateway строил route('payments.webhook'), а маршрут
        // называется 'payment.webhook' — любой драйвер, спрашивающий свой
        // webhook-адрес, падал с «Route not defined».
        $this->assertTrue(Route::has('payment.webhook'));
        $this->assertTrue(Route::has('payments.success'));
        $this->assertTrue(Route::has('payments.fail'));
    }

    public function test_yookassa_payment_is_created_and_returns_a_redirect(): void
    {
        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-42',
            'status' => 'pending',
            'confirmation' => ['confirmation_url' => 'https://yoomoney.ru/checkout/pay-42'],
        ], 200)]);

        SeedDefaultPaymentMethodsCommand::seed();
        $method = PaymentMethod::where('code', 'yookassa')->first();
        $method->update(['active' => true, 'settings' => ['shop_id' => '1', 'secret_key' => 'k']]);

        $result = app(PaymentGatewayService::class)->createPayment($this->order($method), $method);

        $this->assertTrue($result['success']);
        $this->assertSame('pay-42', $result['payment_id']);
        $this->assertStringStartsWith('https://', $result['confirmation_url']);
    }

    public function test_payment_is_not_created_without_credentials(): void
    {
        Http::fake();

        SeedDefaultPaymentMethodsCommand::seed();
        $method = PaymentMethod::where('code', 'yookassa')->first();
        $method->update(['active' => true]);

        $this->expectException(\Exception::class);

        app(PaymentGatewayService::class)->createPayment($this->order($method), $method);
    }

    public function test_tbank_and_sberpay_codes_resolve_to_a_gateway(): void
    {
        // Сидер заводит методы под кодами tbank и sberpay, а карта
        // гейтвеев знала только tinkoff и sberbank — метод оставался
        // без драйвера.
        SeedDefaultPaymentMethodsCommand::seed();
        $service = app(PaymentGatewayService::class);

        foreach (['tbank', 'sberpay', 'yookassa', 'sbp'] as $code) {
            $method = PaymentMethod::where('code', $code)->first();

            $this->assertNotNull(
                $service->getGateway($method),
                "Для метода {$code} не нашёлся драйвер"
            );
        }
    }

    public function test_offline_method_has_no_gateway(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();

        $method = PaymentMethod::where('code', 'cash')->first();

        $this->assertNull(app(PaymentGatewayService::class)->getGateway($method));
    }
}
