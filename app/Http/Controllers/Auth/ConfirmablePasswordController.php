<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 🔐 ConfirmablePasswordController
 *
 * Контроллер для подтверждения пароля при выполнении чувствительных действий
 * (например, смена email, удаление аккаунта и т.п.)
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * 🔎 show()
     *
     * 📄 Показывает форму подтверждения пароля
     *
     * Обычно вызывается, если сессия подтверждения устарела
     *
     * @return \Illuminate\View\View
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * ✅ store()
     *
     * 🔐 Проверяет, что введённый пароль совпадает с текущим паролем пользователя
     *
     * 🔁 Если всё верно — обновляет метку времени последнего подтверждения
     * ❌ Если пароль неверный — выбрасывает ValidationException с сообщением
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 🔐 Проверяем, верен ли введённый пароль
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email, // 👤 Email текущего пользователя
            'password' => $request->password    // 🔑 Введённый пароль
        ])) {
            // ❌ Неверный пароль — возвращаем ошибку валидации
            throw ValidationException::withMessages([
                'password' => __('auth.password'), // сообщение об ошибке (локализованное)
            ]);
        }

        // 🕓 Устанавливаем время последнего подтверждения пароля
        $request->session()->put('auth.password_confirmed_at', time());

        // ✅ Перенаправляем на нужную страницу
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
