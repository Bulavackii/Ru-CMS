<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id',        // ID сущности (товара, услуги и т.д.)
        'item_type',      // Тип сущности (product, service, etc.)
        'user_id',        // ID пользователя
        'name',           // Имя (для гостей)
        'email',          // Email (для гостей)
        'rating',         // Оценка (1-5)
        'title',          // Заголовок отзыва
        'content',        // Текст отзыва
        'status',         // Статус: pending, approved, rejected
        'ip_address',     // IP адрес автора
        'user_agent',     // User agent
        'parent_id',      // ID родительского отзыва (для ответов)
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $dates = ['deleted_at'];

    // Статусы
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Связи
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function item()
    {
        return $this->morphTo();
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForItem($query, $itemId, $itemType)
    {
        return $query->where('item_id', $itemId)->where('item_type', $itemType);
    }

    // Мутаторы
    public function setRatingAttribute($value)
    {
        $this->attributes['rating'] = max(1, min(5, (int)$value));
    }

    // Аксессоры
    public function getRatingStarsAttribute()
    {
        return str_repeat('⭐', $this->rating);
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Методы
    public function approve()
    {
        $this->status = self::STATUS_APPROVED;
        $this->save();
    }

    public function reject()
    {
        $this->status = self::STATUS_REJECTED;
        $this->save();
    }

    public function isOwner($userId)
    {
        return $this->user_id === $userId;
    }

    // Подсчет среднего рейтинга для сущности
    public static function getAverageRating($itemId, $itemType)
    {
        return self::where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->where('status', self::STATUS_APPROVED)
            ->avg('rating');
    }

    // Подсчет количества отзывов для сущности
    public static function getCount($itemId, $itemType)
    {
        return self::where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->where('status', self::STATUS_APPROVED)
            ->count();
    }
}
