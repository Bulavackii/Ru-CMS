<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 📊 Модель статистики контента
 */
class ContentStatistics extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'views_count',
        'unique_views',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    /**
     * Полиморфная связь с контентом
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}

