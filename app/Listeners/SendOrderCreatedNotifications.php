<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
// Без этого импорта тип-хинт Order резолвился в App\Listeners\Order —
// класса, которого не существует, и любое создание заказа роняло
// слушателя TypeError'ом.
use Modules\Payments\Models\Order;

/**
 * 📧 Отправка уведомлений при создании заказа
 */
class SendOrderCreatedNotifications
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // 📧 Email уведомление клиенту
        $this->sendCustomerEmail($order);

        // 🔔 Уведомления администраторам в админке
        $this->notifyAdmins($order);
    }

    /**
     * Отправка email клиенту
     */
    protected function sendCustomerEmail(Order $order): void
    {
        $email = $order->customer_email ?? ($order->user?->email);

        if (!$email) {
            return;
        }

        try {
            // Письмо уходит после ответа: покупатель и админ не должны
            // ждать почтовый сервер, а недоступный SMTP не должен ронять
            // страницу по лимиту времени выполнения.
            after_response(fn () => Mail::send('emails.order_created', [
                'order' => $order,
            ], function ($message) use ($order, $email) {
                $message->to($email)
                        ->subject("Заказ #{$order->id} успешно оформлен");
            }), ['order_id' => $order->id, 'mail' => 'order_created']);
        } catch (\Exception $e) {
            Log::error('Failed to send order created email', [
                'order_id' => $order->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Уведомления администраторам
     */
    protected function notifyAdmins(Order $order): void
    {
        // Одно уведомление на каждого администратора.
        //
        // Раньше их создавалось два комплекта: персональные в цикле ПЛЮС одно
        // общее с user_id = null. Список в панели выбирает записи «мои ИЛИ
        // общие», поэтому каждый администратор видел один и тот же заказ
        // дважды. Оставлены персональные — у них у каждого своя отметка
        // «прочитано», а ссылка на заказ (была только у общего) добавлена сюда.
        $customer = $order->customer_name ?: ($order->user?->name ?: 'Гость');
        $total = number_format((float) $order->total, 2, ',', ' ');

        User::where('is_admin', true)
            ->select('id')
            ->each(function (User $admin) use ($order, $customer, $total) {
                $this->notificationService->create([
                    'user_id' => $admin->id,
                    'type' => 'info',
                    'title' => "Новый заказ #{$order->id}",
                    'message' => "Клиент: {$customer} · Сумма: {$total} ₽",
                    'action_url' => route('admin.orders.show', $order->id),
                    'action_text' => 'Открыть заказ',
                ]);
            });
    }
}

