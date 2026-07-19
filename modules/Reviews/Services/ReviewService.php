<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\Review;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReviewService
{
    /**
     * Добавление отзыва
     */
    public function addReview($data, $itemId, $itemType, $userId = null)
    {
        // Валидация
        $rules = [
            'name' => 'required_if:user_id,null|max:255',
            'email' => 'required_if:user_id,null|email|max:255',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|max:255',
            'content' => 'required|min:10|max:2000',
        ];

        // Каптча только если модуль Captcha активен
        if (class_exists('\Modules\Captcha\Services\CaptchaService')) {
            $rules['captcha'] = 'required|captcha';
        }

        $validator = Validator::make(array_merge($data, [
            'item_id' => $itemId,
            'item_type' => $itemType,
        ]), $rules);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        // Создание отзыва
        $review = Review::create([
            'item_id' => $itemId,
            'item_type' => $itemType,
            'user_id' => $userId,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'status' => Review::STATUS_PENDING,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $review;
    }

    /**
     * Получение отзывов для сущности
     */
    public function getReviews($itemId, $itemType, $approvedOnly = true)
    {
        $query = Review::where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->with(['user', 'children.user'])
            ->orderBy('created_at', 'desc');

        if ($approvedOnly) {
            $query->approved();
        }

        return $query->get();
    }

    /**
     * Статистика по отзывам
     */
    public function getStats($itemId, $itemType)
    {
        $reviews = Review::where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->where('status', Review::STATUS_APPROVED);

        return [
            'count' => $reviews->count(),
            'average' => round($reviews->avg('rating'), 1),
            'distribution' => $reviews->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->orderBy('rating')
                ->pluck('count', 'rating')
                ->toArray(),
        ];
    }

    /**
     * Добавление ответа на отзыв
     */
    public function addReply($parentId, $data, $userId)
    {
        $parent = Review::findOrFail($parentId);

        $reply = Review::create([
            'item_id' => $parent->item_id,
            'item_type' => $parent->item_type,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'rating' => 0,
            'content' => $data['content'],
            'status' => Review::STATUS_APPROVED,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $reply;
    }

    /**
     * Модерация отзывов
     */
    public function moderate($reviewId, $action, $reason = null)
    {
        $review = Review::findOrFail($reviewId);

        switch ($action) {
            case 'approve':
                $review->approve();
                break;
            case 'reject':
                $review->reject();
                break;
            case 'delete':
                $review->delete();
                break;
        }

        // Отправка уведомления
        if (in_array($action, ['approve', 'reject'])) {
            $this->sendModerationNotification($review, $action);
        }

        // Безопасное логирование
        try {
            Log::info("Review moderation", [
                'review_id' => $reviewId,
                'action' => $action,
                'reason' => $reason,
                'user_id' => Auth::check() ? Auth::id() : null,
            ]);
        } catch (\Exception $e) {
            // Логирование не критично
        }

        return $review;
    }

    /**
     * Массовая модерация отзывов
     */
    public function bulkModerate(array $reviewIds, $action, $reason = null)
    {
        $reviews = Review::whereIn('id', $reviewIds)->get();
        $count = 0;

        foreach ($reviews as $review) {
            try {
                $this->moderate($review->id, $action, $reason);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to moderate review {$review->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Импорт/экспорт (для децентрализации)
     */
    public function exportReviews($itemId, $itemType)
    {
        $reviews = Review::where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->get()
            ->toArray();

        return [
            'reviews' => $reviews,
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Экспорт всех отзывов
     */
    public function exportAllReviews()
    {
        $reviews = Review::with(['user', 'item'])->get()->map(function($review) {
            return [
                'id' => $review->id,
                'item_id' => $review->item_id,
                'item_type' => $review->item_type,
                'user_id' => $review->user_id,
                'name' => $review->name,
                'email' => $review->email,
                'rating' => $review->rating,
                'title' => $review->title,
                'content' => $review->content,
                'status' => $review->status,
                'parent_id' => $review->parent_id,
                'created_at' => $review->created_at?->toISOString(),
                'updated_at' => $review->updated_at?->toISOString(),
            ];
        })->toArray();

        return [
            'reviews' => $reviews,
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
            'total' => count($reviews),
        ];
    }

    public function importReviews($data, $merge = true)
    {
        $imported = 0;
        $skipped = 0;

        foreach ($data['reviews'] as $reviewData) {
            // Проверка на дубликаты
            $exists = Review::where('item_id', $reviewData['item_id'])
                ->where('item_type', $reviewData['item_type'])
                ->where('content', $reviewData['content'])
                ->where('created_at', $reviewData['created_at'])
                ->exists();

            if ($exists && !$merge) {
                $skipped++;
                continue;
            }

            Review::updateOrCreate(
                [
                    'item_id' => $reviewData['item_id'],
                    'item_type' => $reviewData['item_type'],
                    'content' => $reviewData['content'],
                ],
                $reviewData
            );

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Очистка старых отзывов
     */
    public function cleanup($days = 365)
    {
        $threshold = now()->subDays($days);

        $deleted = Review::where('created_at', '<', $threshold)
            ->where('status', Review::STATUS_REJECTED)
            ->forceDelete();

        return $deleted;
    }

    /**
     * Проверка на дубликаты отзывов
     */
    public function hasDuplicate($itemId, $itemType, $userId, $content)
    {
        $query = Review::where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->where('content', 'like', '%' . substr($content, 0, 50) . '%')
            ->where('created_at', '>', now()->subDays(30));

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', request()->ip());
        }

        return $query->exists();
    }

    /**
     * Отправка уведомления при модерации
     */
    public function sendModerationNotification($review, $action)
    {
        // Если есть email, отправляем уведомление
        $email = $review->email ?? $review->user?->email;
        
        if ($email) {
            try {
                // Проверяем существование представления
                if (view()->exists('Reviews::emails.moderation')) {
                    \Illuminate\Support\Facades\Mail::send('Reviews::emails.moderation', [
                        'review' => $review,
                        'action' => $action,
                    ], function ($message) use ($email, $action) {
                        $message->to($email)
                            ->subject($action === 'approve' 
                                ? 'Ваш отзыв одобрен' 
                                : 'Ваш отзыв отклонен');
                    });
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send moderation notification', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
