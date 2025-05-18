<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * 🔐 PasswordController
 *
 * Контроллер смены пароля пользователем в личном кабинете
 */
class PasswordController extends Controller
{
    /**
     * 🔑 edit()
     *
     * Показывает форму смены пароля.
     * Обычно содержит поля:
     * - текущий пароль
     * - новый пароль
     * - подтверждение пароля
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        return view('frontend.dashboard.password');
    }

    /**
     * 🔁 update()
     *
     * Обновляет пароль пользователя:
     * - 🔐 Проверяет текущий пароль через `Hash::check()`
     * - 🧾 Валидирует новый пароль и его подтверждение
     * - 💾 Обновляет пароль (хешированный)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // 📋 Валидация формы
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8|confirmed', // confirmation -> new_password_confirmation
        ]);

        // 👤 Получаем текущего пользователя
        $user = Auth::user();

        // ❌ Проверка текущего пароля
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => '❌ Текущий пароль введён неверно',
            ]);
        }

        // ✅ Обновляем пароль
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 🔔 Возвращаем с успешным уведомлением
        return back()->with('success', '✅ Пароль успешно обновлён');
    }
}
