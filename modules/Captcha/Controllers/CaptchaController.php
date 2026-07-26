<?php

namespace Modules\Captcha\Controllers;

use App\Http\Controllers\Controller;
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
     * 🛡️ Страница модуля в панели: типы каптчи, живая демонстрация и памятка
     * по встраиванию. Вьюха существовала, но маршрута к ней не было — открыть
     * её было нельзя.
     *
     * Данные берём из конфига (modules/Captcha/Config/captcha.php), а не из
     * литералов во вьюхе: включённость и тип по умолчанию должны показываться
     * настоящие, иначе страница врёт о состоянии модуля.
     */
    public function admin()
    {
        return view('Captcha::admin.index', [
            'enabled'     => (bool) config('captcha.enabled', true),
            'defaultType' => (string) config('captcha.default_type', 'image'),
            'types'       => (array) config('captcha.types', []),
        ]);
    }
}
