<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Comments\Models\Comment;
use Modules\Comments\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

/**
 * @deprecated Используйте Modules\Comments\Controllers\Admin\CommentController
 * Оставлен для обратной совместимости
 */
class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * 💬 Список комментариев
     */
    public function index(Request $request): View
    {
        // Используем контроллер из модуля
        $moduleController = new \Modules\Comments\Controllers\Admin\CommentController($this->commentService);
        return $moduleController->index($request);
    }

    /**
     * ✅ Одобрить комментарий
     */
    public function approve(Comment $comment): JsonResponse
    {
        $this->commentService->approve($comment);

        return response()->json([
            'success' => true,
            'message' => 'Комментарий одобрен',
        ]);
    }

    /**
     * ❌ Отклонить комментарий
     */
    public function reject(Comment $comment): JsonResponse
    {
        $this->commentService->reject($comment);

        return response()->json([
            'success' => true,
            'message' => 'Комментарий отклонен',
        ]);
    }

    /**
     * 🗑️ Удалить комментарий
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->commentService->reject($comment, 'Удален администратором');

        return response()->json([
            'success' => true,
            'message' => 'Комментарий удален',
        ]);
    }

    /**
     * 🔄 Массовые действия
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete,spam',
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'integer|exists:comments,id',
        ]);

        $ids = $request->input('comment_ids', $request->input('ids', []));
        
        // Используем сервис из модуля
        $comments = Comment::whereIn('id', $ids)->get();

        foreach ($comments as $comment) {
            match ($request->action) {
                'approve' => $this->commentService->approve($comment),
                'reject', 'delete' => $this->commentService->reject($comment),
                'spam' => $this->commentService->markAsSpam($comment),
            };
        }

        return response()->json([
            'success' => true,
            'message' => 'Действие выполнено',
        ]);
    }
}

