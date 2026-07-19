<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Categories\Models\Category;
use Modules\Categories\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="Список категорий",
     *     tags={"Categories"},
     *     @OA\Parameter(name="parent_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="with_children", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        // Фильтрация по типу
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Фильтрация по активности
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Фильтрация по родителю
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->root();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Загрузка связей
        if ($request->filled('with')) {
            $relations = explode(',', $request->with);
            $allowed = ['parent', 'children', 'news', 'pages'];
            $relations = array_intersect($relations, $allowed);
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        // Сортировка
        $query->ordered();

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);
        
        return $this->success([
            'items' => CategoryResource::collection($data->items()),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/categories",
     *     summary="Создать категорию",
     *     tags={"Categories"},
     *     @OA\RequestBody(@OA\JsonContent(
     *         required={"title"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="type", type="string"),
     *     )),
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validate($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category = Category::create($validated);

        return $this->success(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/categories/{id}",
     *     summary="Получить категорию",
     *     tags={"Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function show(Request $request, $id): JsonResponse
    {
        $query = Category::query()->where('id', $id);

        // Загрузка связей
        if ($request->filled('with')) {
            $relations = explode(',', $request->with);
            $allowed = ['parent', 'children', 'news', 'pages'];
            $relations = array_intersect($relations, $allowed);
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        $category = $query->firstOrFail();

        return $this->success(new CategoryResource($category));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/categories/{id}",
     *     summary="Обновить категорию",
     *     tags={"Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="type", type="string"),
     *     )),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $this->validate($request->all(), [
            'title' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id|not_in:' . $id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($validated);

        return $this->success(new CategoryResource($category), 'Category updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     summary="Удалить категорию",
     *     tags={"Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="204", description="No Content")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return $this->success(null, 'Category deleted successfully', 204);
    }
}
