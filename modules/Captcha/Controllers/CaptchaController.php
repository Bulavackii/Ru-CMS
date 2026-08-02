<?php

namespace Modules\Captcha\Controllers;

use App\Http\Controllers\Controller;
use Modules\Captcha\Models\CaptchaPreset;
use Modules\Captcha\Models\CaptchaStat;
use Modules\Captcha\Services\CaptchaService;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    protected $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    /**
     * Генерация каптчи (API)
     */
    public function generate(Request $request, $type = 'image')
    {
        $options = $request->input('options', []);

        $captcha = $this->captchaService->generate($type, $options);

        return response()->json([
            'success' => true,
            'type' => $type,
            'html' => $captcha['html'],
            'code' => $captcha['code'] ?? null, // Только для тестирования!
        ]);
    }

    /**
     * Проверка каптчи
     */
    public function verify(Request $request)
    {
        $request->validate([
            'captcha' => 'required|string',
            'type' => 'required|in:image,slider,math,question',
        ]);

        $isValid = $this->captchaService->verify($request->captcha, $request->type);

        return response()->json([
            'success' => $isValid,
            'message' => $isValid ? 'Каптча верна' : 'Неверный код',
        ]);
    }

    /**
     * Рендер каптчи (для Blade)
     */
    public function render(Request $request, $type = 'image')
    {
        $options = $request->input('options', []);
        $html = $this->captchaService->render($type, $options);

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * JavaScript виджет
     */
    public function widget(Request $request)
    {
        $type = $request->input('type', 'image');
        $selector = $request->input('selector', '#captcha-container');

        $js = $this->captchaService->renderJS($selector, $type);

        return response()->json([
            'success' => true,
            'script' => $js,
        ]);
    }

    /**
     * 🛡️ Конструктор каптчи в панели: сборка мышью, живое превью, сохранённые
     * пресеты. Вьюха существовала и раньше, но маршрута к ней не было вовсе —
     * открыть её было нельзя.
     *
     * Состояние модуля берём из конфига, а не из литералов во вьюхе: страница
     * не должна врать о том, включена каптча или нет.
     */
    public function admin()
    {
        return view('Captcha::admin.index', [
            'enabled'     => (bool) config('captcha.enabled', true),
            'defaultType' => (string) config('captcha.default_type', 'image'),
            'presets'     => CaptchaPreset::orderByDesc('id')->get(),
            'defaults'    => self::defaultOptions(),
            'questions'   => CaptchaService::defaultQuestions(),
            'stats'       => CaptchaStat::totals(),
        ]);
    }

    /**
     * Живое превью конструктора. Отдаёт ровно то же, что получит посетитель:
     * превью строится тем же CaptchaService::render(), а не отдельной
     * «показательной» разметкой, которая могла бы разойтись с реальностью.
     */
    public function preview(Request $request)
    {
        $data = $this->validatePreset($request, withName: false);

        return response()->json([
            'html' => $this->captchaService->render($data['type'], $data['options']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePreset($request);

        $preset = CaptchaPreset::create([
            'name'      => $data['name'],
            'type'      => $data['type'],
            'options'   => $data['options'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.captcha.index')
            ->with('success', "Сборка «{$preset->name}» сохранена");
    }

    public function update(Request $request, CaptchaPreset $preset)
    {
        $data = $this->validatePreset($request);

        $preset->update([
            'name'      => $data['name'],
            'type'      => $data['type'],
            'options'   => $data['options'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.captcha.index')
            ->with('success', "Сборка «{$preset->name}» обновлена");
    }

    public function duplicate(CaptchaPreset $preset)
    {
        $copy = CaptchaPreset::create([
            'name'      => $preset->name . ' (копия)',
            'type'      => $preset->type,
            'options'   => $preset->options,
            // Копия выключена: она ещё нигде не вставлена, и включать её
            // самостоятельно за владельца незачем
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.captcha.index')
            ->with('success', "Создана копия «{$copy->name}» — она выключена");
    }

    public function destroy(CaptchaPreset $preset)
    {
        $name = $preset->name;
        $preset->delete();

        return redirect()
            ->route('admin.captcha.index')
            ->with('success', "Сборка «{$name}» удалена. Вставленные шорткоды перестанут что-либо показывать");
    }

    /**
     * Разбор настроек конструктора.
     *
     * Принимаем только те параметры, которые CaptchaService действительно
     * читает для выбранного типа: складывать в базу ручки, ни на что не
     * влияющие, — значит обещать пользователю несуществующую настройку.
     */
    private function validatePreset(Request $request, bool $withName = true): array
    {
        $rules = [
            'type' => 'required|in:' . implode(',', CaptchaPreset::TYPES),
        ];

        if ($withName) {
            $rules['name'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules + [
            'length'      => 'nullable|integer|min:3|max:10',
            'width'       => 'nullable|integer|min:80|max:600',
            'height'      => 'nullable|integer|min:30|max:200',
            'noise'       => 'nullable|integer|min:0|max:3',
            'lines'       => 'nullable|integer|min:0|max:10',
            'tolerance'   => 'nullable|integer|min:4|max:40',
            'min'         => 'nullable|integer|min:0|max:999',
            'max'         => 'nullable|integer|min:1|max:1000',
            'operations'  => 'nullable|array',
            'operations.*' => 'in:+,-,*',
            'questions'   => 'nullable|array|max:30',
            'questions.*.q' => 'nullable|string|max:255',
            'questions.*.a' => 'nullable|string|max:255',
        ]);

        $type = $validated['type'];
        $options = [];

        foreach (self::optionKeys($type) as $key) {
            if (array_key_exists($key, $validated) && $validated[$key] !== null) {
                $options[$key] = $validated[$key];
            }
        }

        if ($type === 'math') {
            // Пустой список операций сделал бы пример неразрешимым
            $options['operations'] = array_values(array_unique($options['operations'] ?? [])) ?: ['+'];

            if (isset($options['min'], $options['max']) && $options['max'] <= $options['min']) {
                $options['max'] = $options['min'] + 1;
            }
        }

        if ($type === 'question') {
            $options['questions'] = array_values(array_filter(
                $options['questions'] ?? [],
                fn ($pair) => trim((string) ($pair['q'] ?? '')) !== '' && trim((string) ($pair['a'] ?? '')) !== ''
            ));

            // Ни одного своего вопроса — берём набор по умолчанию, иначе
            // спрашивать будет нечего
            if (!$options['questions']) {
                unset($options['questions']);
            }
        }

        return [
            'name'    => $validated['name'] ?? '',
            'type'    => $type,
            'options' => $options,
        ];
    }

    /** Какие параметры имеют смысл для каждого типа. */
    public static function optionKeys(string $type): array
    {
        return match ($type) {
            'image'    => ['length', 'width', 'height', 'noise', 'lines'],
            'slider'   => ['width', 'height', 'tolerance'],
            'math'     => ['min', 'max', 'operations'],
            'question' => ['questions'],
            default    => [],
        };
    }

    /** Значения по умолчанию для конструктора. */
    public static function defaultOptions(): array
    {
        return [
            'image'    => ['length' => 5, 'width' => 200, 'height' => 60, 'noise' => 1, 'lines' => 5],
            'slider'   => ['width' => 260, 'height' => 44, 'tolerance' => 10],
            'math'     => ['min' => 1, 'max' => 20, 'operations' => ['+', '-']],
            'question' => ['questions' => []],
        ];
    }
}
