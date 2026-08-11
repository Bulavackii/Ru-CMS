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

    public function test_admin_can_change_order_status(): void
    {
        // Смена статуса падала с 500 на любом заказе: модель складывала
        // прежний статус в $order->old_status, а это заводило АТРИБУТ, и
        // Eloquent писал его в UPDATE — «столбец old_status не существует».
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();

        $this->actingAs($admin)
            ->put(route('admin.orders.update.status', $order->id), ['status' => 'pending'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_every_status_offered_by_the_panel_is_accepted(): void
    {
        // Набор статусов жил в трёх местах и разошёлся: вьюхи предлагали
        // «Оплачен» (paid), а валидация принимала processing — сохранить
        // выбранное из списка значение было невозможно.
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();

        foreach (Order::STATUSES as $status) {
            $this->actingAs($admin)
                ->put(route('admin.orders.update.status', $order->id), ['status' => $status])
                ->assertSessionHasNoErrors();

            $this->assertSame($status, $order->fresh()->status, "Статус {$status} не сохранился");
        }
    }

    public function test_status_change_reports_the_previous_value(): void
    {
        // Уведомления опираются на прежний статус — если он потеряется,
        // покупателю уедет письмо «было completed → стало completed».
        Event::fake([\App\Events\OrderStatusChanged::class]);

        $order = $this->order();
        $order->status = 'cancelled';
        $order->save();

        Event::assertDispatched(\App\Events\OrderStatusChanged::class, function ($event) use ($order) {
            return $event->order->is($order)
                && $event->oldStatus === 'completed'
                && $event->newStatus === 'cancelled';
        });
    }

    public function test_status_change_does_not_fire_without_a_change(): void
    {
        Event::fake([\App\Events\OrderStatusChanged::class]);

        $order = $this->order();
        $order->customer_name = 'Другое имя';
        $order->save();

        Event::assertNotDispatched(\App\Events\OrderStatusChanged::class);
    }
}
