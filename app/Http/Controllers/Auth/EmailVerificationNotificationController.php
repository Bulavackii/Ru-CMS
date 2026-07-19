<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 📧 EmailVerificationNotificationController
 *
 * Контроллер отправки письма для подтверждения email
 *
 * Используется, если пользователь запрашивает повторную отправку ссылки
 * на верификацию (например, с экрана "Подтвердите ваш email").
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * 📬 Метод store()
     *
     * 🔁 Отправляет новое письмо с ссылкой подтверждения email
     *
     * 🔐 Проверяет, был ли email уже верифицирован:
     *   - ✅ если да — перенаправляет на dashboard
     *   - 📧 если нет — отправляет уведомление повторно
     *
     * 📝 Добавляет flash-сообщение 'verification-link-sent'
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ Если email уже подтверждён — редиректим на главную страницу пользователя
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 📤 Отправляем письмо с верификационной ссылкой
        $request->user()->sendEmailVerificationNotification();

        // 🔔 Flash-сообщение, которое можно использовать во вьюхе
        return back()->with('status', 'verification-link-sent');
    }
}
