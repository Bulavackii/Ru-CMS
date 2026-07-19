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
     * - Валидация токена, email и нового пароля (с проверкой сложности)
     * - Сброс пароля через фасад Password
     * - Хеширование нового пароля и сохранение
     * - 📝 Логирование сброса пароля
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 📌 Валидация данных из формы с усиленными требованиями к паролю
        $request->validate([
            'token' => 'required',                        // Токен из ссылки
            'email' => 'required|email',                  // Email пользователя
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->mixedCase()      // Смешанный регистр (a-z и A-Z)
                    ->numbers()        // Цифры
                    ->symbols()        // Специальные символы
            ],
        ], [
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.mixed' => 'Пароль должен содержать буквы в верхнем и нижнем регистре.',
            'password.numbers' => 'Пароль должен содержать хотя бы одну цифру.',
            'password.symbols' => 'Пароль должен содержать хотя бы один специальный символ.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        // 🔧 Попытка сброса пароля через Laravel Password Broker
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                // 🔑 Обновляем пароль пользователя
                $user->password = Hash::make($password);
                $user->save();

                // 📝 Логирование успешного сброса пароля
                \Illuminate\Support\Facades\Log::info('Password reset successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                ]);
            }
        );

        // 📬 Если успешно — редирект на страницу входа с сообщением
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Пароль успешно изменен. Теперь вы можете войти с новым паролем.');
        }

        // 🔴 Ошибка — показываем сообщение
        return back()->withErrors(['email' => [__($status)]]);
    }
}
