<?php

namespace Modules\Accessibility\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Accessibility\Models\AccessibilitySetting;

class AccessibilityAdminController extends Controller
{
    /**
     * Все переключатели модуля. Единый список для формы и для сохранения:
     * раньше их было два, и enable_multilingual_support, присутствуя в
     * контроллере, отсутствовал в форме — то есть каждое сохранение
     * принудительно выключало его, а включить его было нечем.
     */
    public const OPTIONS = [
        'enable_font_size',
        'enable_speech',
        'enable_selected_text_speech',
        'enable_contrast',
        'enable_background',
        'enable_bw_mode',
        'enable_colorblind_mode',
        'enable_sepia_mode',
        'enable_highlight_links',
        'enable_reading_mask',
        'enable_read_mode',
        'enable_text_spacing',
        'enable_dyslexia_font',
    ];

    public function index()
    {
        return view('Accessibility::admin.index', [
            'settings' => AccessibilitySetting::settings(),
            'options' => self::OPTIONS,
        ]);
    }

    public function update(Request $request)
    {
        // Валидации здесь намеренно нет.
        //
        // Раньше стояло 'sometimes|boolean' на каждом поле, а чекбокс без
        // атрибута value браузер отправляет строкой "on", которую правило
        // boolean не принимает. Сохранение падало на валидации ВСЕГДА, стоило
        // отметить хоть одну галочку, — то есть включить модуль было
        // невозможно в принципе. Ошибки форма не показывала, поэтому со
        // стороны это выглядело как «включил, а на сайте ничего нет».
        //
        // Значения снимаем через boolean(): он одинаково понимает "on", "1",
        // true и отсутствие ключа.
        $values = ['enabled' => $request->boolean('enabled')];

        foreach (self::OPTIONS as $option) {
            $values[$option] = $request->boolean($option);
        }

        try {
            AccessibilitySetting::settings()->update($values);

            // Настройки живут в кеше на час — без сброса изменения дошли бы
            // до сайта в лучшем случае через час.
            Cache::forget('accessibility_settings');

            return redirect()->route('admin.accessibility.index')
                ->with('success', __('admin.flash.settings_updated'));
        } catch (\Throwable $e) {
            Log::error('Не удалось сохранить настройки спецвозможностей: ' . $e->getMessage());

            return redirect()->route('admin.accessibility.index')
                ->with('error', __('admin.flash.settings_error', ['message' => $e->getMessage()]));
        }
    }
}
