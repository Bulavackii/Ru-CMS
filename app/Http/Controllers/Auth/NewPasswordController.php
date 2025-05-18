<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

/**
 * 🔐 NewPasswordController
 *
 * Контроллер для сброса пароля по ссылке из письма
 *
 * Отвечает за:
 * - 🧾 Показ формы ввода нового пароля
 * - ✅ Обработку запроса на сброс пароля
 */
class NewPasswordController extends Controller
{
    /**
     * 🧾 create()
     *
     * 📄 Отображает форму сброса пароля (с токеном)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return view('auth.reset-password', [
            'request' => $request, // Передаём токен и email через URL-параметры
        ]);
    }

    /**
     * 🔄 store()
     *
     * ✅ Обрабатывает сброс пароля:
     * - Валидация токена, email и нового пароля
     * - Сброс пароля через фасад Password
     * - Хеширование нового пароля и сохранение
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 📌 Валидация данных из формы
        $request->validate([
            'token' => 'required',                        // Токен из ссылки
            'email' => 'required|email',                  // Email пользователя
            'password' => 'required|min:8|confirmed',     // Новый пароль + подтверждение
        ]);

        // 🔧 Попытка сброса пароля через Laravel Password Broker
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                // 🔑 Обновляем пароль пользователя
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        // 📬 Если успешно — редирект на страницу входа с сообщением
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
