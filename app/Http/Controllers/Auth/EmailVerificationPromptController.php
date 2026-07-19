<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 📩 EmailVerificationPromptController
 *
 * Контроллер-однострочник (Single Action Controller), вызываемый как middleware
 *
 * Отвечает за:
 * 🔹 Отображение страницы с просьбой подтвердить email
 * 🔹 Или перенаправление на дашборд, если email уже подтверждён
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * 🔄 __invoke()
     *
     * 📌 Метод вызывается, когда маршрут связан напрямую с этим контроллером.
     *
     * 🔍 Проверяет:
     *   - Если email пользователя уже подтверждён → редирект на dashboard
     *   - Иначе → отображает форму `verify-email.blade.php`
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false)) // ✅ Email подтверждён — идём дальше
            : view('auth.verify-email'); // ⏳ Email не подтверждён — просим подтвердить
    }
}
