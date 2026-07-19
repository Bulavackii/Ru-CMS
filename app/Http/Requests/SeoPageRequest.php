<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeoPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->can('manage-seo'));
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $slugRule = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? 'sometimes|nullable|string|max:1024|unique:seo_pages,slug,' . ($id ?? 'NULL') . ',id,deleted_at,NULL'
            : 'required|string|max:1024|unique:seo_pages,slug,NULL,id,deleted_at,NULL';

        return [
            'slug'                => $slugRule,
            'title'               => 'nullable|string|max:255',
            'h1'                  => 'nullable|string|max:255',
            'description'         => 'nullable|string|max:500',
            'canonical'           => 'nullable|string|max:1024|url',
            'keywords'            => 'nullable|string|max:255',
            'robots_index'        => 'nullable|boolean',
            'robots_follow'       => 'nullable|boolean',
            'locked'              => 'nullable|boolean',
            'og_title'            => 'nullable|string|max:255',
            'og_description'      => 'nullable|string|max:512',
            'og_image'            => 'nullable|string|max:1024|url',
            'og_type'             => 'nullable|string|max:50',
            'og_url'              => 'nullable|string|max:1024|url',
            'twitter_card'        => 'nullable|string|max:50|in:summary,summary_large_image,app,player',
            'twitter_title'       => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:512',
            'twitter_image'      => 'nullable|string|max:1024|url',
            'jsonld_raw'          => 'nullable|string|json',
            'source_type'         => 'nullable|string|max:50',
            'source_id'            => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'URL обязателен для заполнения',
            'slug.unique' => 'Такой URL уже существует',
            'slug.max' => 'URL не должен превышать 1024 символов',
            'title.max' => 'Заголовок не должен превышать 255 символов (рекомендуется до 60)',
            'description.max' => 'Описание не должно превышать 500 символов (рекомендуется до 160)',
            'canonical.url' => 'Canonical должен быть валидным URL',
            'og_image.url' => 'OG изображение должно быть валидным URL',
            'og_url.url' => 'OG URL должен быть валидным URL',
            'twitter_image.url' => 'Twitter изображение должно быть валидным URL',
            'jsonld_raw.json' => 'JSON-LD должен быть валидным JSON',
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'URL',
            'title' => 'заголовок',
            'h1' => 'H1',
            'description' => 'описание',
            'canonical' => 'canonical URL',
            'keywords' => 'ключевые слова',
        ];
    }

    /**
     * Подготовка данных для валидации
     */
    protected function prepareForValidation(): void
    {
        // Нормализация canonical - если относительный путь, преобразуем в абсолютный
        if ($this->has('canonical') && !empty($this->canonical)) {
            $canonical = trim($this->canonical);
            if (!filter_var($canonical, FILTER_VALIDATE_URL)) {
                // Относительный путь - добавим домен
                $canonical = rtrim(config('app.url'), '/') . '/' . ltrim($canonical, '/');
                $this->merge(['canonical' => $canonical]);
            }
        }
    }
}




