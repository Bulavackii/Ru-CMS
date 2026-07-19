<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Events\NotificationSent;

/**
 * 🔔 NotificationService - Сервис для создания уведомлений
 */
class NotificationService
{

    /**
     * ✅ Успешное уведомление
     */
    public function success(string $title, ?string $message = null, ?int $userId = null): void
    {
        $this->create([
            'user_id' => $userId,
            'type' => 'success',
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * ❌ Ошибка
     */
    public function error(string $title, ?string $message = null, ?int $userId = null): void
    {
        $this->create([
            'user_id' => $userId,
            'type' => 'error',
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * ⚠️ Предупреждение
     */
    public function warning(string $title, ?string $message = null, ?int $userId = null): void
    {
        $this->create([
            'user_id' => $userId,
            'type' => 'warning',
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * ℹ️ Информация
     */
    public function info(string $title, ?string $message = null, ?int $userId = null, ?string $actionUrl = null, ?string $actionText = null): void
    {
        $this->create([
            'user_id' => $userId,
            'type' => 'info',
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
        ]);
    }

    /**
     * 📢 Создать уведомление (расширенная версия)
     */
    public function create(array $data): void
    {
        $notification = [
            'user_id' => $data['user_id'] ?? null,
            'type' => $data['type'] ?? 'info',
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('admin_notifications')->insert($notification);

        // Broadcast real-time уведомление
        if (config('broadcasting.default') !== 'null') {
            event(new NotificationSent($notification));
        }

        // Отправка Web Push уведомления
        if (isset($data['send_web_push']) && $data['send_web_push']) {
            $this->sendWebPush($notification);
        }
    }

    /**
     * 📱 Отправить Web Push уведомление
     */
    private function sendWebPush(array $notification): void
    {
        try {
            $webPushService = app(\App\Services\WebPushService::class);
            
            if (!$webPushService->isSupported()) {
                return;
            }

            $payload = [
                'title' => $notification['title'],
                'body' => $notification['message'] ?? '',
                'icon' => config('webpush.notifications.default_icon'),
                'badge' => config('webpush.notifications.default_badge'),
                'tag' => 'notification-' . ($notification['user_id'] ?? 'all'),
                'data' => [
                    'url' => $notification['action_url'] ?? url('/'),
                    'type' => $notification['type'],
                ],
                'vibrate' => config('webpush.notifications.default_vibrate'),
            ];

            $webPushService->broadcast($payload, $notification['user_id']);
        } catch (\Exception $e) {
            \Log::error('Web Push notification error', [
                'error' => $e->getMessage(),
                'notification' => $notification,
            ]);
        }
    }
}

