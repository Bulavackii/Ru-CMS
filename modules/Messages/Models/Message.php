<?php

namespace Modules\Messages\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * 💬 Модель "Message" — внутренние сообщения между администраторами
 *
 * Связи:
 * 🔸 sender() — отправитель сообщения
 * 🔸 receiver() — получатель сообщения
 */
class Message extends Model
{
    // 🗃️ Название таблицы
    protected $table = 'messages';

    // 📝 Поля, разрешённые для массового заполнения
    protected $fillable = [
        'user_id',      // ID отправителя
        'to_user_id',   // ID получателя
        'subject',      // Тема сообщения
        'body',         // Текст сообщения
        'is_read',      // Прочитано или нет (boolean)
    ];

    /**
     * 📤 Связь с моделью User (отправитель)
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 📥 Связь с моделью User (получатель)
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
