<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryMethodRequest extends FormRequest
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
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'active' => 'boolean',
            'code' => 'nullable|string|max:50|unique:delivery_methods,code,' . ($this->delivery?->id ?? 'null'),
            'is_russian' => 'boolean',
            'api_enabled' => 'boolean',
            'api_settings' => 'nullable|array',
            'type' => 'required|in:courier,pickup,post,terminal',
            'min_days' => 'nullable|integer|min:0|max:365',
            'max_days' => 'nullable|integer|min:0|max:365|gte:min_days',
            'weight_limit' => 'nullable|numeric|min:0|max:1000',
            'regions' => 'nullable|array',
            'regions.*' => 'nullable|string|max:255',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Получить сообщения об ошибках для определённых атрибутов валидации.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название метода доставки обязательно',
            'price.required' => 'Стоимость доставки обязательна',
            'price.numeric' => 'Стоимость должна быть числом',
            'price.min' => 'Стоимость не может быть отрицательной',
            'code.unique' => 'Этот код уже используется другим методом доставки',
            'is_russian.boolean' => 'Флаг российской службы должен быть булевым значением',
            'api_enabled.boolean' => 'Флаг API интеграции должен быть булевым значением',
            'type.required' => 'Тип доставки обязателен',
            'type.in' => 'Недопустимый тип доставки',
            'min_days.integer' => 'Минимальные сроки должны быть целым числом',
            'max_days.integer' => 'Максимальные сроки должны быть целым числом',
            'max_days.gte' => 'Максимальные сроки должны быть больше или равны минимальным',
            'weight_limit.numeric' => 'Ограничение по весу должно быть числом',
            'weight_limit.max' => 'Ограничение по весу слишком большое',
        ];
    }

    /**
     * Подготовить данные валидации.
     */
    public function prepareForValidation(): void
    {
        $regions = $this->input('regions');

        if (is_string($regions)) {
            $regions = array_values(array_filter(array_map('trim', explode(',', $regions)), static fn ($value) => $value !== ''));
        } elseif (is_array($regions)) {
            $regions = array_values(array_filter(array_map('trim', $regions), static fn ($value) => $value !== ''));
        }

        if ($regions === []) {
            $regions = null;
        }

        $this->merge([
            'regions' => $regions,
            'is_russian' => $this->has('is_russian'),
            'active' => $this->has('active'),
            'api_enabled' => $this->has('api_enabled'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}

