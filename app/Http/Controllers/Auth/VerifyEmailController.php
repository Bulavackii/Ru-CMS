<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * ✅ VerifyEmailController
 *
 * Контроллер подтверждения email по ссылке из письма
 *
 * 📬 Ссылка обычно имеет вид:
 * `/verify-email/{id}/{hash}?expires=...&signature=...`
 */
class VerifyEmailController extends Controller
{
    /**
     * 📩 __invoke()
     *
     * Обрабатывает подтверждение email пользователя.
     *
     * 🔍 Проверяет:
     * - Если email уже подтверждён — редирект на dashboard с параметром ?verified=1
     * - Если ещё нет — помечает как подтверждён и генерирует событие Verified
     *
     * ⚠️ Использует `EmailVerificationRequest`, который уже встроенно:
     * - Проверяет подпись ссылки
     * - Сравнивает ID и hash
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // ✅ Если email уже подтверждён — просто редиректим
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        // 📌 Если email не подтверждён — подтверждаем и генерируем событие
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // 🚀 Редиректим с параметром, чтобы на клиенте можно было показать уведомление
        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }
}
