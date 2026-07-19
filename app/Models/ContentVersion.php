<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🔄 Модель версии контента
 */
class ContentVersion extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'data',
        'changes',
        'version_number',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_current' => 'boolean',
        ];
    }

    /**
     * Полиморфная связь с контентом
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Пользователь, создавший версию
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Создать версию из модели
     */
    public static function createFromModel($model, ?string $changes = null): self
    {
        // Пометить все предыдущие версии как неактуальные
        self::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->update(['is_current' => false]);

        // Получить следующую версию
        $lastVersion = self::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->orderByDesc('id')
            ->first();

        $versionNumber = '1.0.0';
        if ($lastVersion) {
            $parts = explode('.', $lastVersion->version_number);
            $parts[2] = (int)$parts[2] + 1; // Инкремент патча
            $versionNumber = implode('.', $parts);
        }

        return self::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'user_id' => auth()->id(),
            'data' => $model->toArray(),
            'changes' => $changes,
            'version_number' => $versionNumber,
            'is_current' => true,
        ]);
    }

    /**
     * Восстановить модель из версии
     */
    public function restore(): bool
    {
        $model = $this->model_type::find($this->model_id);
        
        if (!$model) {
            return false;
        }

        // Создать новую версию перед восстановлением
        self::createFromModel($model, "Восстановление из версии {$this->version_number}");

        // Восстановить данные
        $model->fill($this->data);
        $model->save();

        // Пометить эту версию как текущую
        $this->update(['is_current' => true]);

        return true;
    }

    /**
     * Получить различия между версиями
     */
    public function diff(self $otherVersion): array
    {
        $diff = [];
        $thisData = $this->data;
        $otherData = $otherVersion->data;

        foreach ($thisData as $key => $value) {
            if (!isset($otherData[$key])) {
                $diff['added'][$key] = $value;
            } elseif ($otherData[$key] !== $value) {
                $diff['changed'][$key] = [
                    'old' => $otherData[$key],
                    'new' => $value,
                ];
            }
        }

        foreach ($otherData as $key => $value) {
            if (!isset($thisData[$key])) {
                $diff['removed'][$key] = $value;
            }
        }

        return $diff;
    }
}

