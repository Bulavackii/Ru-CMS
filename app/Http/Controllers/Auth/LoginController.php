<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 🔐 LoginController
 *
 * Контроллер для отображения формы входа и обработки логина пользователя.
 */
class LoginController extends Controller
{
    /**
     * 📄 showLoginForm()
     *
     * 🔓 Показывает форму авторизации
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * ✅ login()
     *
     * 🔐 Обрабатывает вход пользователя:
     *   - 🔍 Валидирует email и пароль
     *   - 🧪 Проверяет учётные данные через Auth::attempt
     *   - 🔄 Регенерирует сессию после входа (защита от фиксации)
     *   - 🚫 В случае ошибки — возвращает с сообщением
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // 🔍 Валидация данных формы входа
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 🔐 Попытка входа с проверкой email и пароля
        if (Auth::attempt($credentials)) {
            // 🔄 Генерация новой сессии для безопасности
            $request->session()->regenerate();

            // ✅ Успешный вход — перенаправление в админку
            return redirect()->intended('/admin/modules');
        }

        // ❌ Ошибка входа — возвращаем с сообщением об ошибке
        return back()->withErrors([
            'email' => 'Неверные данные для входа.',
        ]);
    }
}
