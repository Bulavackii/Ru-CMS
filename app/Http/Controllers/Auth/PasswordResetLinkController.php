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
     * 🚫 Rate limiting: не более 3 попыток в час на IP
     *
     * 📤 Использует `Password::sendResetLink()` для отправки письма
     *
     * 📝 Логирует попытки восстановления пароля
     *
     * 🔁 Возвращает сообщение об успехе или ошибке
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 🚫 Rate limiting для защиты от спама
        $key = 'password-reset:' . $request->ip();
        $maxAttempts = 3; // 3 попытки
        $decayMinutes = 60; // в час

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        // ✅ Валидация email и капчи
        $captchaType = config('captcha.default_type', 'image');
        $captchaEnabled = config('captcha.enabled', true);
        
        $rules = [
            'email' => 'required|email',
        ];
        
        // Добавляем капчу, если она включена
        if ($captchaEnabled && class_exists(\Modules\Captcha\Services\CaptchaService::class)) {
            $rules['captcha'] = 'required|captcha:' . $captchaType;
        }
        
        $request->validate($rules, [
            'captcha.required' => 'Пожалуйста, введите код с картинки.',
            'captcha.captcha' => 'Неверный код каптчи. Пожалуйста, попробуйте еще раз.',
        ]);

        // 📤 Отправляем ссылку сброса пароля
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Увеличиваем счетчик попыток
        \Illuminate\Support\Facades\RateLimiter::hit($key, $decayMinutes * 60);

        // 📝 Логирование попытки восстановления
        \Illuminate\Support\Facades\Log::info('Password reset requested', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'status' => $status,
            'user_agent' => $request->userAgent(),
        ]);

        // 🟢 Успешно — уведомляем пользователя (всегда показываем успех для безопасности)
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Если указанный email существует в нашей системе, мы отправили на него ссылку для сброса пароля.');
        }

        // 🔴 Ошибка — но показываем то же сообщение для безопасности (не раскрываем существование email)
        return back()->with('status', 'Если указанный email существует в нашей системе, мы отправили на него ссылку для сброса пароля.');
    }
}
