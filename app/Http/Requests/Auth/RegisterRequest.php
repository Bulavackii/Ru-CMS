<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

/**
 * 🛂 RegisterRequest
 *
 * FormRequest для обработки регистрации пользователя.
 * Отвечает за:
 * - 🔍 Валидацию данных регистрации
 * - 🔐 Проверку сложности пароля
 * - 🚫 Защиту от перебора (rate limit)
 */
class RegisterRequest extends FormRequest
{
    /**
     * ✅ authorize()
     *
     * Разрешает выполнение запроса (для всех гостей).
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
     * Правила валидации формы регистрации:
     * - name: обязателен, строка, максимум 255 символов
     * - email: обязателен, валидный email, уникальный в таблице users
     * - password: обязателен, минимум 8 символов, подтверждение, сложность
     * - terms_agree: обязателен (согласие с условиями)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $captchaType = config('captcha.default_type', 'image');
        $captchaEnabled = config('captcha.enabled', true);
        
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                Password::min(8)
                    ->mixedCase()      // Смешанный регистр (a-z и A-Z)
                    ->numbers()        // Цифры
                    ->symbols()        // Специальные символы
            ],
            'password_confirmation' => ['required', 'string'],
            'terms_agree' => ['required', 'accepted'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9\s()+\-]+$/'],

            // Реквизиты организации.
            //
            // Имена полей — как колонки в users (is_company, company_name, inn,
            // ogrn). Раньше здесь были is_legal/org_name/kpp, и контроллер
            // складывал их в settings, тогда как профиль в кабинете читает и
            // пишет колонки: зарегистрировавшись организацией, человек открывал
            // свой профиль и видел пустые реквизиты. Одна сущность — одно место
            // хранения. Поля kpp в таблице нет вовсе, и в профиле его тоже не
            // спрашивают, поэтому оно убрано.
            'is_company' => ['sometimes', 'boolean'],
            'company_name' => ['required_if:is_company,1', 'nullable', 'string', 'max:255'],
            'ogrn' => ['nullable', 'string', 'regex:/^\d{13,15}$/'],
            'inn' => ['required_if:is_company,1', 'nullable', 'string', 'regex:/^\d{10,12}$/'],
        ];

        // Добавляем капчу, если она включена
        if ($captchaEnabled && class_exists(\Modules\Captcha\Services\CaptchaService::class)) {
            $rules['captcha'] = ['required', 'captcha:' . $captchaType];
        }

        return $rules;
    }

    /**
     * 📝 messages()
     *
     * Кастомные сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Имя" обязательно для заполнения.',
            'name.max' => 'Имя не должно превышать 255 символов.',
            
            'email.required' => 'Поле "Email" обязательно для заполнения.',
            'email.email' => 'Введите корректный адрес электронной почты.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            
            'password.required' => 'Поле "Пароль" обязательно для заполнения.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
            'password.mixed' => 'Пароль должен содержать буквы в верхнем и нижнем регистре.',
            'password.numbers' => 'Пароль должен содержать хотя бы одну цифру.',
            'password.symbols' => 'Пароль должен содержать хотя бы один специальный символ.',
            
            'password_confirmation.required' => 'Подтверждение пароля обязательно.',
            
            'terms_agree.required' => 'Необходимо согласиться с условиями использования.',
            'terms_agree.accepted' => 'Необходимо согласиться с условиями использования.',
            
            'org_name.required_if' => 'Наименование организации обязательно для юридических лиц.',
            'ogrn.required_if' => 'ОГРН обязателен для юридических лиц.',
            'ogrn.regex' => 'ОГРН должен состоять из 13 цифр.',
            'inn.required_if' => 'ИНН обязателен для юридических лиц.',
            'inn.regex' => 'ИНН должен состоять из 10 или 12 цифр.',
            'kpp.required_if' => 'КПП обязателен для юридических лиц.',
            'kpp.regex' => 'КПП должен состоять из 9 цифр.',
            
            'captcha.required' => 'Пожалуйста, введите код с картинки.',
            'captcha.captcha' => 'Неверный код каптчи. Пожалуйста, попробуйте еще раз.',
        ];
    }

    /**
     * 🛡️ ensureIsNotRateLimited()
     *
     * Проверяет, не превышен ли лимит попыток регистрации.
     * По умолчанию: не более 3 попыток в час на IP.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = 'register:' . $this->ip();
        
        if (!RateLimiter::tooManyAttempts($key, 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * 🔄 prepareForValidation()
     *
     * Подготовка данных перед валидацией.
     */
    protected function prepareForValidation(): void
    {
        // Проверка rate limiting
        $this->ensureIsNotRateLimited();
        
        // Нормализация email (приведение к нижнему регистру)
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->input('email'))),
            ]);
        }
    }

    /**
     * ✅ withValidator()
     *
     * Дополнительная валидация после базовой.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // После успешной валидации увеличиваем счетчик попыток
            $key = 'register:' . $this->ip();
            RateLimiter::hit($key, 3600); // 1 час
        });
    }
}

