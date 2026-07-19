<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 🧾 ProfileUpdateRequest
 *
 * FormRequest для обновления профиля пользователя.
 *
 * Содержит:
 * 🔹 Валидацию имени
 * 🔹 Валидацию email с учётом текущего пользователя
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * 📋 rules()
     *
     * Правила валидации при обновлении профиля:
     * - name: обязательное текстовое поле, не длиннее 255 символов
     * - email: обязателен, email-формат, не более 255 символов, уникален среди пользователей
     *   ⚠️ Исключает текущего пользователя из проверки уникальности
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase', // приведение email к нижнему регистру
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id), // 👤 Игнорируем текущего пользователя
            ],
        ];
    }
}
