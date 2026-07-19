<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 🔐 Контроллер управления сессиями пользователей
 *
 * Отвечает за:
 * - 🧑‍💻 Отображение формы входа
 * - ✅ Обработку логина
 * - 🚪 Выход из системы
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * 🧾 create()
     *
     * 📄 Отображает страницу авторизации
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * 🔓 store()
     *
     * ✅ Обрабатывает запрос на вход пользователя
     *
     * 🔐 Используется `LoginRequest`, в котором определена логика аутентификации
     * 🔄 Регенерирует сессию после входа (защита от фиксации сессии)
     *
     * 🧭 Перенаправляет на желаемую страницу или на `dashboard`
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 🔐 Проверка логина и пароля
        $request->authenticate();

        // 🔄 Обновление ID сессии после входа
        $request->session()->regenerate();

        // 🚀 Редирект на запрошенную ранее страницу или дашборд
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * 🚪 destroy()
     *
     * ❌ Завершает сессию пользователя (выход)
     *
     * 🧹 Инвалидирует текущую сессию
     * 🆕 Генерирует новый CSRF-токен
     * 🔁 Редирект на главную страницу
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // ⛔ Выход пользователя из системы
        Auth::guard('web')->logout();

        // 🧼 Инвалидация текущей сессии
        $request->session()->invalidate();

        // 🔁 Генерация нового токена (безопасность CSRF)
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
