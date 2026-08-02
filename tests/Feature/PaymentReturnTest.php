<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Возврат покупателя с платёжной страницы.
 *
 * Сюда платёжная система приводит человека после оплаты или отказа.
 * Раньше страницы возврата лежали за auth и объявляли успех по факту
 * перехода — гость после оплаты упирался в форму входа, а любой, кто
 * открыл ссылку руками, видел «Платёж успешно выполнен».
 */
class PaymentReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    private function order(string $status): Order
    {
        $method = PaymentMethod::create([
            'title' => 'Тест', 'code' => 'ret', 'type' => 'offline', 'active' => true,
        ]);

        return Order::create([
            'payment_method_id' => $method->id,
            'total' => 100, 'items_total' => 100, 'status' => $status,
            'customer_name' => 'Гость', 'customer_phone' => '+70000000000',
            'customer_email' => 'guest@example.com',
        ]);
    }

    public function test_guest_is_not_sent_to_the_login_page(): void
    {
        // Заказ можно оформить без регистрации, значит и вернуться с
        // оплаты гость обязан без входа.
        $order = $this->order('pending');

        $this->get(route('payments.success', $order->id))
            ->assertRedirect(route('cart.confirm', ['id' => $order->id]));
    }

    public function test_return_page_does_not_claim_success_by_itself(): void
    {
        // Заказ ещё не подтверждён webhook'ом — успех объявлять нельзя.
        $order = $this->order('pending');

        $this->get(route('payments.success', $order->id))
            ->assertSessionMissing('success')
            ->assertSessionHas('info');
    }

    public function test_confirmed_order_is_reported_as_paid(): void
    {
        $order = $this->order('completed');

        $this->get(route('payments.success', $order->id))
            ->assertSessionHas('success');
    }

    public function test_cancelled_order_is_reported_as_cancelled(): void
    {
        $order = $this->order('cancelled');

        $this->get(route('payments.fail', $order->id))
            ->assertSessionHas('error');
    }

    public function test_unknown_order_does_not_break_the_page(): void
    {
        $this->get(route('payments.success', 999999))
            ->assertRedirect(route('cart.index'));
    }

    public function test_return_routes_are_public(): void
    {
        // Проверяем поведение, а не список middleware: withoutMiddleware()
        // оставляет исключённое в gatherMiddleware(), но роутер его
        // пропускает — по списку такой маршрут выглядит закрытым, хотя
        // гостя пускает.
        $order = $this->order('pending');

        foreach (['payments.success', 'payments.fail'] as $name) {
            $this->get(route($name, $order->id))
                ->assertRedirect(route('cart.confirm', ['id' => $order->id]));
        }
    }
}
