<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RedirectRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->can('manage-seo'));
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'from'     => 'required|string|max:1024',
            'to'       => 'nullable|string|max:1024',
            'code'     => 'required|in:301,302,410',
            'is_regex' => 'sometimes|boolean',
            'priority' => 'nullable|integer|min:0|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'from.required' => 'Исходный путь обязателен для заполнения',
            'from.max' => 'Исходный путь не должен превышать 1024 символов',
            'to.max' => 'Целевой путь не должен превышать 1024 символов',
            'code.required' => 'Код редиректа обязателен',
            'code.in' => 'Код редиректа должен быть 301, 302 или 410',
            'priority.integer' => 'Приоритет должен быть целым числом',
            'priority.min' => 'Приоритет не может быть отрицательным',
            'priority.max' => 'Приоритет не может превышать 1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'исходный путь',
            'to' => 'целевой путь',
            'code' => 'код редиректа',
            'is_regex' => 'регулярное выражение',
            'priority' => 'приоритет',
        ];
    }

    /**
     * Дополнительная валидация после базовых правил
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();
            $isRegex = $this->boolean('is_regex');

            // Для 410 целевой URL не нужен
            if ($data['code'] === '410') {
                return;
            }

            // Для 301/302 — обязателен целевой адрес
            if (empty($data['to'])) {
                $validator->errors()->add('to', 'Для кода ' . $data['code'] . ' требуется целевой адрес.');
                return;
            }

            // Валидация regex
            if ($isRegex) {
                $this->assertValidRegex($data['from'], $validator);
            }

            // Запрет саморедиректа (для нерегулярных правил)
            if (!$isRegex && !empty($data['to'])) {
                if ($this->sameLocation($data['from'], $data['to'])) {
                    $validator->errors()->add('to', 'Нельзя редиректить на тот же путь.');
                }
            }

            // Защита от дублей
            $id = $this->route('id');
            $dup = \Modules\Seo\Models\RedirectRule::query()
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->where('from', $data['from'])
                ->where('is_regex', $isRegex)
                ->first();

            if ($dup) {
                $validator->errors()->add('from', 'Правило с таким "from" уже существует.');
            }
        });
    }

    /**
     * Проверка валидности регулярного выражения
     */
    protected function assertValidRegex(string $pattern, $validator): void
    {
        $delims = ['#', '~', '%', '!', '/'];
        $delim = null;
        foreach ($delims as $d) {
            if (strpos($pattern, $d) === false) {
                $delim = $d;
                break;
            }
        }
        if (!$delim) {
            $delim = '#';
            $pattern = str_replace('#', '\#', $pattern);
        }
        $wrapped = $delim . $pattern . $delim . 'i';
        
        set_error_handler(function () {}, E_WARNING);
        $ok = @preg_match($wrapped, '') !== false;
        restore_error_handler();

        if (!$ok) {
            $validator->errors()->add('from', 'Некорректное регулярное выражение.');
        }
    }

    /**
     * Проверка, что to указывает на тот же path?query, что и from
     */
    protected function sameLocation(string $from, string $to): bool
    {
        $toCmp = $to;
        if (filter_var($to, FILTER_VALIDATE_URL)) {
            $parts = parse_url($to);
            $toCmp = ($parts['path'] ?? '/') . (isset($parts['query']) ? ('?' . $parts['query']) : '');
        } else {
            $toCmp = \Modules\Seo\Models\RedirectRule::normalizePath($to);
        }
        return rtrim($from, '/') === rtrim($toCmp, '/');
    }
}




