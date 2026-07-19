<?php

namespace App\Jobs;

use App\Services\SubscriptionService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * ⚠️ Проверка истечения лицензии и отправка уведомлений
 */
class CheckLicenseExpiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(SubscriptionService $subscriptionService): void
    {
        try {
            $notificationData = $subscriptionService->shouldSendExpirationNotification();
            
            if (!$notificationData) {
                return; // Не нужно отправлять уведомление
            }

            // Получаем всех администраторов
            $admins = User::where('is_admin', true)->get();
            
            if ($admins->isEmpty()) {
                Log::warning('No admin users found for license expiration notification');
                return;
            }

            $daysLeft = $notificationData['days_left'];
            $expiresAt = $notificationData['expires_at'];
            $plan = $notificationData['plan'];
            
            // Определяем уровень критичности
            $urgency = match(true) {
                $daysLeft <= 1 => 'критическое',
                $daysLeft <= 3 => 'срочное',
                $daysLeft <= 7 => 'важное',
                $daysLeft <= 14 => 'напоминание',
                default => 'информационное',
            };

            // Отправляем email каждому администратору
            foreach ($admins as $admin) {
                try {
                    Mail::send('emails.license-expiration', [
                        'admin' => $admin,
                        'daysLeft' => $daysLeft,
                        'expiresAt' => $expiresAt,
                        'plan' => $plan,
                        'urgency' => $urgency,
                        'licenseKey' => $notificationData['license_key'],
                    ], function ($message) use ($admin, $daysLeft, $urgency) {
                        $message->to($admin->email, $admin->name)
                                ->subject("⚠️ Лицензия истекает через $daysLeft " . $this->pluralize($daysLeft, 'день', 'дня', 'дней'));
                    });
                    
                    Log::info('License expiration email sent', [
                        'admin_id' => $admin->id,
                        'days_left' => $daysLeft,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send license expiration email', [
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('License expiration check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Склонение слов
     */
    private function pluralize(int $number, string $one, string $two, string $five): string
    {
        $n = abs($number) % 100;
        $n1 = $n % 10;
        
        if ($n > 10 && $n < 20) {
            return $five;
        }
        
        if ($n1 > 1 && $n1 < 5) {
            return $two;
        }
        
        if ($n1 === 1) {
            return $one;
        }
        
        return $five;
    }
}

