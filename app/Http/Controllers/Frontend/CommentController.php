<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\Comments\Models\Comment;
use Modules\Comments\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @deprecated Используйте Modules\Comments\Controllers\Frontend\CommentController
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
     * 💬 Список комментариев для контента
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $comments = Comment::with(['user', 'replies.user', 'replies.replies.user'])
            ->where('model_type', $request->model_type)
            ->where('model_id', $request->model_id)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * 💬 Создать комментарий
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'content' => 'required|string|min:3|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'author_name' => 'required_if:user_id,null|string|max:255',
            'author_email' => 'required_if:user_id,null|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Используем сервис из модуля (с проверкой каптчи и спама)
        try {
            $comment = $this->commentService->create([
                'model_type' => $request->model_type,
                'model_id' => $request->model_id,
                'content' => $request->content,
                'parent_id' => $request->parent_id,
                'author_name' => $request->author_name,
                'author_email' => $request->author_email,
                'captcha' => $request->captcha ?? null,
            ], !auth()->check());

            return response()->json([
                'success' => true,
                'message' => $comment->status === 'approved' 
                    ? 'Комментарий добавлен' 
                    : 'Комментарий отправлен на модерацию',
                'comment' => $comment->load('user'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 👍 Лайк комментария
     */
    public function like(Comment $comment): JsonResponse
    {
        try {
            $this->commentService->vote($comment, 'like');
            $comment->refresh();

            return response()->json([
                'success' => true,
                'action' => 'added',
                'likes' => $comment->likes,
                'dislikes' => $comment->dislikes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 👎 Дизлайк комментария
     */
    public function dislike(Comment $comment): JsonResponse
    {
        try {
            $this->commentService->vote($comment, 'dislike');
            $comment->refresh();

            return response()->json([
                'success' => true,
                'action' => 'added',
                'likes' => $comment->likes,
                'dislikes' => $comment->dislikes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

