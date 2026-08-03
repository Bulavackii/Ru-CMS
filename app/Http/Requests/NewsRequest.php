<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->can('manage-news'));
    }

    public function rules(): array
    {
        $newsId = $this->route('news')?->id;

        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:news,slug,' . ($newsId ?? 'NULL') . ',id',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'published' => 'nullable|boolean',
            // Список шаблонов — из общей константы, а не своей копией:
            // эта разошлась и не пропускала новые шаблоны («Выбран
            // недопустимый шаблон» при сохранении «Игр» и «Клиники»).
            'template' => 'nullable|string|max:50|in:' . implode(',', array_keys(
                \Modules\News\Controllers\Admin\NewsController::TEMPLATES
            )),
            'rating' => 'nullable|numeric|min:0|max:10',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_promo' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'meta_header' => 'nullable|string|max:255',
            'force_seo' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Название обязательно для заполнения',
            'title.max' => 'Название не должно превышать 255 символов',
            'slug.unique' => 'Такой URL уже существует. Выберите другой.',
            'slug.regex' => 'URL может содержать только латинские буквы, цифры и дефисы',
            'template.in' => 'Выбран недопустимый шаблон',
            'price.numeric' => 'Цена должна быть числом',
            'stock.integer' => 'Остаток должен быть целым числом',
            'categories.*.exists' => 'Выбранная категория не существует',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
            'content' => 'содержание',
            'template' => 'шаблон',
            'price' => 'цена',
            'stock' => 'остаток',
        ];
    }
}
