<?php

namespace Modules\Captcha\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Дневная свёртка показов и проверок одной сборки каптчи.
 *
 * Пишется из CaptchaService на боевом трафике, поэтому все записи идут
 * через bump(): инкремент атомарный, а сбой статистики не должен ронять
 * саму каптчу — иначе форма перестанет отправляться из-за счётчика.
 */
class CaptchaStat extends Model
{
    protected $fillable = ['preset_id', 'date', 'shown', 'passed', 'failed'];

    protected $casts = [
        'date' => 'date',
        'shown' => 'integer',
        'passed' => 'integer',
        'failed' => 'integer',
    ];

    public function preset(): BelongsTo
    {
        return $this->belongsTo(CaptchaPreset::class, 'preset_id');
    }

    /**
     * Увеличить один из счётчиков за сегодня.
     *
     * @param  'shown'|'passed'|'failed'  $column
     */
    public static function bump(?int $presetId, string $column): void
    {
        if ($presetId === null || ! in_array($column, ['shown', 'passed', 'failed'], true)) {
            return;
        }

        $today = now()->toDateString();

        try {
            $updated = static::query()
                ->where('preset_id', $presetId)
                ->whereDate('date', $today)
                ->increment($column);

            if ($updated === 0) {
                static::query()->create([
                    'preset_id' => $presetId,
                    'date' => $today,
                    $column => 1,
                ]);
            }
        } catch (Throwable) {
            // Статистика — вспомогательная вещь. Нет таблицы, гонка на
            // уникальном индексе, недоступная БД — каптча обязана работать
            // дальше, поэтому глушим молча.
        }
    }

    /**
     * Итоги по всем сборкам разом: id => ['shown'=>, 'passed'=>, 'failed'=>].
     * Один запрос вместо выборки на каждую карточку в списке.
     */
    public static function totals(): array
    {
        try {
            return static::query()
                ->select('preset_id', DB::raw('SUM(shown) as shown'), DB::raw('SUM(passed) as passed'), DB::raw('SUM(failed) as failed'))
                ->groupBy('preset_id')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->preset_id => [
                    'shown' => (int) $row->shown,
                    'passed' => (int) $row->passed,
                    'failed' => (int) $row->failed,
                ]])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
