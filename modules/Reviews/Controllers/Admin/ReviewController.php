<?php

namespace Modules\Reviews\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Reviews\Models\Review;
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
     * Список всех отзывов
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'item'])
            ->where('parent_id', null)
            ->orderBy('created_at', 'desc');

        // Валидация фильтров
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'item_type' => 'nullable|string|max:50',
            'rating' => 'nullable|integer|between:1,5',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:newest,oldest,rating_high,rating_low',
        ]);

        // Фильтры
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('item_type')) {
            $query->where('item_type', $request->item_type);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('content', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Сортировка
        $sort = $request->input('sort', 'newest');
        match($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating_high' => $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc'),
            'rating_low' => $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $reviews = $query->paginate(20)->withQueryString();

        return view('Reviews::admin.index', compact('reviews'));
    }

    /**
     * Просмотр отзыва
     */
    public function show($id)
    {
        $review = Review::with(['user', 'children.user', 'item'])->findOrFail($id);
        return view('Reviews::admin.show', compact('review'));
    }

    /**
     * Модерация отзыва
     */
    public function moderate(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->reviewService->moderate($id, $request->action, $request->reason);

            $actionText = match($request->action) {
                'approve' => 'одобрен',
                'reject' => 'отклонен',
                'delete' => 'удален',
            };

            // Если это AJAX запрос
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Отзыв {$actionText} успешно",
                ]);
            }

            return redirect()->back()->with('success', "Отзыв {$actionText} успешно");
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return redirect()->back()->with('error', "Ошибка: {$e->getMessage()}");
        }
    }

    /**
     * Массовая модерация отзывов
     */
    public function bulkModerate(Request $request)
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
            'action' => 'required|in:approve,reject,delete',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $count = $this->reviewService->bulkModerate(
                $request->review_ids,
                $request->action,
                $request->reason
            );

            $actionText = match($request->action) {
                'approve' => 'одобрено',
                'reject' => 'отклонено',
                'delete' => 'удалено',
            };

            return redirect()->back()->with('success', "{$count} отзывов {$actionText} успешно");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Ошибка: {$e->getMessage()}");
        }
    }

    /**
     * Ответ на отзыв
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|min:5|max:2000',
        ]);

        try {
            $this->reviewService->addReply($id, $request->all(), Auth::id());
            return redirect()->back()->with('success', 'Ответ добавлен успешно');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Ошибка: {$e->getMessage()}");
        }
    }

    /**
     * Статистика
     */
    public function stats(Request $request)
    {
        $itemType = $request->item_type ?? 'product';

        $stats = Review::where('item_type', $itemType)
            ->where('status', 'approved')
            ->selectRaw('
                COUNT(*) as total,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            ')
            ->first();

        $pending = Review::where('item_type', $itemType)
            ->where('status', 'pending')
            ->count();

        return view('Reviews::admin.stats', [
            'stats' => $stats,
            'pending' => $pending,
            'itemType' => $itemType,
        ]);
    }

    /**
     * Экспорт отзывов
     */
    public function export(Request $request)
    {
        $request->validate([
            'item_id' => 'nullable|integer',
            'item_type' => 'nullable|string',
            'format' => 'nullable|in:json,csv',
        ]);

        $format = $request->input('format', 'json');
        $itemId = $request->input('item_id');
        $itemType = $request->input('item_type');

        if ($format === 'csv') {
            $query = Review::with(['user', 'item']);
            
            if ($itemId && $itemType) {
                $query->where('item_id', $itemId)->where('item_type', $itemType);
            }

            $reviews = $query->get();

            $filename = 'reviews_export_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($reviews) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM для Excel
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Автор', 'Email', 'Объект', 'Тип', 'Оценка', 'Заголовок', 'Текст', 'Статус', 'Дата']);
                
                foreach ($reviews as $review) {
                    fputcsv($file, [
                        $review->id,
                        $review->name ?? $review->user?->name ?? 'Гость',
                        $review->email ?? $review->user?->email ?? '',
                        $review->item_id,
                        $review->item_type,
                        $review->rating,
                        $review->title ?? '',
                        strip_tags($review->content),
                        $review->status,
                        $review->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // JSON экспорт
        $data = $itemId && $itemType 
            ? $this->reviewService->exportReviews($itemId, $itemType)
            : $this->reviewService->exportAllReviews();

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="reviews_export.json"',
        ]);
    }

    /**
     * Импорт отзывов
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|json',
            'merge' => 'boolean',
        ]);

        $json = json_decode(file_get_contents($request->file('file')), true);

        try {
            $result = $this->reviewService->importReviews($json, $request->merge ?? true);

            return redirect()->back()->with('success', "Импортировано: {$result['imported']}, Пропущено: {$result['skipped']}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Ошибка импорта: {$e->getMessage()}");
        }
    }
}
