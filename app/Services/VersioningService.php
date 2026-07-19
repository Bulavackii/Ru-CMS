<?php

namespace App\Services;

use App\Models\ContentVersion;
use App\Models\ContentDraft;
use Illuminate\Support\Facades\Log;

/**
 * 🔄 Сервис версионирования контента
 */
class VersioningService
{
    /**
     * Создать версию при сохранении
     */
    public function createVersion($model, ?string $changes = null): ?ContentVersion
    {
        try {
            return ContentVersion::createFromModel($model, $changes);
        } catch (\Exception $e) {
            Log::error('Failed to create content version', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Получить историю версий
     */
    public function getHistory($model, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return ContentVersion::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Восстановить из версии
     */
    public function restoreVersion(ContentVersion $version): bool
    {
        try {
            return $version->restore();
        } catch (\Exception $e) {
            Log::error('Failed to restore version', [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Сравнить две версии
     */
    public function compareVersions(ContentVersion $version1, ContentVersion $version2): array
    {
        return $version1->diff($version2);
    }

    /**
     * Сохранить черновик
     */
    public function saveDraft(string $modelType, ?int $modelId, array $data, ?int $userId = null): ContentDraft
    {
        return ContentDraft::saveDraft($modelType, $modelId, $data, $userId);
    }

    /**
     * Получить черновик
     */
    public function getDraft(string $modelType, ?int $modelId, ?int $userId = null): ?ContentDraft
    {
        $userId = $userId ?? auth()->id();
        
        return ContentDraft::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Удалить черновик
     */
    public function deleteDraft(string $key): bool
    {
        $draft = ContentDraft::findByKey($key);
        if ($draft) {
            return $draft->delete();
        }
        return false;
    }

    /**
     * Очистить старые черновики
     */
    public function cleanupOldDrafts(int $days = 30): int
    {
        return ContentDraft::cleanupOldDrafts($days);
    }
}

