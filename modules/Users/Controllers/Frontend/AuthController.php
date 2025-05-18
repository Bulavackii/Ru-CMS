<?php

namespace Modules\Users\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Users\Models\User;

/**
 * 🔐 Контроллер авторизации пользователей (клиентская часть)
 */
class AuthController extends Controller
{
    /**
     * 🧾 Отображение формы входа
     */
    public function showLoginForm()
    {
        return view('Users::frontend.login');
    }

    /**
     * 🔓 Обработка логина
     */
    public function login(Request $request)
    {
        // 📋 Валидация данных
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // ✅ Попытка входа
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // 🔁 Редирект в админку или личный кабинет
            return Auth::user()->is_admin
                ? redirect('/admin/modules') // 🛠 Админка
                : redirect('/dashboard');    // 👤 Кабинет пользователя
        }

        // ❌ Ошибка авторизации
        return back()->withErrors([
            'email' => 'Неверный логин или пароль',
        ])->onlyInput('email');
    }

    /**
     * 🚪 Выход из аккаунта
     */
    public function logout(Request $request)
    {
        Auth::logout(); // ⛔ Завершаем сессию
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/'); // 🔙 На главную
    }
}
