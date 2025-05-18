<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * 👤 User
 *
 * Модель пользователя. Наследует функциональность авторизации (Authenticatable).
 * Используется для регистрации, входа, уведомлений, профиля и т.д.
 */
class User extends Authenticatable
{
    /** 🔧 Подключение фабрик (для сидеров/тестов) */
    use HasFactory, Notifiable;

    /**
     * 🧾 Разрешённые для массового заполнения поля (через create/update)
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * 🙈 Скрытые поля при сериализации (например, в JSON-ответе API)
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 🔁 Преобразование типов для указанных полей
     *
     * - email_verified_at → Carbon-объект (datetime)
     * - password → Laravel автоматически хеширует при установке
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
