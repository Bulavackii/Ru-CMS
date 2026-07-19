<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VersioningService;
use App\Models\ContentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VersionController extends Controller
{
    protected VersioningService $versioning;

    public function __construct(VersioningService $versioning)
    {
        $this->versioning = $versioning;
    }

    /**
     * 📜 История версий контента
     */
    public function history(Request $request): View|JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $model = $request->model_type::findOrFail($request->model_id);
        $versions = $this->versioning->getHistory($model);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'versions' => $versions,
            ]);
        }

        return view('admin.versions.history', compact('model', 'versions'));
    }

    /**
     * 🔄 Восстановить версию
     */
    public function restore(ContentVersion $version): JsonResponse
    {
        $success = $this->versioning->restoreVersion($version);

        if ($success) {
            \App\Models\ActivityLog::log('version_restored', $version, "Восстановлена версия {$version->version_number}");
            
            return response()->json([
                'success' => true,
                'message' => 'Версия успешно восстановлена',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Ошибка восстановления версии',
        ], 500);
    }

    /**
     * 🔍 Сравнить версии
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'version1_id' => 'required|exists:content_versions,id',
            'version2_id' => 'required|exists:content_versions,id',
        ]);

        $version1 = ContentVersion::findOrFail($request->version1_id);
        $version2 = ContentVersion::findOrFail($request->version2_id);

        $diff = $this->versioning->compareVersions($version1, $version2);

        return response()->json([
            'success' => true,
            'diff' => $diff,
        ]);
    }

    /**
     * 📝 Сохранить черновик
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'nullable|integer',
            'data' => 'required|array',
        ]);

        $draft = $this->versioning->saveDraft(
            $request->model_type,
            $request->model_id,
            $request->data
        );

        return response()->json([
            'success' => true,
            'draft' => [
                'key' => $draft->key,
                'saved_at' => $draft->saved_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * 📥 Загрузить черновик
     */
    public function loadDraft(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'nullable|integer',
        ]);

        $draft = $this->versioning->getDraft(
            $request->model_type,
            $request->model_id
        );

        if (!$draft) {
            return response()->json([
                'success' => false,
                'message' => 'Черновик не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft,
            'data' => $draft->data,
        ]);
    }
}

