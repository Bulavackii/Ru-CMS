<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;

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
            Mail::send('emails.order_created', [
                'order' => $order,
            ], function ($message) use ($order, $email) {
                $message->to($email)
                        ->subject("Заказ #{$order->id} успешно оформлен");
            });
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
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            $this->notificationService->info(
                "Новый заказ #{$order->id}",
                "Создан новый заказ на сумму " . number_format($order->total, 2, ',', ' ') . " ₽",
                $admin->id
            );
        }

        // Также создаем уведомление для всех админов (null = для всех)
        $this->notificationService->create([
            'user_id' => null, // Для всех админов
            'type' => 'info',
            'title' => "🆕 Новый заказ #{$order->id}",
            'message' => "Клиент: " . ($order->customer_name ?? $order->user?->name ?? 'Гость') . 
                        " | Сумма: " . number_format($order->total, 2, ',', ' ') . " ₽",
            'action_url' => route('admin.orders.show', $order->id),
            'action_text' => 'Просмотреть заказ',
        ]);
    }
}

