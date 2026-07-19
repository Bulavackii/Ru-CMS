<?php

namespace Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 📋 Form Request для валидации данных категории
 */
class CategoryRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Получить правила валидации, применимые к запросу.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:categories,slug,' . ($categoryId ?? 'NULL') . ',id',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id|not_in:' . ($categoryId ?? '0'),
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Получить сообщения об ошибках для определённых атрибутов валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название категории обязательно для заполнения',
            'title.max' => 'Название не должно превышать 255 символов',
            'slug.unique' => 'Такой URL уже существует. Выберите другой.',
            'slug.regex' => 'URL может содержать только латинские буквы, цифры и дефисы',
            'description.max' => 'Описание не должно превышать 1000 символов',
            'type.max' => 'Тип не должен превышать 50 символов',
            'icon.max' => 'Иконка не должна превышать 100 символов',
            'parent_id.exists' => 'Выбранная родительская категория не существует',
            'parent_id.not_in' => 'Категория не может быть родителем самой себя',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
        ];
    }

    /**
     * Получить пользовательские имена атрибутов для сообщений об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'название',
            'slug' => 'URL',
            'description' => 'описание',
            'type' => 'тип',
            'icon' => 'иконка',
            'parent_id' => 'родительская категория',
            'sort_order' => 'порядок сортировки',
            'is_active' => 'активность',
        ];
    }

    /**
     * Подготовить данные для валидации.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Если slug не указан, оставляем null (будет сгенерирован в модели)
        if ($this->has('slug') && empty($this->slug)) {
            $this->merge(['slug' => null]);
        }

        // Преобразуем checkbox в boolean
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => false]);
        }
    }
}




