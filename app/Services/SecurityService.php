<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * 🔒 SecurityService - Комплексная система безопасности
 * 
 * Обеспечивает:
 * - 2FA аутентификацию
 * - Rate limiting
 * - Защиту от брутфорса
 * - Валидацию входных данных
 * - Логирование подозрительной активности
 */
class SecurityService
{
    private Google2FA $google2fa;
    private int $maxLoginAttempts = 5;
    private int $lockoutDuration = 900; // 15 минут

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * 🔐 Генерация секретного ключа для 2FA
     */
    public function generate2FASecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * ✅ Проверка 2FA кода
     */
    public function verify2FACode(string $secret, string $code): bool
    {
        try {
            return $this->google2fa->verifyKey($secret, $code);
        } catch (\Exception $e) {
            Log::warning('2FA verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 📱 Получить QR код для Google Authenticator
     */
    public function getQRCodeUrl(string $email, string $secret, string $company = 'RU CMS'): string
    {
        return $this->google2fa->getQRCodeUrl($company, $email, $secret);
    }

    /**
     * 🚫 Проверка блокировки по IP
     */
    public function isIpBlocked(string $ip): bool
    {
        $key = "security:blocked_ip:{$ip}";
        return Cache::has($key);
    }

    /**
     * 🚫 Блокировка IP адреса
     */
    public function blockIp(string $ip, int $minutes = 60): void
    {
        $key = "security:blocked_ip:{$ip}";
        Cache::put($key, true, now()->addMinutes($minutes));
        
        Log::warning('IP blocked', [
            'ip' => $ip,
            'duration' => $minutes,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * 🔓 Разблокировка IP адреса
     */
    public function unblockIp(string $ip): void
    {
        $key = "security:blocked_ip:{$ip}";
        Cache::forget($key);
    }

    /**
     * 🔢 Проверка попыток входа
     */
    public function checkLoginAttempts(string $identifier): bool
    {
        $key = "security:login_attempts:{$identifier}";
        $attempts = Cache::get($key, 0);

        if ($attempts >= $this->maxLoginAttempts) {
            $this->blockIp(request()->ip(), $this->lockoutDuration / 60);
            return false;
        }

        return true;
    }

    /**
     * ➕ Увеличить счетчик попыток входа
     */
    public function incrementLoginAttempts(string $identifier): void
    {
        $key = "security:login_attempts:{$identifier}";
        $attempts = Cache::get($key, 0) + 1;
        
        Cache::put($key, $attempts, now()->addMinutes(15));
        
        if ($attempts >= $this->maxLoginAttempts) {
            Log::warning('Max login attempts reached', [
                'identifier' => $identifier,
                'ip' => request()->ip(),
                'attempts' => $attempts
            ]);
        }
    }

    /**
     * ✅ Сброс счетчика попыток входа
     */
    public function resetLoginAttempts(string $identifier): void
    {
        $key = "security:login_attempts:{$identifier}";
        Cache::forget($key);
    }

    /**
     * 🛡️ Валидация и санитизация входных данных
     */
    public function sanitizeInput(string $input, array $options = []): string
    {
        // Удаление опасных тегов
        $input = strip_tags($input);
        
        // Удаление нулевых байтов
        $input = str_replace("\0", '', $input);
        
        // Удаление управляющих символов (кроме переносов строк)
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Ограничение длины
        $maxLength = $options['max_length'] ?? 10000;
        if (mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }
        
        return trim($input);
    }

    /**
     * 🔍 Проверка на SQL injection паттерны
     */
    public function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\bSCRIPT\b)/i',
            '/(\b--\b|\b\/\*|\*\/)/',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning('SQL injection attempt detected', [
                    'input' => substr($input, 0, 200),
                    'ip' => request()->ip()
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * 🔍 Проверка на XSS паттерны
     */
    public function detectXss(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<img[^>]+src[^>]*=.*javascript:/i',
            '/<link[^>]+href[^>]*=.*javascript:/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning('XSS attempt detected', [
                    'input' => substr($input, 0, 200),
                    'ip' => request()->ip()
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * 🔐 Генерация безопасного токена
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * 🔐 Хеширование пароля с солью
     */
    public function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * ✅ Проверка сложности пароля
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Пароль должен содержать минимум 8 символов';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать строчные буквы';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать заглавные буквы';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать цифры';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать специальные символы';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 📊 Получить статистику безопасности
     */
    public function getSecurityStats(): array
    {
        return [
            'blocked_ips' => $this->getBlockedIpsCount(),
            'failed_logins_24h' => $this->getFailedLoginsCount(),
            'active_sessions' => $this->getActiveSessionsCount(),
        ];
    }

    private function getBlockedIpsCount(): int
    {
        try {
            $cacheDriver = config('cache.default');
            $count = 0;

            if ($cacheDriver === 'redis') {
                // Для Redis сканируем ключи
                $redis = Cache::getStore()->getRedis();
                $pattern = config('cache.prefix', '') . 'security:blocked_ip:*';
                $keys = $redis->keys($pattern);
                $count = count($keys);
            } elseif ($cacheDriver === 'database') {
                // Для database драйвера
                try {
                    $count = \DB::table('cache')
                        ->where('key', 'like', '%security:blocked_ip:%')
                        ->where('expiration', '>', time())
                        ->count();
                } catch (\Exception $e) {
                    // Таблица cache может не существовать
                }
            } else {
                // Для file драйвера - сканируем директорию
                $cachePath = storage_path('framework/cache/data');
                if (File::isDirectory($cachePath)) {
                    $files = File::glob($cachePath . '/*/security:blocked_ip:*');
                    $count = count($files);
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::warning('Failed to count blocked IPs', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    private function getFailedLoginsCount(): int
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (!File::exists($logPath)) {
                return 0;
            }

            // Читаем последние строки лога (последние 24 часа)
            $lines = File::lines($logPath);
            $count = 0;
            $cutoffTime = now()->subHours(24)->timestamp;

            foreach ($lines as $line) {
                // Ищем записи о неудачных попытках входа
                if (
                    (str_contains($line, 'Max login attempts reached') ||
                     str_contains($line, 'Login attempt failed') ||
                     str_contains($line, 'Failed login attempt')) &&
                    preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)
                ) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $cutoffTime) {
                        $count++;
                    }
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::warning('Failed to count failed logins', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    private function getActiveSessionsCount(): int
    {
        try {
            return \DB::table('sessions')->where('last_activity', '>', now()->subHours(1))->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}

