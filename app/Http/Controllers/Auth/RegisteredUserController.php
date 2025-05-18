<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * 📝 RegisteredUserController
 *
 * Контроллер для регистрации нового пользователя
 *
 * Отвечает за:
 * 🔹 Отображение формы регистрации
 * 🔹 Обработку запроса регистрации
 * 🔹 Создание пользователя, генерацию события и вход
 */
class RegisteredUserController extends Controller
{
    /**
     * 📄 create()
     *
     * Показывает форму регистрации пользователя.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * 🧾 store()
     *
     * Обрабатывает входящий запрос на регистрацию:
     * - 🔍 Валидирует данные (имя, email, пароль)
     * - 💾 Создаёт нового пользователя
     * - 📣 Генерирует событие Registered (для отправки письма и т.п.)
     * - 🔐 Выполняет вход под созданным пользователем
     * - 🚀 Перенаправляет на dashboard
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ Валидация полей
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()], // 🔐 Строгая проверка
        ]);

        // 💾 Создание нового пользователя
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 📣 Генерация события регистрации (можно использовать Listener для отправки письма)
        event(new Registered($user));

        // 🔐 Вход под новым пользователем
        Auth::login($user);

        // 🚀 Редирект на дашборд
        return redirect(route('dashboard', absolute: false));
    }
}
