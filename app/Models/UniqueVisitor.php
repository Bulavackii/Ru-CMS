<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 📊 Модель уникального посетителя
 */
class UniqueVisitor extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'visit_date',
        'page_views',
        'session_duration',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }

    /**
     * Увеличить счетчик просмотров для IP
     */
    public static function incrementViews(string $ipAddress, ?string $userAgent = null): void
    {
        self::updateOrCreate(
            [
                'ip_address' => $ipAddress,
                'visit_date' => today(),
            ],
            [
                'user_agent' => $userAgent ?? request()->userAgent(),
                'page_views' => \DB::raw('page_views + 1'),
            ]
        );
    }

    /**
     * Получить статистику за период
     */
    public static function getStats(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        return [
            'unique_visitors' => self::whereBetween('visit_date', [$start, $end])
                ->distinct('ip_address')
                ->count('ip_address'),
            'total_views' => self::whereBetween('visit_date', [$start, $end])
                ->sum('page_views'),
            'avg_session_duration' => self::whereBetween('visit_date', [$start, $end])
                ->avg('session_duration'),
        ];
    }
}

