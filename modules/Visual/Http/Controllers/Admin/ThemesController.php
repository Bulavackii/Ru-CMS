<?php

namespace Modules\Visual\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Visual\Models\Theme;
use ZipArchive;

class ThemesController extends Controller
{
    /** Диск, куда кладём ассеты тем (публичный). */
    protected string $disk = 'public';

    public function index()
    {
        $themes = Theme::orderByDesc('is_default')->orderBy('title')->get();
        return view('Visual::admin.themes.index', compact('themes'));
    }

    public function create()
    {
        return view('Visual::admin.themes.form', [
            'theme'        => new Theme(),
            'assetLibrary' => $this->assetLibrary(null),
        ]);
    }

    public function store(Request $request)
    {
        $data  = $this->validated($request);

        // На создании просто сохраняем, затем обработаем загрузки
        $theme = new Theme($data);
        $theme->save(); // нужен ID для путей хранения

        $this->handleUploads($request, $theme);
        $this->regenerateCss($theme);
        $theme->save();

        Theme::flushActiveCache();

        return redirect()
            ->route('admin.visual.themes.edit', $theme)
            ->with('success', 'Тема сохранена');
    }

    public function edit(Theme $theme)
    {
        return view('Visual::admin.themes.form', [
            'theme'        => $theme,
            'assetLibrary' => $this->assetLibrary($theme->id),
        ]);
    }

    public function update(Request $request, Theme $theme)
    {
        $data = $this->validated($request, $theme->id);

        // ВАЖНО: НЕ ЗАТИРАЕМ старые tokens/config — аккуратно мёрджим
        // Старые значения:
        $oldTokens = $theme->tokens ?? [];
        $oldConfig = $theme->config ?? [];

        $newTokens = $data['tokens'] ?? [];
        $newConfig = $data['config'] ?? [];

        // Рекурсивный merge: новые значения перекрывают старые, отсутствующие — сохраняются
        $data['tokens'] = $this->mergeAssoc($oldTokens, $newTokens);
        $data['config'] = $this->mergeAssoc($oldConfig, $newConfig);

        // Применяем поля (ещё без файлов)
        $theme->fill($data);

        // Обрабатываем загрузки / удаления — они модифицируют $theme->config поверх merge
        $this->handleUploads($request, $theme);

        // Перегенерируем CSS на основе итоговых tokens/config
        $this->regenerateCss($theme);
        $theme->save();

        Theme::flushActiveCache();

        return back()->with('success', 'Изменения сохранены');
    }

    /** Сделать тему активной (по умолчанию). */
    public function apply(Theme $theme)
    {
        $this->applyTheme($theme);
        return back();
    }

    /** Удаление темы. */
    public function destroy(Theme $theme)
    {
        if ($theme->is_default) {
            return back()->with('error', 'Нельзя удалить активную тему');
        }

        // подчистим файлы
        Storage::disk($this->disk)->deleteDirectory("themes/{$theme->id}");
        $theme->delete();

        // Сбрасываем безусловно: проверять, «та ли это была тема», больше
        // нечем и незачем — активность определяет колонка в базе, а удаление
        // в любом случае меняет список тем для переключателя.
        Theme::flushActiveCache();

        return redirect()
            ->route('admin.visual.themes.index');
    }

    /* --------------------- ВСПОМОГАТЕЛЬНОЕ --------------------- */

    protected function validated(Request $request, ?int $id = null): array
    {
        $rules = [
            'title' => ['required','string','max:255'],
            'slug'  => ['required','string','max:255','alpha_dash', Rule::unique('visual_themes','slug')->ignore($id)],

            'tokens' => ['nullable'], // массив или json
            'config' => ['nullable'],

            // файлы необязательны
            'logo'        => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:' . max_upload_kb(5120),
                'dimensions:max_width=2000,max_height=2000',
            ],
            'bg_image'    => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:' . max_upload_kb(10240),
            ],
            'icons_zip'   => [
                'nullable',
                'file',
                'mimes:zip',
                'max:' . max_upload_kb(10240),
            ],
            'font_woff2'  => [
                'nullable',
                'file',
                'mimes:woff2',
                'max:2048', // 2MB
            ],
            'font_ttf'    => [
                'nullable',
                'file',
                'mimes:ttf,otf',
                'max:' . max_upload_kb(5120),
            ],

