<?php

namespace Modules\Accessibility\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accessibility\Models\AccessibilitySetting;

/**
 * Настройки спецвозможностей по умолчанию.
 *
 * Строка создавалась лениво — при первом заходе в раздел панели, — и
 * приходила ВЫКЛЮЧЕННОЙ. То есть после чистой установки кнопки
 * спецвозможностей на сайте не было вовсе, хотя виджет готов и работает.
 *
 * Идемпотентно: настроенное владельцем не перетирается, `--reset`
 * возвращает набор к этому.
 */
class SeedDefaultAccessibilityCommand extends Command
{
    protected $signature = 'accessibility:seed-default {--reset : Вернуть настройки к значениям по умолчанию}';

    protected $description = 'Включить виджет спецвозможностей и задать набор режимов по умолчанию';

    public function handle(): int
    {
        $created = self::seed((bool) $this->option('reset'));

        $this->info($created
            ? 'Настройки спецвозможностей заданы.'
            : 'Настройки уже есть — ничего не меняли.');

        return self::SUCCESS;
    }

    /**
     * Набор режимов, включённых по умолчанию.
     *
     * Выключены три: многоязычность (у виджета свой переключатель, он
     * дублирует языковой в шапке), чёрно-белый режим и сепия — оба
     * перекрашивают сайт целиком и нужны единицам.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'enable_font_size' => true,
            'enable_speech' => true,
            'enable_contrast' => true,
            'enable_background' => true,
            'enable_highlight_links' => true,
            'enable_reading_mask' => true,
            'enable_read_mode' => true,
            'enable_text_spacing' => true,
            'enable_dyslexia_font' => true,
            'enable_multilingual_support' => false,
            'enable_bw_mode' => false,
            'enable_colorblind_mode' => true,
            'enable_sepia_mode' => false,
            'enable_selected_text_speech' => true,
        ];
    }

    /**
     * @return bool создали или переписали настройки
     */
    public static function seed(bool $reset = false): bool
    {
        if (! class_exists(AccessibilitySetting::class)) {
            return false;
        }

        $existing = AccessibilitySetting::query()->first();

        if ($existing && ! $reset) {
            return false;
        }

        if ($existing) {
            $existing->update(self::defaults());
        } else {
            AccessibilitySetting::create(self::defaults());
        }

        // У модели кеш на час — иначе сайт до его истечения показывал бы
        // старое состояние виджета.
        \Cache::forget('accessibility_settings');

        return true;
    }
}
