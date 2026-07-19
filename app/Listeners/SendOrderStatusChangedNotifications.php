<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;

/**
 * 📧 Отправка уведомлений при изменении статуса заказа
 */
class SendOrderStatusChangedNotifications
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        // 📧 Email уведомление клиенту
        $this->sendCustomerEmail($order, $event->oldStatus);

        // 🔔 Уведомления администраторам в админке
        $this->notifyAdmins($order, $event->oldStatus);
    }

    /**
     * Отправка email клиенту
     */
    protected function sendCustomerEmail(Order $order, string $oldStatus): void
    {
        $email = $order->customer_email ?? ($order->user?->email);

        if (!$email) {
            return;
        }

        try {
            Mail::send('emails.order_status', [
                'order' => $order,
                'oldStatus' => $oldStatus,
            ], function ($message) use ($order, $email) {
                $message->to($email)
                        ->subject("Обновление статуса заказа #{$order->id}");
            });
        } catch (\Exception $e) {
            Log::error('Failed to send order status email', [
                'order_id' => $order->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Уведомления администраторам
     */
    protected function notifyAdmins(Order $order, string $oldStatus): void
    {
        $statusText = [
            'pending' => 'В ожидании',
            'processing' => 'В обработке',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен',
        ];

        $oldStatusText = $statusText[$oldStatus] ?? $oldStatus;
        $newStatusText = $statusText[$order->status] ?? $order->status;

        $type = match($order->status) {
            'completed' => 'success',
            'cancelled' => 'error',
            'processing' => 'warning',
            default => 'info',
        };

        // Уведомление для всех админов
        $this->notificationService->create([
            'user_id' => null, // Для всех админов
            'type' => $type,
            'title' => "Заказ #{$order->id} обновлён",
            'message' => "Статус изменён с '{$oldStatusText}' на '{$newStatusText}'",
            'action_url' => route('admin.orders.show', $order->id),
            'action_text' => 'Просмотреть заказ',
        ]);
    }
}

