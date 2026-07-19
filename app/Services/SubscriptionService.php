<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 💳 SubscriptionService - Система подписок и промокодов
 * 
 * Обеспечивает:
 * - Управление подписками
 * - Промокоды и скидки
 * - Проверку лицензий
 * - Ограничения по тарифам
 */
class SubscriptionService
{
    /**
     * ✅ Проверка активной подписки
     */
    public function hasActiveSubscription(): bool
    {
        $subscription = $this->getCurrentSubscription();
        return $subscription && $subscription->is_active && $subscription->expires_at > now();
    }

    /**
     * 📋 Получить текущую подписку
     */
    public function getCurrentSubscription()
    {
        return Cache::remember('subscription:current', 3600, function () {
            try {
                return DB::table('subscriptions')
                    ->where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->orderBy('expires_at', 'desc')
                    ->first();
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * 🎟️ Применить промокод
     */
    public function applyPromoCode(string $code, string $plan = 'basic'): array
    {
        try {
            $promo = DB::table('promo_codes')
                ->where('code', $code)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->where(function ($query) {
                    $query->whereNull('usage_limit')
                          ->orWhereRaw('used_count < usage_limit');
                })
                ->first();

            if (!$promo) {
                return [
                    'success' => false,
                    'message' => 'Промокод не найден или недействителен'
                ];
            }

            // Проверка использования пользователем
            $usedByUser = DB::table('promo_code_usage')
                ->where('promo_code_id', $promo->id)
                ->where('user_id', auth()->id())
                ->exists();

            if ($usedByUser && !$promo->reusable) {
                return [
                    'success' => false,
                    'message' => 'Промокод уже был использован'
                ];
            }

            // Расчет скидки
            $discount = $this->calculateDiscount($promo, $plan);

            return [
                'success' => true,
                'discount' => $discount,
                'discount_type' => $promo->discount_type,
                'promo_id' => $promo->id,
                'message' => 'Промокод применен успешно'
            ];
        } catch (\Exception $e) {
            Log::error('Promo code application failed', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при применении промокода'
            ];
        }
    }

    /**
     * 💰 Расчет скидки
     */
    private function calculateDiscount($promo, string $plan): float
    {
        $planPrice = $this->getPlanPrice($plan);
        
        if ($promo->discount_type === 'percentage') {
            return ($planPrice * $promo->discount_value) / 100;
        } else {
            return min($promo->discount_value, $planPrice);
        }
    }

    /**
     * 💵 Получить цену тарифа
     */
    private function getPlanPrice(string $plan): float
    {
        $prices = [
            'basic' => 990,
            'pro' => 2990,
            'enterprise' => 9990,
        ];

        return $prices[$plan] ?? 0;
    }

    /**
     * ✅ Активировать промокод
     */
    public function activatePromoCode(int $promoId, int $userId): bool
    {
        try {
            DB::beginTransaction();

            // Запись использования
            DB::table('promo_code_usage')->insert([
                'promo_code_id' => $promoId,
                'user_id' => $userId,
                'used_at' => now(),
            ]);

            // Увеличение счетчика
            DB::table('promo_codes')
                ->where('id', $promoId)
                ->increment('used_count');

            DB::commit();
            
            Cache::forget('subscription:current');
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Promo code activation failed', [
                'promo_id' => $promoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 📦 Создать подписку
     */
    public function createSubscription(array $data): bool
    {
        try {
            DB::beginTransaction();

            // Деактивация старых подписок
            DB::table('subscriptions')
                ->where('user_id', $data['user_id'])
                ->update(['is_active' => false]);

            // Создание новой подписки
            $subscriptionId = DB::table('subscriptions')->insertGetId([
                'user_id' => $data['user_id'],
                'plan' => $data['plan'],
                'license_key' => $this->generateLicenseKey(),
                'starts_at' => now(),
                'expires_at' => Carbon::parse($data['starts_at'])->addMonths($data['duration'] ?? 1),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Применение промокода если есть
            if (isset($data['promo_code_id'])) {
                $this->activatePromoCode($data['promo_code_id'], $data['user_id']);
            }

            DB::commit();
            
            Cache::forget('subscription:current');
            
            Log::info('Subscription created', [
                'subscription_id' => $subscriptionId,
                'user_id' => $data['user_id']
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription creation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 🔑 Генерация лицензионного ключа
     */
    public function generateLicenseKey(): string
    {
        return strtoupper(
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8)
        );
    }

    /**
     * 🔍 Проверка лицензионного ключа
     */
    public function validateLicenseKey(string $licenseKey): bool
    {
        try {
            $subscription = DB::table('subscriptions')
                ->where('license_key', $licenseKey)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            return $subscription !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 📊 Получить информацию о тарифе
     */
    public function getPlanInfo(string $plan): array
    {
        $plans = [
            'basic' => [
                'name' => 'Базовый',
                'price' => 990,
                'features' => [
                    'До 10 модулей',
                    'Базовая поддержка',
                    'Обновления',
                ],
            ],
            'pro' => [
                'name' => 'Профессиональный',
                'price' => 2990,
                'features' => [
                    'Неограниченные модули',
                    'Приоритетная поддержка',
                    'Все обновления',
                    'Кастомные модули',
                ],
            ],
            'enterprise' => [
                'name' => 'Корпоративный',
                'price' => 9990,
                'features' => [
                    'Все возможности Pro',
                    'Персональный менеджер',
                    'Кастомизация под заказ',
                    'SLA гарантии',
                ],
            ],
        ];

        return $plans[$plan] ?? [];
    }

    /**
     * 🚫 Проверка ограничений тарифа
     */
    public function checkFeatureAccess(string $feature): bool
    {
        $subscription = $this->getCurrentSubscription();
        
        if (!$subscription) {
            return false;
        }

        $planLimits = [
            'basic' => [
                'modules' => 10,
                'users' => 5,
                'backups' => true,
                'api_access' => false,
                'custom_themes' => false,
                'priority_support' => false,
            ],
            'pro' => [
                'modules' => -1, // неограниченно
                'users' => 50,
                'backups' => true,
                'api_access' => true,
                'custom_themes' => true,
                'priority_support' => true,
            ],
            'enterprise' => [
                'modules' => -1,
                'users' => -1,
                'backups' => true,
                'api_access' => true,
                'custom_themes' => true,
                'priority_support' => true,
                'custom_modules' => true,
                'dedicated_support' => true,
            ],
        ];

        $limits = $planLimits[$subscription->plan] ?? [];

        // Проверка конкретной фичи
        if (isset($limits[$feature])) {
            // Если значение -1, значит неограниченно
            if ($limits[$feature] === -1) {
                return true;
            }
            
            // Если булево значение
            if (is_bool($limits[$feature])) {
                return $limits[$feature];
            }
            
            // Если числовое значение - проверяем текущее использование
            if (is_numeric($limits[$feature])) {
                return $this->checkFeatureLimit($feature, $limits[$feature]);
            }
        }

        // Если фича не найдена в лимитах, разрешаем для pro и enterprise
        return in_array($subscription->plan, ['pro', 'enterprise']);
    }

    /**
     * 🔢 Проверка лимита использования фичи
     */
    private function checkFeatureLimit(string $feature, int $limit): bool
    {
        try {
            switch ($feature) {
                case 'modules':
                    $currentCount = \DB::table('modules')->where('active', true)->count();
                    return $currentCount < $limit;
                
                case 'users':
                    $currentCount = \DB::table('users')->count();
                    return $currentCount < $limit;
                
                default:
                    return true;
            }
        } catch (\Exception $e) {
            Log::warning('Feature limit check failed', [
                'feature' => $feature,
                'error' => $e->getMessage()
            ]);
            return true; // В случае ошибки разрешаем доступ
        }
    }

    /**
     * 📅 Получить информацию о лицензии с оставшимся временем
     */
    public function getLicenseInfo(): ?array
    {
        $subscription = $this->getCurrentSubscription();
        
        if (!$subscription) {
            return null;
        }

        $expiresAt = Carbon::parse($subscription->expires_at);
        $now = Carbon::now();
        $daysLeft = $now->diffInDays($expiresAt, false);
        $isExpired = $expiresAt->isPast();
        $isExpiringSoon = $daysLeft <= 30 && $daysLeft > 0;
        $isCritical = $daysLeft <= 7 && $daysLeft > 0;

        return [
            'subscription' => $subscription,
            'expires_at' => $expiresAt,
            'days_left' => $daysLeft,
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'is_critical' => $isCritical,
            'plan_info' => $this->getPlanInfo($subscription->plan),
            'formatted_expires_at' => $expiresAt->format('d.m.Y H:i'),
            'formatted_days_left' => $this->formatDaysLeft($daysLeft),
        ];
    }

    /**
     * 📝 Форматирование оставшихся дней
     */
    private function formatDaysLeft(int $days): string
    {
        if ($days < 0) {
            return 'Истекла';
        }
        
        if ($days === 0) {
            return 'Истекает сегодня';
        }
        
        if ($days === 1) {
            return '1 день';
        }
        
        if ($days < 5) {
            return "$days дня";
        }
        
        if ($days < 30) {
            return "$days дней";
        }
        
        $months = floor($days / 30);
        $remainingDays = $days % 30;
        
        if ($months === 0) {
            return "$days дней";
        }
        
        if ($remainingDays === 0) {
            return "$months " . $this->pluralize($months, 'месяц', 'месяца', 'месяцев');
        }
        
        return "$months " . $this->pluralize($months, 'месяц', 'месяца', 'месяцев') . " и $remainingDays " . $this->pluralize($remainingDays, 'день', 'дня', 'дней');
    }

    /**
     * 🔤 Склонение слов
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

    /**
     * ⚠️ Проверка необходимости отправки уведомления
     */
    public function shouldSendExpirationNotification(): ?array
    {
        $licenseInfo = $this->getLicenseInfo();
        
        if (!$licenseInfo || $licenseInfo['is_expired']) {
            return null;
        }

        $daysLeft = $licenseInfo['days_left'];
        
        // Отправляем уведомления за 30, 14, 7, 3, 1 день до истечения
        $notificationDays = [30, 14, 7, 3, 1];
        
        if (in_array($daysLeft, $notificationDays)) {
            return [
                'days_left' => $daysLeft,
                'expires_at' => $licenseInfo['expires_at'],
                'license_key' => $licenseInfo['subscription']->license_key,
                'plan' => $licenseInfo['subscription']->plan,
            ];
        }
        
        return null;
    }
}

