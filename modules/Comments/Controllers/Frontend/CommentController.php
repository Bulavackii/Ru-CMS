<?php

namespace Modules\Comments\Controllers\Frontend;

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
     * Список комментариев
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $comments = $this->commentService->getForModel(
            $request->model_type,
            $request->model_id,
            true
        );

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * Создать комментарий
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'content' => 'required|string|min:3|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'author_name' => 'required_if:user_id,null|string|max:255',
            'author_email' => 'required_if:user_id,null|email|max:255',
            'captcha' => 'required_without:user_id|string',
            'captcha_type' => 'nullable|in:image,slider,math,question',
        ]);

        try {
            $comment = $this->commentService->create($validated, !auth()->check());

            return response()->json([
                'success' => true,
                'message' => $comment->status === 'approved' 
                    ? 'Комментарий добавлен' 
                    : 'Комментарий отправлен на модерацию',
                'comment' => $comment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Голосование за комментарий (универсальный метод)
     */
    public function vote(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'vote' => 'required|in:like,dislike',
        ]);

        try {
            $this->commentService->vote($comment, $validated['vote']);

            $comment->refresh();

            return response()->json([
                'success' => true,
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
     * 👍 Лайк комментария (для обратной совместимости)
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
     * 👎 Дизлайк комментария (для обратной совместимости)
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

