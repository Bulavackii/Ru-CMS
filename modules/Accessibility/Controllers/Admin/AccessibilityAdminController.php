<?php

namespace Modules\Accessibility\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accessibility\Models\AccessibilitySetting;

class AccessibilityAdminController extends Controller
{
    public function index()
    {
        $settings = AccessibilitySetting::settings();
        return view('Accessibility::admin.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'sometimes|boolean',
            'enable_font_size' => 'sometimes|boolean',
            'enable_speech' => 'sometimes|boolean',
            'enable_selected_text_speech' => 'sometimes|boolean',
            'enable_contrast' => 'sometimes|boolean',
            'enable_background' => 'sometimes|boolean',
            'enable_bw_mode' => 'sometimes|boolean',
            'enable_colorblind_mode' => 'sometimes|boolean',
            'enable_sepia_mode' => 'sometimes|boolean',
            'enable_highlight_links' => 'sometimes|boolean',
            'enable_reading_mask' => 'sometimes|boolean',
            'enable_read_mode' => 'sometimes|boolean',
            'enable_text_spacing' => 'sometimes|boolean',
            'enable_dyslexia_font' => 'sometimes|boolean',
            'enable_multilingual_support' => 'sometimes|boolean',
        ]);

        try {
            $settings = AccessibilitySetting::settings();

            $settings->update([
                'enabled' => $request->has('enabled'),
                'enable_font_size' => $request->has('enable_font_size'),
                'enable_speech' => $request->has('enable_speech'),
                'enable_selected_text_speech' => $request->has('enable_selected_text_speech'),
                'enable_contrast' => $request->has('enable_contrast'),
                'enable_background' => $request->has('enable_background'),
                'enable_bw_mode' => $request->has('enable_bw_mode'),
                'enable_colorblind_mode' => $request->has('enable_colorblind_mode'),
                'enable_sepia_mode' => $request->has('enable_sepia_mode'),
                'enable_highlight_links' => $request->has('enable_highlight_links'),
                'enable_reading_mask' => $request->has('enable_reading_mask'),
                'enable_read_mode' => $request->has('enable_read_mode'),
                'enable_text_spacing' => $request->has('enable_text_spacing'),
                'enable_dyslexia_font' => $request->has('enable_dyslexia_font'),
                'enable_multilingual_support' => $request->has('enable_multilingual_support'),
            ]);

            // Очистка кэша
            \Cache::forget('accessibility_settings');

            return redirect()->route('admin.accessibility.index')
                ->with('success', 'Настройки успешно обновлены.');
        } catch (\Exception $e) {
            return redirect()->route('admin.accessibility.index')
                ->with('error', 'Ошибка при обновлении настроек: ' . $e->getMessage());
        }
    }
}
