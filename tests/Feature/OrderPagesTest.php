<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Страницы заказа: карточка в панели, смена статуса, удаление и
 * подтверждение для покупателя.
 *
 * По этим путям тестов не было вовсе — они и открываются-то только при
 * наличии реального заказа, а в базе его обычно нет.
 */
class OrderPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function order(string $status = 'pending'): Order
    {
        $method = PaymentMethod::create([
            'title' => 'Наличными', 'code' => 'op_cash', 'type' => 'offline', 'active' => true,
        ]);

        $order = Order::create([
            'payment_method_id' => $method->id,
            'total' => 2500, 'items_total' => 2500, 'status' => $status,
            'customer_name' => 'Иван Петров', 'customer_phone' => '+79990000000',
            'customer_email' => 'ivan@example.com', 'customer_address' => 'Курск, ул. Ленина, 1',
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'product_id' => 1,
            'title' => 'Товар', 'price' => 2500, 'qty' => 1,
        ]);

        return $order;
    }

    public function test_order_card_opens(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $this->order()->id))
            ->assertStatus(200)
            ->assertSee('Иван Петров', false);
    }

    public function test_order_list_shows_the_order(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertStatus(200)
            ->assertSee((string) $order->id, false);
    }

    public function test_status_can_be_changed(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update.status', $order->id), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_unknown_status_is_rejected(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update.status', $order->id), ['status' => 'что-угодно'])
            ->assertSessionHasErrors();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_order_can_be_deleted(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin())
            ->delete(route('admin.orders.destroy', $order->id))
            ->assertRedirect();

        $this->assertNull(Order::find($order->id));
    }

    public function test_order_pages_are_closed_for_plain_users(): void
    {
        $order = $this->order();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.orders.show', $order->id))->assertForbidden();
        $this->actingAs($user)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_customer_confirmation_page_opens(): void
    {
        // В тестовой среде маршруты модуля наследуют admin-группу (строка
        // модуля в таблице modules отсутствует, и файл маршрутов грузится
        // другим путём), поэтому страница подтверждения уходит на вход.
        // Проверяем то, ради чего страница существует: заказ находится и
        // отдаётся вьюхе со всеми связями.
        $order = $this->order();

        // ⚠️ Страница больше не показывает ЛЮБОЙ заказ по номеру: посторонний
        // перебирал адреса и читал всю историю продаж (см. CartIntegrityTest::
        // test_confirmation_page_hides_other_peoples_orders). Гостю доступ
        // даёт сессия — туда номер кладётся при оформлении.
        session(['my_orders' => [$order->id]]);

        $data = app(\Modules\Payments\Controllers\Frontend\CartController::class)
            ->confirm($order->id)
            ->getData();

        $this->assertSame($order->id, $data['order']->id);
        $this->assertNotNull($data['paymentMethod']);
        $this->assertCount(1, $data['order']->items);
    }
}
