<?php

namespace App\Http\Controllers\Api\V1;

use Modules\News\Models\News;
use App\Http\Resources\NewsResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Events\NewsCreated;
use App\Events\NewsUpdated;
use App\Events\NewsDeleted;

class NewsController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/news",
     *     summary="Список новостей",
     *     tags={"News"},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = News::with('categories');

        // Фильтр по статусу публикации (по умолчанию только опубликованные)
        if ($request->filled('published')) {
            if ($request->boolean('published')) {
                $query->published();
            } else {
                $query->where('published', false);
            }
        } else {
            $query->published();
        }

        // Фильтры
        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn($q) => $q->where('id', $request->category_id));
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('template')) {
            $query->byTemplate($request->template);
        }

        return $this->paginate($query, [], $request->input('per_page', 15));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/news",
     *     summary="Создать новость",
     *     tags={"News"},
     *     @OA\RequestBody(@OA\JsonContent(
     *         required={"title", "content"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="meta_title", type="string"),
     *         @OA\Property(property="meta_description", type="string"),
     *         @OA\Property(property="category_ids", type="array", @OA\Items(type="integer")),
     *     )),
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validate($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'slug' => 'nullable|string|max:255|unique:news,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'published' => 'boolean',
            'template' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_promo' => 'boolean',
        ]);

        // Генерация slug, если не указан
        if (empty($validated['slug']) && !empty($validated['title'])) {
            $slug = \Illuminate\Support\Str::slug($validated['title']);
            $baseSlug = $slug;
            $counter = 1;
            while (News::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['created_by'] = auth()->id();
        $news = News::create($validated);

        if (isset($validated['category_ids'])) {
            $news->categories()->sync($validated['category_ids']);
        }

        event(new NewsCreated($news));

        return $this->success(
            new NewsResource($news),
            'News created successfully',
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/news/{id}",
     *     summary="Получить новость",
     *     tags={"News"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="404", description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $news = News::with('categories')
            ->published()
            ->findOrFail($id);

        return $this->resource($news, NewsResource::class);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/news/{id}",
     *     summary="Обновить новость",
     *     tags={"News"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="content", type="string"),
     *     )),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $news = News::findOrFail($id);

        $validated = $this->validate($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'slug' => 'nullable|string|max:255|unique:news,slug,' . $news->id . '|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'published' => 'boolean',
            'template' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_promo' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $news->update($validated);

        if (isset($validated['category_ids'])) {
            $news->categories()->sync($validated['category_ids']);
        }

        event(new NewsUpdated($news));

        return $this->success(new NewsResource($news), 'News updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/news/{id}",
     *     summary="Удалить новость",
     *     tags={"News"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="204", description="No Content")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $news = News::findOrFail($id);
        $news->delete();

        event(new NewsDeleted($news));

        return $this->success(null, 'News deleted successfully', 204);
    }
}
