<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Menu\Models\Page;
use App\Http\Resources\PageResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PageController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/pages",
     *     summary="Список страниц",
     *     tags={"Pages"},
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="slug", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="published", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Page::with(['categories']);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->filled('slug')) {
            $query->where('slug', $request->slug);
        }

        // Только опубликованные по умолчанию
        $query->where('published', true);

        return $this->paginate($query);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pages",
     *     summary="Создать страницу",
     *     tags={"Pages"},
     *     @OA\RequestBody(@OA\JsonContent(
     *         required={"title", "content"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="published", type="boolean"),
     *         @OA\Property(property="meta_title", type="string"),
     *         @OA\Property(property="meta_description", type="string"),
     *     )),
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validate($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'published' => 'sometimes|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $page = Page::create($validated);

        return $this->success(
            new PageResource($page),
            'Page created successfully',
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pages/{id}",
     *     summary="Получить страницу",
     *     tags={"Pages"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function show($id): JsonResponse
    {
        $page = Page::with(['categories'])->findOrFail($id);

        if (!$page->published) {
            return $this->error('Page not published', 404);
        }

        return $this->resource($page, PageResource::class);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/pages/{id}",
     *     summary="Обновить страницу",
     *     tags={"Pages"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="published", type="boolean"),
     *     )),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $validated = $this->validate($request->all(), [
            'title' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'sometimes|string',
            'published' => 'sometimes|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $page->update($validated);

        return $this->success(new PageResource($page), 'Page updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/pages/{id}",
     *     summary="Удалить страницу",
     *     tags={"Pages"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="204", description="No Content")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return $this->success(null, 'Page deleted successfully', 204);
    }
}
