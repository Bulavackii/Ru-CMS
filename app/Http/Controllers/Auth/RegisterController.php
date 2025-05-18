<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * 📝 RegisterController
 *
 * Контроллер регистрации новых пользователей
 *
 * Отвечает за:
 * 🔹 Отображение формы регистрации
 * 🔹 Обработку создания нового пользователя
 * 🔹 Автоматическую авторизацию после регистрации
 */
class RegisterController extends Controller
{
    /**
     * 📄 showRegistrationForm()
     *
     * Отображает форму регистрации
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * 🧾 register()
     *
     * Обрабатывает регистрацию нового пользователя:
     * 🔐 Валидация данных формы
     * 🔒 Хеширование пароля
     * 🆕 Создание записи в БД
     * 🔓 Автоматический вход пользователя
     * 🚀 Редирект на дашборд
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        // ✅ Валидация входных данных
        $validated = $request->validate([
            'name' => 'required|string|max:255',             // Имя обязательно
            'email' => 'required|email|unique:users',        // Уникальный email
            'password' => 'required|string|confirmed|min:6', // Пароль + подтверждение
        ]);

        // 💾 Создание нового пользователя
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Хешируем пароль
        ]);

        // 🔐 Автоматический вход после регистрации
        Auth::login($user);

        // 📦 Редирект на дашборд или главную
        return redirect('/dashboard');
    }
}
