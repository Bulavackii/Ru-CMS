<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 📊 LoginHistory
 *
 * Модель для истории входов пользователей
 */
class LoginHistory extends Model
{
    use HasFactory;

    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'location',
        'device_type',
        'browser',
        'platform',
        'is_suspicious',
        'suspicious_reason',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить последние входы пользователя
     */
    public static function getRecentLogins(int $userId, int $limit = 10)
    {
        return static::where('user_id', $userId)
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить подозрительные входы
     */
    public static function getSuspiciousLogins(int $limit = 50)
    {
        return static::where('is_suspicious', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику входов по IP
     */
    public static function getLoginStatsByIp(string $ip, int $hours = 24)
    {
        return static::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }
}