            // флаги удаления (кнопки «Удалить» в форме)
            'remove_logo' => ['sometimes','boolean'],
            'remove_bg'   => ['sometimes','boolean'],
        ];

        $data = $request->validate($rules);

        // Нормализация json-полей
        foreach (['tokens','config'] as $jsonField) {
            $val = $data[$jsonField] ?? null;
            if (is_string($val) && $val !== '') {
                $decoded = json_decode($val, true);
                $data[$jsonField] = is_array($decoded) ? $decoded : [];
            } elseif (is_array($val)) {
                // как пришло — так и оставляем
            } else {
                // если поле вовсе не отправляли — НЕ затираем (оставим null, позже смёрджим со старым)
                unset($data[$jsonField]);
            }
        }

        unset($data['is_default']); // не даём из формы менять активность

        return $data;
    }

    /**
     * Загрузка/удаление ассетов в storage/app/public/themes/{id}
     * И аккуратная работа с config.
     */
    protected function handleUploads(Request $r, Theme $theme): void
    {
        $disk = $this->disk;
        $base = "themes/{$theme->id}";

        // Текущий config (уже с мёрджем)
        $cfg  = $theme->config ?? [];

        // УДАЛЕНИЕ по флажкам (если пользователь нажал «Удалить»)
        if ($r->boolean('remove_logo')) {
            unset($cfg['logo_url']);
        }
        if ($r->boolean('remove_bg')) {
            unset($cfg['background_url'], $cfg['bg_url'], $cfg['pattern_url']);
        }

        // Логотип
        if ($r->hasFile('logo')) {
            $path = $r->file('logo')->store($base, $disk);
            $cfg['logo_url'] = Storage::disk($disk)->url($path);
        }

        // Фон (узор). Принадлежит ТОЛЬКО этой теме.
        //
        // Раньше загруженный фон копировался во все темы разом: это чинило
        // прежнюю жалобу («картинка пропадает при переключении оформления»),
        // но появилась она оттого, что фон был всего у одной темы. Теперь фон
        // есть у каждой из коробки, и разбрасывать его больше не нужно — по
        // прямой просьбе владельца темы должны отличаться и картинкой тоже.
        // Чужой фон берётся осознанно: в форме есть список тем со скачиванием.
        if ($r->hasFile('bg_image')) {
            $path = $r->file('bg_image')->store($base, $disk);
            $cfg['background_url'] = Storage::disk($disk)->url($path);
        }

        // Локальные шрифты
        if ($r->hasFile('font_woff2')) {
            $path = $r->file('font_woff2')->store($base, $disk);
            $cfg['font_woff2'] = Storage::disk($disk)->url($path);
        }
        if ($r->hasFile('font_ttf')) {
            $path = $r->file('font_ttf')->store($base, $disk);
            $cfg['font_ttf'] = Storage::disk($disk)->url($path);
        }

        // Иконки (ZIP)
        if ($r->hasFile('icons_zip')) {
            $zipPath  = $r->file('icons_zip')->store($base, $disk);
            $extract  = Storage::disk($disk)->path("$base/icons");
            @mkdir($extract, 0775, true);

            $zip = new ZipArchive();
            if ($zip->open(Storage::disk($disk)->path($zipPath)) === true) {
                $zip->extractTo($extract);
                $zip->close();
                $cfg['icons_path'] = Storage::disk($disk)->url("$base/icons");
                $cfg['icon_mode']  = 'svg';
            }
        }

        // Поля из формы (они приходят без файлов и не должны затирать URL-ы)
        // Провайдер/название шрифта (онлайн)
        $cfg['font_provider'] = $r->input('config.font_provider', $cfg['font_provider'] ?? null);
        $cfg['font_name']     = $r->input('config.font_name',     $cfg['font_name'] ?? null);

        // Режим иконок
        $cfg['icon_mode']     = $r->input('config.icon_mode', $cfg['icon_mode'] ?? 'lucide');

        // Пользовательский CSS (не трогаем, если поле пустое и не прислано)
        if ($r->exists('config.css')) {
            $cfg['css'] = $r->input('config.css', $cfg['css'] ?? '');
        }

        // Позиция/ширина логотипа из формы (если ты добавил эти поля)
        if ($r->exists('config.logo_position')) {
            $cfg['logo_position'] = $r->input('config.logo_position', $cfg['logo_position'] ?? 'left');
        }
        if ($r->exists('config.logo_width')) {
            $val = trim((string) $r->input('config.logo_width', ''));
            $cfg['logo_width'] = $val !== '' ? $val : ($cfg['logo_width'] ?? '120px');
        }

        $theme->config = $cfg;
    }

    /**
     * Сборка CSS-переменных переехала в саму тему: она описывает её
     * собственные данные, и переключателю в шапке она тоже нужна.
     */
    protected function regenerateCss(Theme $theme): void
    {
        $theme->regenerateCss();
    }

    /**
     * Фоны и знаки ОСТАЛЬНЫХ тем — чтобы их можно было забрать себе.
     *
     * Владелец: «хочу зайти и скачать — установить фоновое изображение от
     * Индиго на любую тему». Без такого списка пришлось бы открыть другую
     * тему, скачать оттуда, вернуться и загрузить — а по дороге легко
     * сохранить чужую тему по ошибке.
     *
     * @param  int|null  $exceptId  Тема, которую сейчас правят: показывать её
     *                              среди чужих незачем.
     */
    private function assetLibrary(?int $exceptId)
    {
        return Theme::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'tokens', 'config'])
            ->map(fn (Theme $theme) => (object) [
                'title'      => $theme->t('title'),
                'slug'       => $theme->slug,
                'primary'    => data_get($theme->tokens, 'colors.primary', '#6366f1'),
                'background' => data_get($theme->config, 'background_url'),
                'logo'       => data_get($theme->config, 'logo_url'),
            ])
            ->filter(fn ($theme) => $theme->background || $theme->logo)
            ->values();
    }

    /**
     * Применение темы живёт в модели: тот же метод зовёт переключатель в
     * шапке панели. Раньше это были две независимые реализации, и они уже
     * разошлись — переключатель не пересобирал CSS темы.
     */
    private function applyTheme(Theme $theme): void
    {
        Theme::apply($theme);
    }

    /** Неброский рекурсивный merge ассоц. массивов (правые ключи перекрывают левые). */
    private function mergeAssoc(?array $base, ?array $over): array
    {
        $base = $base ?? [];
        $over = $over ?? [];
        // array_replace_recursive как раз то, что нужно:
        return array_replace_recursive($base, $over);
    }

}
