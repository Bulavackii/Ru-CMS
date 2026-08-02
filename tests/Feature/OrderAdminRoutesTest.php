<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Маршруты заказов: доступность и разграничение прав.
 */
class OrderAdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    private function order(?int $userId = null): Order
    {
        $method = PaymentMethod::create([
            'title' => 'Тест', 'code' => 'ord', 'type' => 'yookassa', 'active' => true,
            'settings' => ['shop_id' => '1', 'secret_key' => 'k'],
        ]);

        return Order::create([
            'user_id' => $userId,
            'payment_method_id' => $method->id,
            'total' => 100, 'items_total' => 100, 'status' => 'completed',
            'customer_name' => 'Гость', 'customer_phone' => '+70000000000',
            'customer_email' => 'g@example.com',
        ]);
    }

    public function test_stats_route_is_not_shadowed_by_the_order_page(): void
    {
        // /admin/orders/{order} объявлен раньше /admin/orders/stats,
        // поэтому «stats» попадал в него как идентификатор заказа и
        // страница статистики отдавала 500.
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.orders.stats'))
            ->assertStatus(200)
            ->assertJsonStructure(['total', 'pending', 'completed', 'revenue']);
    }

    public function test_export_route_still_works(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.orders.export'))
            ->assertStatus(200);
    }

    public function test_user_cannot_start_payment_for_someone_elses_order(): void
    {
        // Иначе чужой оплаченный заказ можно было сбросить в pending.
        $owner = User::factory()->create(['is_admin' => false]);
        $stranger = User::factory()->create(['is_admin' => false]);

        $order = $this->order($owner->id);

        $this->actingAs($stranger)
            ->post(route('orders.payment.initiate', $order->id))
            ->assertForbidden();

        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_guest_cannot_start_payment(): void
    {
        $order = $this->order();

        $this->post(route('orders.payment.initiate', $order->id))->assertRedirect();
    }
}
