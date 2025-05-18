<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * 📧 PasswordResetLinkController
 *
 * Контроллер для запроса ссылки сброса пароля
 *
 * Отвечает за:
 * 🔹 Отображение формы "Забыли пароль?"
 * 🔹 Отправку письма со ссылкой для сброса пароля
 */
class PasswordResetLinkController extends Controller
{
    /**
     * 🧾 create()
     *
     * 📄 Показывает форму запроса ссылки на сброс пароля
     * (ввод email и кнопка "Отправить ссылку")
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * 📬 store()
     *
     * ✉️ Обрабатывает отправку ссылки для сброса пароля
     *
     * 🔍 Валидация:
     * - email обязателен и должен быть валидным
     *
     * 📤 Использует `Password::sendResetLink()` для отправки письма
     *
     * 🔁 Возвращает сообщение об успехе или ошибке
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ✅ Валидация email
        $request->validate([
            'email' => 'required|email',
        ]);

        // 📤 Отправляем ссылку сброса пароля
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // 🟢 Успешно — уведомляем пользователя
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // 🔴 Ошибка — показываем сообщение
        return back()->withErrors([
            'email' => __($status),
        ]);
    }
}
