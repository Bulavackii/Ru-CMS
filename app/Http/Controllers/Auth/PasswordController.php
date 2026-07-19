<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * 🔐 PasswordController
 *
 * Контроллер для смены пароля авторизованного пользователя
 *
 * Используется, например, в личном кабинете (профиле)
 */
class PasswordController extends Controller
{
    /**
     * 🔄 update()
     *
     * ✅ Обновляет пароль пользователя:
     * - Проверяет текущий пароль
     * - Валидирует новый пароль
     * - Хеширует и сохраняет
     *
     * 🔐 Использует валидатор `current_password` для проверки старого пароля
     * 🔁 Возвращает пользователя обратно с flash-сообщением об успехе
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        // 📋 Валидация с указанием bag для сообщений (updatePassword)
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'], // 🔒 Проверка текущего пароля
            'password' => ['required', Password::defaults(), 'confirmed'], // 🆕 Новый пароль + подтверждение
        ]);

        // 💾 Обновление пароля пользователя
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // 🔔 Возврат с уведомлением об успехе
        return back()->with('status', 'password-updated');
    }
}
