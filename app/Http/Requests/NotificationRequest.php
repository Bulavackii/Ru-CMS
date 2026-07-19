<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->can('manage-notifications'));
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'type'         => 'required|in:text,html,cookie',
            'target'       => 'required|in:all,admin,user',
            'position'     => 'required|in:top,bottom,fullscreen',
            'duration'     => 'nullable|integer|min:0',
            'icon'         => 'nullable|string|max:100',
            'route_filter' => 'nullable|string|max:255',
            'cookie_key'   => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
            'bg_color'     => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color'   => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{6}$/',
            'priority'     => 'nullable|integer|min:0|max:100',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Заголовок обязателен для заполнения',
            'title.max' => 'Заголовок не должен превышать 255 символов',
            'message.required' => 'Содержимое обязательно для заполнения',
            'type.in' => 'Выбран недопустимый тип уведомления',
            'target.in' => 'Выбрана недопустимая целевая аудитория',
            'position.in' => 'Выбрана недопустимая позиция',
            'duration.integer' => 'Время показа должно быть целым числом',
            'duration.min' => 'Время показа не может быть отрицательным',
            'bg_color.regex' => 'Цвет фона должен быть в формате HEX (#RRGGBB)',
            'text_color.regex' => 'Цвет текста должен быть в формате HEX (#RRGGBB)',
            'cookie_key.regex' => 'Ключ cookie может содержать только буквы, цифры, дефисы и подчеркивания',
            'ends_at.after_or_equal' => 'Дата окончания должна быть позже или равна дате начала',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'заголовок',
            'message' => 'содержимое',
            'type' => 'тип',
            'target' => 'целевая аудитория',
            'position' => 'позиция',
            'duration' => 'время показа',
            'bg_color' => 'цвет фона',
            'text_color' => 'цвет текста',
        ];
    }
}




