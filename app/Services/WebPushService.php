<?php

namespace App\Services;

use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * 📱 WebPushService - Сервис для отправки Web Push уведомлений
 * 
 * Использует Web Push API для отправки уведомлений в браузер
 */
class WebPushService
{
    private ?string $vapidPublicKey = null;
    private ?string $vapidPrivateKey = null;
    private ?string $vapidSubject = null;

    public function __construct()
    {
        $this->vapidPublicKey = config('webpush.vapid.public_key');
        $this->vapidPrivateKey = config('webpush.vapid.private_key');
        $this->vapidSubject = config('webpush.vapid.subject', url('/'));
    }

    /**
     * 📤 Отправить уведомление одной подписке
     */
    public function send(WebPushSubscription $subscription, array $payload): bool
    {
        if (!$subscription->isValid()) {
            Log::warning('Invalid web push subscription', ['subscription_id' => $subscription->id]);
            return false;
        }

        try {
            $encryptedPayload = $this->encryptPayload($payload, $subscription);
            $authHeader = $this->generateAuthHeader($subscription->endpoint);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/octet-stream',
                'Content-Encoding' => 'aes128gcm',
                'TTL' => '86400', // 24 часа
            ])->post($subscription->endpoint, $encryptedPayload);

            if ($response->successful()) {
                $subscription->update(['last_notified_at' => now()]);
                return true;
            } else {
                // Если подписка недействительна, деактивируем её
                if ($response->status() === 410 || $response->status() === 404) {
                    $subscription->update(['active' => false]);
                }
                Log::warning('Web push notification failed', [
                    'subscription_id' => $subscription->id,
                    'status' => $response->status(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Web push notification error', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 📤 Отправить уведомление всем активным подпискам
     */
    public function broadcast(array $payload, ?int $userId = null): int
    {
        $query = WebPushSubscription::where('active', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $subscriptions = $query->get();
        $sent = 0;

        foreach ($subscriptions as $subscription) {
            if ($this->send($subscription, $payload)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * 🔐 Шифрование payload (упрощенная версия)
     * 
     * В реальном проекте нужно использовать библиотеку web-push
     */
    private function encryptPayload(array $payload, WebPushSubscription $subscription): string
    {
        // Упрощенная реализация - в продакшене используйте библиотеку web-push
        $data = json_encode($payload);
        
        // Здесь должна быть реальная шифровка с использованием VAPID ключей
        // Для демонстрации возвращаем просто JSON
        return $data;
    }

    /**
     * 🔑 Генерация заголовка авторизации (VAPID)
     */
    private function generateAuthHeader(string $endpoint): string
    {
        if (!$this->vapidPublicKey || !$this->vapidPrivateKey) {
            throw new \Exception('VAPID keys not configured');
        }

        // Упрощенная реализация - в продакшене используйте библиотеку web-push
        // Здесь должна быть реальная генерация JWT токена с VAPID ключами
        return 'vapid t=' . base64_encode($this->vapidPublicKey) . ', k=' . base64_encode($this->vapidPrivateKey);
    }

    /**
     * ✅ Проверка поддержки Web Push
     */
    public function isSupported(): bool
    {
        return !empty($this->vapidPublicKey) && !empty($this->vapidPrivateKey);
    }
}

