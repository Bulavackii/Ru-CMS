<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить правила валидации, применимые к запросу.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required_if:active,1,true|nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:offline,online,sbp,yookassa,tbank,tinkoff,sberbank,sberpay,qiwi,robokassa,cloudpayments,unitpay,interkassa',
            'active' => 'boolean',
            // Маршрут объявлен как {id}, а не {paymentMethod}, поэтому
            // $this->paymentMethod всегда null и правило не исключало
            // саму запись: сохранить метод, не меняя код, было нельзя.
            'code' => 'nullable|string|max:50|unique:payment_methods,code,' . ($this->route('id') ?? 'null'),
            'is_russian' => 'boolean',
            'settings' => 'nullable|array',
            'commission' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:0|max:1000000',
            'max_amount' => 'nullable|numeric|min:0|max:1000000000|gte:min_amount',
            // Поле формы — строка «RUB, USD»; в массив её превращает
            // prepareForValidation(), правило проверяет уже массив.
            'currencies' => 'nullable|array',
            'currencies.*' => 'nullable|string|size:3',
            'test_mode' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'docs_url' => 'nullable|url|max:255',

            // 🇷🇺 Российские платежные системы - дополнительные поля
            'inn' => 'nullable|string|digits:10|regex:/^\d{10}$/',
            'bik' => 'nullable|string|digits:9|regex:/^\d{9}$/',
            'account' => 'nullable|string|digits:20|regex:/^\d{20}$/',
            'shop_id' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string|max:255',
            'terminal_key' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'shop_url' => 'nullable|url|max:255',
            'callback_url' => 'nullable|url|max:255',
            'success_url' => 'nullable|url|max:255',
            'fail_url' => 'nullable|url|max:255',
            'sandbox' => 'boolean',

            // 🏦 Банковские реквизиты для юрлиц
            'bank_name' => 'nullable|string|max:255',
            'correspondent_account' => 'nullable|string|digits:20|regex:/^\d{20}$/',
            'kpp' => 'nullable|string|digits:9|regex:/^\d{9}$/',

            // 📱 Дополнительные настройки
            'webhook_url' => 'nullable|url|max:255',
            'timeout' => 'nullable|integer|min:1|max:300',
            'retries' => 'nullable|integer|min:0|max:10',
        ];

        // Дополнительная валидация для российских платежных систем
        if ($this->has('type')) {
            switch ($this->type) {
                case 'yookassa':
                    $rules['shop_id'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['secret_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'tinkoff':
                    $rules['terminal_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['secret_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'sberbank':
                case 'sberpay':
                    $rules['api_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['inn'] = 'required|string|digits:10|regex:/^\d{10}$/';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'sbp':
                    $rules['bik'] = 'required|string|digits:9|regex:/^\d{9}$/';
                    $rules['account'] = 'required|string|digits:20|regex:/^\d{20}$/';
                    $rules['inn'] = 'nullable|string|digits:10|regex:/^\d{10}$/';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'qiwi':
                    $rules['api_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['shop_id'] = 'nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'robokassa':
                    $rules['shop_id'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['secret_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'cloudpayments':
                    $rules['api_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['public_id'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'unitpay':
                    $rules['shop_id'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['secret_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;

                case 'interkassa':
                    $rules['shop_id'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['secret_key'] = 'required_if:active,1,true|nullable|string|max:255';
                    $rules['is_russian'] = 'nullable|boolean';
                    break;
            }
        }

        return $rules;
    }

    /**
     * 🔴 Способ с драйвером-заглушкой включить нельзя.
     *
     * Форма — не последний рубеж (тот же отказ стоит в
     * PaymentGatewayService::createPayment), но именно здесь владелец узнаёт
     * причину. Иначе он включил бы СБП, увидел его на сайте и обнаружил
     * поломку только после первого покупателя.
     *
     * Выключать и сохранять такой способ по-прежнему можно — иначе нельзя
     * было бы поправить у него название или порядок.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('active')) {
                return;
            }

            $код = $this->input('code') ?: $this->input('type');

            if ((\Modules\Payments\Models\PaymentMethod::READINESS[$код] ?? null) === 'stub') {
                $validator->errors()->add(
                    'active',
                    __('admin.errors.payment_driver_stub')
                );
            }
        });
    }

    /**
     * Получить сообщения об ошибках для определённых атрибутов валидации.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название платежной системы обязательно',
            'type.required' => 'Тип платежной системы обязателен',
            'type.in' => 'Недопустимый тип платежной системы',
            'code.unique' => 'Этот код уже используется другой платежной системой',
            'is_russian.boolean' => 'Флаг российской системы должен быть булевым значением',
            'commission.numeric' => 'Комиссия должна быть числом',
            'commission.min' => 'Комиссия не может быть отрицательной',
            'commission.max' => 'Комиссия не может превышать 100%',
            'min_amount.numeric' => 'Минимальная сумма должна быть числом',
            'max_amount.numeric' => 'Максимальная сумма должна быть числом',
            'max_amount.gte' => 'Максимальная сумма должна быть больше или равна минимальной',
            'inn.digits' => 'ИНН должен состоять из 10 цифр',
            'bik.digits' => 'БИК должен состоять из 9 цифр',
            'account.digits' => 'Расчетный счет должен состоять из 20 цифр',
            'kpp.digits' => 'КПП должен состоять из 9 цифр',
            'correspondent_account.digits' => 'Корреспондентский счет должен состоять из 20 цифр',
            'currencies.*.size' => 'Код валюты должен состоять из 3 символов',
            'public_id.required' => 'Public ID обязателен для CloudPayments',
        ];
    }

    /**
     * Подготовить данные валидации.
     */
    public function prepareForValidation(): void
    {
        // Реквизиты форма шлёт вложенно (settings[shop_id]), а условные
        // правила и normalizeSettings() ждут их верхним уровнем. Поднимаем,
        // иначе метод с реквизитами не сохранялся вовсе: валидация требовала
        // shop_id, которого «нет».
        $settings = $this->input('settings');

        if (is_array($settings)) {
            $lifted = array_filter(
                $settings,
                fn ($value, $key) => is_string($key) && ! $this->has($key),
                ARRAY_FILTER_USE_BOTH
            );

            if ($lifted !== []) {
                $this->merge($lifted);
            }
        }

        $currencies = $this->input('currencies');

        if (is_string($currencies)) {
            $currencies = array_values(array_filter(array_map('trim', explode(',', $currencies)), static fn ($value) => $value !== ''));
        } elseif (is_array($currencies)) {
            $currencies = array_values(array_filter(array_map('trim', $currencies), static fn ($value) => $value !== ''));
        }

        if (is_array($currencies)) {
            $currencies = array_map(static fn ($value) => strtoupper($value), $currencies);
        }

        if ($currencies === []) {
            $currencies = null;
        }

        // Переработанная форма шлёт реквизиты как settings[shop_id] и т.д.,
        // а условные правила по типу требуют их на верхнем уровне
        // (shop_id, secret_key…). Без этого подъёма сохранить онлайн-метод
        // было невозможно: валидация падала на «shop id required».
        $settings = $this->input('settings');

        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                if (! $this->exists($key) || blank($this->input($key))) {
                    $this->merge([$key => $value]);
                }
            }
        }

        $this->merge([
            'currencies' => $currencies,
            'is_russian' => $this->boolean('is_russian'),
            'active' => $this->boolean('active'),
            'test_mode' => $this->boolean('test_mode'),
            'sandbox' => $this->has('sandbox'),
        ]);
    }
}

