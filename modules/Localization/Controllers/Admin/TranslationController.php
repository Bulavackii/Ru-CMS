<?php

namespace Modules\Localization\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\Localization\Services\TranslationFileService;

/**
 * 📝 Графический редактор переводов.
 *
 * Правит те же файлы resources/lang/<locale>/<group>.php, что и
 * разработчик руками, — отдельного хранилища переводов в БД нет.
 * Здесь же заводятся новые языки.
 */
class TranslationController extends Controller
{
    public function __construct(private TranslationFileService $files)
    {
    }

    /** 📊 Список языков с прогрессом перевода. */
    public function index(): View
    {
        $locales = [];
        foreach ($this->files->locales() as $code) {
            $locales[$code] = [
                'code'      => $code,
                'name'      => $this->localeName($code),
                'stats'     => $this->files->stats($code),
                'protected' => in_array($code, $this->files->protectedLocales(), true),
            ];
        }

        return view('Localization::admin.translations.index', [
            'locales'   => $locales,
            'groups'    => $this->files->groups(),
            'reference' => TranslationFileService::REFERENCE_LOCALE,
        ]);
    }

    /** ✏️ Редактор одной группы (файла) переводов. */
    public function edit(string $locale, ?string $group = null): View|RedirectResponse
    {
        if (!$this->files->isValidLocale($locale)) {
            return redirect()
                ->route('admin.localization.translations.index')
                ->with('error', 'Недопустимый код локали.');
        }

        $groups = $this->files->groups();
        if ($groups === []) {
            return redirect()
                ->route('admin.localization.translations.index')
                ->with('error', 'Не найдено ни одного файла переводов.');
        }

        // Без явной группы открываем первую — редактор всегда с контентом.
        $group ??= $groups[0];

        if (!in_array($group, $groups, true)) {
            return redirect()
                ->route('admin.localization.translations.index')
                ->with('error', "Файл переводов «{$group}» не найден.");
        }

        $reference = TranslationFileService::REFERENCE_LOCALE;

        $referenceLines = $this->files->loadFlat($reference, $group);
        $currentLines   = $this->files->loadFlat($locale, $group);

        // Строки редактора: порядок берём из эталона, следом — ключи,
        // которых в эталоне нет (остались от прежних версий словаря).
        $rows = [];
        foreach ($referenceLines as $key => $referenceValue) {
            $value = $currentLines[$key] ?? '';
            $rows[] = [
                'key'       => $key,
                'reference' => $referenceValue,
                'value'     => $value,
                'missing'   => $value === '',
                'same'      => $value !== '' && $value === $referenceValue,
                'extra'     => false,
            ];
        }
        foreach ($currentLines as $key => $value) {
            if (!array_key_exists($key, $referenceLines)) {
                $rows[] = [
                    'key'       => $key,
                    'reference' => '',
                    'value'     => $value,
                    'missing'   => false,
                    'same'      => false,
                    'extra'     => true,
                ];
            }
        }

        $groupStats = [];
        foreach ($groups as $g) {
            $groupStats[$g] = $this->files->groupStats($locale, $g);
        }

        return view('Localization::admin.translations.edit', [
            'locale'      => $locale,
            'localeName'  => $this->localeName($locale),
            'group'       => $group,
            'groups'      => $groups,
            'groupStats'  => $groupStats,
            'rows'        => $rows,
            'reference'   => $reference,
            'isReference' => $locale === $reference,
        ]);
    }

    /** 💾 Сохранение группы переводов. */
    public function update(Request $request, string $locale, string $group): RedirectResponse
    {
        if (!$this->files->isValidLocale($locale) || !$this->files->isValidGroup($group)) {
            return back()->with('error', 'Недопустимый язык или файл переводов.');
        }

        /** @var array<string,string> $lines */
        $lines = $request->input('lines', []);

        if (!is_array($lines)) {
            return back()->with('error', 'Пустая форма — сохранять нечего.');
        }

        // Пустые значения не пишем: пусть ключ отсутствует и уедет в
        // fallback, чем в интерфейсе появится пустое место.
        $clean = [];
        foreach ($lines as $key => $value) {
            $value = is_string($value) ? trim($value) : '';
            if ($value !== '') {
                $clean[(string) $key] = $value;
            }
        }

        try {
            $this->files->save($locale, $group, $clean);
        } catch (\Throwable $e) {
            return back()->with('error', 'Не удалось сохранить: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.localization.translations.edit', [$locale, $group])
            ->with('success', "Переводы «{$group}» для языка «{$locale}» сохранены (" . count($clean) . ' строк).');
    }

    /** ➕ Создание новой локали копированием из существующей. */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'code'      => ['required', 'string', 'max:12'],
            'copy_from' => ['required', 'string', 'max:12'],
        ], [], [
            'code'      => 'Код языка',
            'copy_from' => 'Источник копирования',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $code = strtolower(trim((string) $request->input('code')));
        $from = (string) $request->input('copy_from');

        if (!$this->files->isValidLocale($code)) {
            return back()
                ->with('error', 'Код языка должен быть в формате ru, en, kk или pt_BR.')
                ->withInput();
        }

        if (!in_array($from, $this->files->locales(), true)) {
            return back()->with('error', 'Язык-источник не найден.')->withInput();
        }

        try {
            $this->files->createLocale($code, $from);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('admin.localization.translations.edit', [$code])
            ->with('success', "Язык «{$code}» создан копированием из «{$from}». Теперь переведите строки.");
    }

    /** 🗑️ Удаление локали целиком. */
    public function destroy(string $locale): RedirectResponse
    {
        try {
            $this->files->deleteLocale($locale);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.localization.translations.index')
            ->with('success', "Язык «{$locale}» удалён.");
    }

    /**
     * Человеческое название языка. Берём из intl, если он собран,
     * иначе — из небольшого встроенного списка, иначе показываем код.
     */
    private function localeName(string $code): string
    {
        $known = [
            'ru' => 'Русский',
            'en' => 'English',
            'be' => 'Беларуская',
            'kk' => 'Қазақша',
            'uk' => 'Українська',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'it' => 'Italiano',
            'es' => 'Español',
            'pl' => 'Polski',
            'tr' => 'Türkçe',
            'zh' => '中文',
        ];

        if (isset($known[$code])) {
            return $known[$code];
        }

        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayLanguage($code, $code);
            if ($name !== '' && $name !== $code) {
                return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return strtoupper($code);
    }
}
