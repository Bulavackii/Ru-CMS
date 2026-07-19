<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\LoginHistoryService;

/**
 * 🛂 LoginRequest
 *
 * FormRequest для обработки логина пользователя.
 * Отвечает за:
 * - 🔍 Валидацию email и пароля
 * - 🚫 Защиту от перебора (rate limit)
 * - 🔐 Аутентификацию через `Auth::attempt()`
 */
class LoginRequest extends FormRequest
{
    /**
     * ✅ authorize()
     *
     * Разрешает выполнение запроса (для всех).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 📋 rules()
     *
     * Правила валидации формы входа:
     * - email: обязателен, строка, валидный формат
     * - password: обязателен, строка
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * 🔐 authenticate()
     *
     * Выполняет попытку входа:
     * - Проверяет, не превышен ли лимит попыток
     * - Пытается авторизовать пользователя
     * - Если неудачно — увеличивает счётчик RateLimiter, логирует попытку и выбрасывает ошибку
     * - Если успешно — сбрасывает счётчик попыток
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->input('email');

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey()); // Увеличиваем счётчик попыток

            // Логируем неудачную попытку входа
            if (app()->bound(LoginHistoryService::class)) {
                try {
                    $loginHistoryService = app(LoginHistoryService::class);
                    $loginHistoryService->logLoginAttempt(
                        null,
                        $email,
                        $this,
                        'failed',
                        'wrong_password'
                    );
                } catch (\Exception $e) {
                    // Игнорируем ошибки логирования
                }
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'), // ❌ Неверный логин/пароль
            ]);
        }

        RateLimiter::clear($this->throttleKey()); // ✅ Сброс счётчика после успешного входа
    }

    /**
     * 🛡️ ensureIsNotRateLimited()
     *
     * Проверяет, не превышен ли лимит попыток входа.
     * По умолчанию: не более 5 попыток на IP+email.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this)); // ⏳ Вызываем событие блокировки

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * 🧠 throttleKey()
     *
     * Генерирует уникальный ключ для учёта попыток:
     * 💡 email + IP → translit → lowercase
     *
     * @return string
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')) . '|' . $this->ip()
        );
    }
}
