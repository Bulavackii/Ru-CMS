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
}
