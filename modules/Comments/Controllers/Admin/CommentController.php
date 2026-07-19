<?php

namespace Modules\Comments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Comments\Services\CommentService;
use Modules\Comments\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Список комментариев для модерации
     */
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'commentable']);

        // Фильтры
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('content', 'like', "%{$request->search}%")
                  ->orWhere('author_name', 'like', "%{$request->search}%")
                  ->orWhere('author_email', 'like', "%{$request->search}%");
            });
        }

        $comments = $query->orderByDesc('created_at')->paginate(20);

        // Используем view из модуля, если есть, иначе из app
        if (view()->exists('Comments::admin.index')) {
            return view('Comments::admin.index', compact('comments'));
        }
        
        return view('admin.comments.index', compact('comments'));
    }

    /**
     * Одобрить комментарий
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
     * Отклонить комментарий
     */
    public function reject(Request $request, Comment $comment): JsonResponse
    {
        $this->commentService->reject($comment, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Комментарий отклонен',
        ]);
    }

    /**
     * Удалить комментарий (для обратной совместимости)
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
     * Пометить как спам
     */
    public function spam(Comment $comment): JsonResponse
    {
        $this->commentService->markAsSpam($comment);

        return response()->json([
            'success' => true,
            'message' => 'Комментарий помечен как спам',
        ]);
    }

    /**
     * Массовые действия
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject,spam,delete',
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:comments,id',
            // Для обратной совместимости
            'comment_ids' => 'nullable|array',
            'comment_ids.*' => 'integer|exists:comments,id',
        ]);

        $ids = $request->input('ids', $request->input('comment_ids', []));
        
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Не выбраны комментарии',
            ], 422);
        }

        $comments = Comment::whereIn('id', $ids)->get();

        foreach ($comments as $comment) {
            match ($request->action) {
                'approve' => $this->commentService->approve($comment),
                'reject' => $this->commentService->reject($comment),
                'spam' => $this->commentService->markAsSpam($comment),
                'delete' => $this->commentService->reject($comment, 'Удален массовым действием'),
            };
        }

        return response()->json([
            'success' => true,
            'message' => 'Действие выполнено',
        ]);
    }
}

