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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:offline,online,sbp,yookassa,tinkoff,sberbank,sberpay,qiwi,robokassa,cloudpayments,unitpay,interkassa',
            'active' => 'boolean',
            'code' => 'nullable|string|max:50|unique:payment_methods,code,' . ($this->paymentMethod?->id ?? 'null'),
            'is_russian' => 'boolean',
            'settings' => 'nullable|array',
            'commission' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:0|max:1000000',
            'max_amount' => 'nullable|numeric|min:0|max:1000000000|gte:min_amount',
            'currencies' => 'nullable|array',
            'currencies.*' => 'nullable|string|size:3',
            'test_mode' => 'boolean',

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
                    $rules['shop_id'] = 'required|string|max:255';
                    $rules['secret_key'] = 'required|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'tinkoff':
                    $rules['terminal_key'] = 'required|string|max:255';
                    $rules['secret_key'] = 'required|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'sberbank':
                case 'sberpay':
                    $rules['api_key'] = 'required|string|max:255';
                    $rules['inn'] = 'required|string|digits:10|regex:/^\d{10}$/';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'sbp':
                    $rules['bik'] = 'required|string|digits:9|regex:/^\d{9}$/';
                    $rules['account'] = 'required|string|digits:20|regex:/^\d{20}$/';
                    $rules['inn'] = 'nullable|string|digits:10|regex:/^\d{10}$/';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'qiwi':
                    $rules['api_key'] = 'required|string|max:255';
                    $rules['shop_id'] = 'nullable|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'robokassa':
                    $rules['shop_id'] = 'required|string|max:255';
                    $rules['secret_key'] = 'required|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'cloudpayments':
                    $rules['api_key'] = 'required|string|max:255';
                    $rules['public_id'] = 'required|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'unitpay':
                    $rules['shop_id'] = 'required|string|max:255';
                    $rules['secret_key'] = 'required|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;

                case 'interkassa':
                    $rules['shop_id'] = 'required|string|max:255';
                    $rules['secret_key'] = 'required|string|max:255';
                    $rules['is_russian'] = 'required|boolean';
                    break;
            }
        }

        return $rules;
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

        $this->merge([
            'currencies' => $currencies,
            'is_russian' => $this->has('is_russian'),
            'active' => $this->has('active'),
            'test_mode' => $this->has('test_mode'),
            'sandbox' => $this->has('sandbox'),
        ]);
    }
}

