<?php

namespace Modules\Visual\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Visual\Models\Fragment;
use Modules\Visual\Models\Theme;
use Modules\Visual\Support\FragmentRenderer;

class PreviewController extends Controller
{
    /**
     * 👁️ Предпросмотр фрагмента
     */
    public function fragment(Request $request)
    {
        $request->validate([
            'fragment_id' => 'required|exists:visual_fragments,id',
        ]);

        $fragment = Fragment::findOrFail($request->fragment_id);
        
        // Используем кэшированный HTML или рендерим заново
        $html = $fragment->html_cached;
        if (empty($html)) {
            $html = FragmentRenderer::render(['slug' => $fragment->slug]);
        }
        
        return response()->json([
            'html' => $html,
            'css' => $fragment->css_inline ?? '',
            'title' => $fragment->title,
            'slug' => $fragment->slug,
        ]);
    }

    /**
     * 🎨 Предпросмотр темы
     */
    public function theme(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:visual_themes,id',
        ]);

        $theme = Theme::findOrFail($request->theme_id);
        
        // Генерируем CSS из токенов
        $css = $this->generateThemeCss($theme);
        
        return response()->json([
            'css' => $css,
            'tokens' => $theme->tokens ?? [],
            'config' => $theme->config ?? [],
            'title' => $theme->title,
        ]);
    }

    /**
     * Генерация CSS из токенов темы
     */
    protected function generateThemeCss(Theme $theme): string
    {
        $tokens = $theme->tokens ?? [];
        $css = ':root {';

        // colors.*
        foreach ((array) data_get($tokens, 'colors', []) as $k => $v) {
            if ($v !== null && $v !== '') {
                $css .= "--color-{$k}: {$v};";
            }
        }

        // radius.md
        $css .= '--radius-md: ' . (string) data_get($tokens, 'radius.md', '12px') . ';';

        // font.base
        $fontBase = (string) data_get($tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
        $css .= '--font-base: ' . $fontBase . ';';

        $css .= '}';

        // Добавляем пользовательский CSS из config
        $config = $theme->config ?? [];
        if (!empty($config['css'])) {
            $css .= "\n" . $config['css'];
        }

        return $css;
    }
}
