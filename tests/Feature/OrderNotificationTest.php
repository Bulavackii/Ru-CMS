<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Уведомления администраторам о новом заказе.
 */
class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $payment = PaymentMethod::create([
            'title' => 'Наличные', 'code' => 'cash', 'type' => 'offline', 'active' => true,
        ]);

        $delivery = DeliveryMethod::create([
            'title' => 'Самовывоз', 'code' => 'pickup', 'price' => 0, 'active' => true,
        ]);

        return Order::create([
            'payment_method_id' => $payment->id,
            'delivery_method_id' => $delivery->id,
            'customer_name' => 'Иван Петров',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '+70000000000',
            'customer_address' => 'Курск',
            'total' => 1500,
            'status' => 'pending',
        ]);
    }

    public function test_every_admin_gets_exactly_one_notification(): void
    {
        Mail::fake();

        $first = User::factory()->create(['is_admin' => true]);
        $second = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => false]);

        $order = $this->makeOrder();

        // Раньше создавались персональные уведомления ПЛЮС одно общее
        // (user_id = null), а список выбирает «мои ИЛИ общие» — каждый
        // администратор видел один и тот же заказ дважды.
        foreach ([$first, $second] as $admin) {
            $seen = DB::table('admin_notifications')
                ->where(fn ($q) => $q->where('user_id', $admin->id)->orWhereNull('user_id'))
                ->count();

            $this->assertSame(1, $seen, "Администратор {$admin->id} видит {$seen} уведомлений вместо одного");
        }

        $this->assertSame(2, DB::table('admin_notifications')->count());
        $this->assertDatabaseHas('admin_notifications', ['title' => "Новый заказ #{$order->id}"]);
    }

    public function test_notification_links_to_the_order(): void
    {
        Mail::fake();
        User::factory()->create(['is_admin' => true]);

        $order = $this->makeOrder();
        $notification = DB::table('admin_notifications')->first();

        // Ссылка была только у общего уведомления, которое теперь не создаётся.
        $this->assertNotNull($notification->action_url);
        $this->assertStringContainsString((string) $order->id, $notification->action_url);
        $this->assertStringContainsString('Иван Петров', $notification->message);
    }

    public function test_broken_broadcaster_does_not_break_order_creation(): void
    {
        Mail::fake();
        User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => true]);

        // Пакета pusher в проекте нет: построение броадкастера бросает
        // RuntimeException. Раньше он вылетал наружу уже ПОСЛЕ записи заказа
        // в базу — покупатель получал ошибку, оплата не запускалась, а
        // уведомление доставалось только первому администратору.
        config(['broadcasting.default' => 'pusher']);

        $order = $this->makeOrder();

        $this->assertNotNull($order->id);
        $this->assertSame(2, DB::table('admin_notifications')->count());
    }

    public function test_broadcast_connection_env_variable_is_read(): void
    {
        // Конфиг читал только устаревшее имя BROADCAST_DRIVER, поэтому
        // BROADCAST_CONNECTION из .env игнорировался и значение падало в
        // дефолт 'pusher'.
        $config = require base_path('config/broadcasting.php');

        $this->assertNotSame('pusher', $config['default'],
            'Дефолт вещания не должен требовать пакета, которого нет в проекте');
    }

    public function test_status_change_notifies_admins_with_readable_labels(): void
    {
        // Слушатель тип-хинтил Order БЕЗ импорта: имя резолвилось в
        // App\Listeners\Order, и первое же событие роняло TypeError.
        // Тот же баг уже был у слушателя создания заказа.
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->makeOrder();

        DB::table('admin_notifications')->delete();

        $order->status = 'paid';
        $order->save();

        $notification = DB::table('admin_notifications')->latest('id')->first();

        $this->assertNotNull($notification, 'Уведомление о смене статуса не создано');

        // Подписи, а не сырые коды: свой набор в слушателе не знал
        // статуса «Оплачен» и печатал его как paid.
        $this->assertStringContainsString(__('admin.orders.st_pending'), $notification->message);
        $this->assertStringContainsString(__('admin.orders.st_paid'), $notification->message);
    }

    public function test_customer_emails_leave_only_after_the_response(): void
    {
        // Письма уходили прямо в запросе. Недоступный SMTP (у владельца —
        // smtp.mail.ru:465, закрытый провайдером) держал сокет до лимита
        // PHP, и смена статуса падала фатальной ошибкой «Maximum execution
        // time of 30 seconds exceeded». Теперь отправка регистрируется на
        // завершение запроса — ответ уходит в браузер первым.
        config(['mail.default' => 'array']);
        $transport = Mail::mailer('array')->getSymfonyTransport();

        $order = $this->makeOrder();   // письмо о создании заказа
        $order->status = 'completed';  // письмо о смене статуса
        $order->save();

        $this->assertCount(0, $transport->messages(), 'Письмо ушло прямо в запросе');

        $this->app->terminate();

        $this->assertCount(2, $transport->messages(), 'Письма не ушли после ответа');
    }
}
