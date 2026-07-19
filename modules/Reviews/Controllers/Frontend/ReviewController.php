<?php

namespace Modules\Reviews\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\Reviews\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Получение отзывов для сущности (API)
     */
    public function getReviews(Request $request, $itemId, $itemType)
    {
        $reviews = $this->reviewService->getReviews($itemId, $itemType, true);

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'count' => $reviews->count(),
        ]);
    }

    /**
     * Статистика для сущности (API)
     */
    public function getStats(Request $request, $itemId, $itemType)
    {
        $stats = $this->reviewService->getStats($itemId, $itemType);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Добавление отзыва
     */
    public function store(Request $request, $itemId, $itemType)
    {
        // Валидация на уровне контроллера
        $rules = [
            'name' => 'required_if:user_id,null|max:255',
            'email' => 'required_if:user_id,null|email|max:255',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:10|max:2000',
        ];

        // Каптча только если модуль Captcha активен
        if (class_exists('\Modules\Captcha\Services\CaptchaService')) {
            $rules['captcha'] = 'required|captcha';
        }

        $request->validate($rules);

        $userId = Auth::check() ? Auth::id() : null;

        // Проверка на дубликаты (защита от спама)
        if ($this->reviewService->hasDuplicate($itemId, $itemType, $userId, $request->input('content'))) {
            return response()->json([
                'success' => false,
                'message' => 'Похожий отзыв уже существует',
            ], 422);
        }

        // Rate limiting для гостей
        if (!$userId) {
            $ip = $request->ip();
            $recentReviews = \Modules\Reviews\Models\Review::where('ip_address', $ip)
                ->where('created_at', '>', now()->subHours(24))
                ->count();

            if ($recentReviews >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Превышен лимит отзывов. Попробуйте позже.',
                ], 429);
            }
        }

        try {
            $review = $this->reviewService->addReview(
                $request->all(),
                $itemId,
                $itemType,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'Отзыв отправлен на модерацию',
                'review' => $review,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Форма добавления отзыва (для Blade)
     */
    public function createForm($itemId, $itemType)
    {
        return view('Reviews::frontend.create', [
            'item_id' => $itemId,
            'item_type' => $itemType,
        ]);
    }

    /**
     * Обработка формы отзыва
     */
    public function submitForm(Request $request, $itemId, $itemType)
    {
        $userId = Auth::check() ? Auth::id() : null;

        try {
            $review = $this->reviewService->addReview(
                $request->all(),
                $itemId,
                $itemType,
                $userId
            );

            return redirect()->back()->with('success', 'Отзыв отправлен на модерацию. Спасибо за ваш мнение!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Виджет отзывов (можно встроить в любое место)
     */
    public function widget(Request $request)
    {
        $itemId = $request->input('item_id');
        $itemType = $request->input('item_type');
        $limit = $request->input('limit', 5);
        $showForm = $request->input('show_form', true);

        if (!$itemId || !$itemType) {
            return response()->json(['error' => 'item_id and item_type required'], 400);
        }

        $reviews = $this->reviewService->getReviews($itemId, $itemType, true)->take($limit);
        $stats = $this->reviewService->getStats($itemId, $itemType);

        if ($request->has('blade')) {
            return view('Reviews::frontend.widget', compact('reviews', 'stats', 'itemId', 'itemType', 'showForm'));
        }

        return response()->json([
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }

    /**
     * API для проверки возможности добавления отзыва
     */
    public function canReview(Request $request, $itemId, $itemType)
    {
        // Проверка: пользователь уже оставлял отзыв
        if (Auth::check()) {
            $hasReview = \Modules\Reviews\Models\Review::where('item_id', $itemId)
                ->where('item_type', $itemType)
                ->where('user_id', Auth::id())
                ->where('status', '!=', 'rejected')
                ->exists();

            if ($hasReview) {
                return response()->json([
                    'can_review' => false,
                    'message' => 'Вы уже оставляли отзыв для этого объекта',
                ]);
            }
        }

        return response()->json([
            'can_review' => true,
        ]);
    }
}
