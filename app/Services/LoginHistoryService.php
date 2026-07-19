<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 📊 LoginHistoryService
 *
 * Сервис для логирования и анализа истории входов пользователей
 */
class LoginHistoryService
{
    /**
     * Логировать попытку входа
     */
    public function logLoginAttempt(
        ?User $user,
        string $email,
        Request $request,
        string $status = 'success',
        ?string $failureReason = null,
        bool $isSuspicious = false,
        ?string $suspiciousReason = null
    ): LoginHistory {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        // Получаем информацию о локации (если пакет stevebauman/location установлен)
        $location = null;
        try {
            if (class_exists(\Stevebauman\Location\Facades\Location::class)) {
                $locationData = \Stevebauman\Location\Facades\Location::get($ip);
                if ($locationData && is_object($locationData)) {
                    $locationParts = array_filter([
                        $locationData->cityName ?? null,
                        $locationData->regionName ?? null,
                        $locationData->countryName ?? null,
                    ]);
                    $location = !empty($locationParts) ? implode(', ', $locationParts) : null;
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки получения локации
            Log::debug('Failed to get location for IP: ' . $ip);
        }
        
        // Определяем устройство и браузер
        $deviceInfo = $this->parseUserAgent($userAgent);
        
        // Проверяем на подозрительность
        if (!$isSuspicious && $user) {
            $isSuspicious = $this->detectSuspiciousActivity($user, $ip, $location, $request);
            if ($isSuspicious) {
                $suspiciousReason = $this->getSuspiciousReason($user, $ip, $location);
            }
        }
        
        $loginHistory = LoginHistory::create([
            'user_id' => $user?->id,
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => $status,
            'failure_reason' => $failureReason,
            'location' => $location,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'is_suspicious' => $isSuspicious,
            'suspicious_reason' => $suspiciousReason,
        ]);
        
        // Если подозрительная активность - отправляем уведомление
        if ($isSuspicious && $user) {
            $this->notifySuspiciousActivity($user, $loginHistory);
        }
        
        return $loginHistory;
    }
    
    /**
     * Определить устройство и браузер из user agent
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'device_type' => 'unknown',
                'browser' => 'unknown',
                'platform' => 'unknown',
            ];
        }
        
        $deviceType = 'desktop';
        $browser = 'unknown';
        $platform = 'unknown';
        
        // Определение устройства
        if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            $deviceType = 'tablet';
        }
        
        // Определение браузера
        if (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }
        
        // Определение платформы
        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }
        
        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
    
    /**
     * Определить подозрительную активность
     */
    protected function detectSuspiciousActivity(User $user, string $ip, ?string $location, Request $request): bool
    {
        // Проверяем, если IP отличается от последнего известного
        if ($user->last_login_ip && $user->last_login_ip !== $ip) {
            // Проверяем, были ли входы с этого IP ранее
            $previousLoginsFromIp = LoginHistory::where('user_id', $user->id)
                ->where('ip_address', $ip)
                ->where('status', 'success')
                ->count();
            
            // Если IP новый и нет предыдущих успешных входов - подозрительно
            if ($previousLoginsFromIp === 0) {
                return true;
            }
        }
        
        // Проверяем на частые неудачные попытки
        $recentFailures = LoginHistory::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(1))
            ->count();
        
        if ($recentFailures >= 3) {
            return true;
        }
        
        // Проверяем на входы из разных стран за короткий период
        if ($location && $user->last_login_at) {
            $lastLogin = LoginHistory::where('user_id', $user->id)
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->skip(1) // Пропускаем текущий
                ->first();
            
            if ($lastLogin && $lastLogin->location && $lastLogin->location !== $location) {
                // Если последний вход был меньше часа назад из другой локации - подозрительно
                if ($lastLogin->created_at->diffInHours(now()) < 1) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Получить причину подозрительности
     */
    protected function getSuspiciousReason(User $user, string $ip, ?string $location): string
    {
        $reasons = [];
        
        if ($user->last_login_ip && $user->last_login_ip !== $ip) {
            $reasons[] = 'Новый IP адрес';
        }
        
        if ($location) {
            $reasons[] = "Локация: {$location}";
        }
        
        return implode(', ', $reasons);
    }
    
    /**
     * Уведомить о подозрительной активности
     */
    protected function notifySuspiciousActivity(User $user, LoginHistory $loginHistory): void
    {
        try {
            // Логируем подозрительную активность
            Log::warning('Suspicious login activity detected', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $loginHistory->ip_address,
                'location' => $loginHistory->location,
                'reason' => $loginHistory->suspicious_reason,
            ]);
            
            // Отправляем уведомление через MonitoringService
            try {
                if (app()->bound('monitoring')) {
                    $monitoring = app('monitoring');
                    if (method_exists($monitoring, 'trackError')) {
                        $monitoring->trackError(
                            new \Exception('Suspicious login activity'),
                            [
                                'type' => 'suspicious_login',
                                'user_id' => $user->id,
                                'ip' => $loginHistory->ip_address,
                                'location' => $loginHistory->location,
                                'reason' => $loginHistory->suspicious_reason,
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Failed to send monitoring notification', ['error' => $e->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about suspicious activity', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Обновить последний вход пользователя
     */
    public function updateLastLogin(User $user, Request $request): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
    }
}

