<?php

namespace Modules\Comments\Services;

use Modules\Comments\Models\Comment;
use Modules\Comments\Models\CommentVote;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\CommentCreated;

/**
 * 💬 Сервис управления комментариями
 */
class CommentService
{
    /**
     * Создать комментарий с проверкой каптчи
     */
    public function create(array $data, bool $checkCaptcha = true): Comment
    {
        // Проверка каптчи, если модуль Captcha активен
        if ($checkCaptcha && $this->isCaptchaEnabled()) {
            $captchaService = app('captcha');
            $captchaType = config('captcha.default_type', 'image');
            
            if (!isset($data['captcha']) || !$captchaService->verify($data['captcha'], $captchaType)) {
                throw new \Exception('Неверный код каптчи');
            }
        }

        // Проверка на спам
        if ($this->isSpam($data)) {
            $data['status'] = 'spam';
        } elseif (!auth()->check()) {
            // Для гостей - модерация по умолчанию
            $data['status'] = config('comments.auto_approve_guests', false) ? 'approved' : 'pending';
        } else {
            // Для авторизованных - проверка настроек
            $data['status'] = config('comments.auto_approve_users', true) ? 'approved' : 'pending';
        }

        $comment = Comment::create([
            'model_type' => $data['model_type'],
            'model_id' => $data['model_id'],
            'user_id' => auth()->id(),
            'author_name' => $data['author_name'] ?? auth()->user()?->name,
            'author_email' => $data['author_email'] ?? auth()->user()?->email,
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Логирование
        ActivityLog::log('comment.created', $comment, 'Создан комментарий');

        // Уведомления (если настроены)
        if ($comment->status === 'pending') {
            event(new CommentCreated($comment));
        }

        return $comment;
    }

    /**
     * Одобрить комментарий
     */
    public function approve(Comment $comment): bool
    {
        $comment->update(['status' => 'approved']);
        
        ActivityLog::log('comment.approved', $comment, 'Комментарий одобрен');
        
        return true;
    }

    /**
     * Отклонить комментарий
     */
    public function reject(Comment $comment, ?string $reason = null): bool
    {
        $comment->update(['status' => 'trash']);
        
        ActivityLog::log('comment.rejected', $comment, 'Комментарий отклонен: ' . ($reason ?? ''));
        
        return true;
    }

    /**
     * Пометить как спам
     */
    public function markAsSpam(Comment $comment): bool
    {
        $comment->update(['status' => 'spam']);
        
        // Блокировка IP при множественном спаме
        $spamCount = Comment::where('ip_address', $comment->ip_address)
            ->where('status', 'spam')
            ->count();
            
        if ($spamCount >= 5) {
            app('security')->blockIp($comment->ip_address, 60);
        }
        
        ActivityLog::log('comment.spam', $comment, 'Комментарий помечен как спам');
        
        return true;
    }

    /**
     * Голосование за комментарий
     */
    public function vote(Comment $comment, string $vote, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $ipAddress = request()->ip();

        // Проверка, не голосовал ли уже
        $existingVote = CommentVote::where('comment_id', $comment->id)
            ->where(function ($q) use ($userId, $ipAddress) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('ip_address', $ipAddress);
                }
            })
            ->first();

        if ($existingVote) {
            if ($existingVote->vote === $vote) {
                // Удалить голос, если тот же тип
                $existingVote->delete();
                $this->updateVoteCounts($comment);
                return true;
            } else {
                // Изменить голос
                $existingVote->update(['vote' => $vote]);
            }
        } else {
            // Создать новый голос
            CommentVote::create([
                'comment_id' => $comment->id,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'vote' => $vote,
            ]);
        }

        $this->updateVoteCounts($comment);

        return true;
    }

    /**
     * Обновить счетчики лайков/дизлайков
     */
    protected function updateVoteCounts(Comment $comment): void
    {
        $likes = CommentVote::where('comment_id', $comment->id)
            ->where('vote', 'like')
            ->count();
            
        $dislikes = CommentVote::where('comment_id', $comment->id)
            ->where('vote', 'dislike')
            ->count();

        $comment->update([
            'likes' => $likes,
            'dislikes' => $dislikes,
        ]);
    }

    /**
     * Проверка на спам (базовая)
     */
    protected function isSpam(array $data): bool
    {
        $content = strtolower($data['content'] ?? '');
        
        // Список спам-слов (можно вынести в конфиг)
        $spamWords = config('comments.spam_words', [
            'casino', 'viagra', 'xxx', 'poker', 'loan', 'credit',
        ]);

        foreach ($spamWords as $word) {
            if (str_contains($content, $word)) {
                return true;
            }
        }

        // Проверка на множественные ссылки
        $linkCount = substr_count($content, 'http');
        if ($linkCount > 2) {
            return true;
        }

        // Проверка IP на спам
        $recentSpam = Comment::where('ip_address', request()->ip())
            ->where('status', 'spam')
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        return $recentSpam >= 3;
    }

    /**
     * Получить комментарии для модели
     */
    public function getForModel(string $modelType, int $modelId, bool $onlyApproved = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = Comment::with(['user', 'replies.user', 'replies.replies.user'])
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->whereNull('parent_id');

        if ($onlyApproved) {
            $query->where('status', 'approved');
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Проверка, активен ли модуль Captcha
     */
    protected function isCaptchaEnabled(): bool
    {
        try {
            return app()->bound('captcha') && config('captcha.enabled', true);
        } catch (\Exception $e) {
            return false;
        }
    }
}

