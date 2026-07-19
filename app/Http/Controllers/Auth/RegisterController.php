<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 📝 RegisterController
 *
 * Контроллер регистрации новых пользователей
 *
 * Отвечает за:
 * 🔹 Отображение формы регистрации
 * 🔹 Обработку создания нового пользователя
 * 🔹 Обработку регистрации юридических лиц
 * 🔹 Автоматическую авторизацию после регистрации
 * 🔹 Логирование регистраций
 */
class RegisterController extends Controller
{
    /**
     * 📄 showRegistrationForm()
     *
     * Отображает форму регистрации
     *
     * @return View
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * 🧾 register()
     *
     * Обрабатывает регистрацию нового пользователя:
     * 🔐 Валидация данных формы (через RegisterRequest с rate limiting)
     * 🔒 Хеширование пароля
     * 🆕 Создание записи в БД
     * 📝 Сохранение данных юридического лица (если указано)
     * 📣 Генерация события Registered
     * 🔓 Автоматический вход пользователя
     * 📊 Логирование регистрации
     * 🚀 Редирект на дашборд
     *
     * @param  RegisterRequest  $request
     * @return RedirectResponse
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // 💾 Создание нового пользователя
        $userData = [
            'name' => $request->validated()['name'],
            'email' => $request->validated()['email'],
            'password' => Hash::make($request->validated()['password']),
        ];

        // Если регистрация как юридическое лицо
        if ($request->boolean('is_legal')) {
            // Сохраняем данные организации в settings (можно расширить модель User)
            $userData['settings'] = [
                'is_legal' => true,
                'org_name' => $request->validated()['org_name'] ?? null,
                'ogrn' => $request->validated()['ogrn'] ?? null,
                'inn' => $request->validated()['inn'] ?? null,
                'kpp' => $request->validated()['kpp'] ?? null,
            ];
        }

        $user = User::create($userData);

        // 📣 Генерация события регистрации (для отправки email, уведомлений и т.д.)
        // Laravel автоматически отправляет email верификацию, если User реализует MustVerifyEmail
        event(new Registered($user));

        // 🔐 Автоматический вход после регистрации
        Auth::login($user);

        // 📊 Логирование успешной регистрации
        Log::info('User registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'is_legal' => $request->boolean('is_legal'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // 📦 Редирект на дашборд
        return redirect('/dashboard')->with('success', 'Регистрация успешно завершена! Добро пожаловать!');
    }
}
