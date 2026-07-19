<?php

namespace Modules\Messages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use SoftDeletes;

    // 🗃️ Название таблицы
    protected $table = 'messages';

    // 📝 Поля, разрешённые для массового заполнения
    protected $fillable = [
        'user_id',      // ID отправителя
        'to_user_id',   // ID получателя
        'parent_id',    // ID родительского сообщения (для цепочки)
        'subject',      // Тема сообщения
        'body',         // Текст сообщения
        'is_read',      // Прочитано или нет (boolean)
        'is_important', // Важное сообщение (boolean)
        'archived_at',  // Дата архивирования
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_important' => 'boolean',
        'archived_at' => 'datetime',
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
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * 📎 Родительское сообщение (для цепочки переписки)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * 📎 Ответы на это сообщение
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * 📎 Вложения сообщения
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Scope: входящие сообщения для пользователя
     */
    public function scopeInbox($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    /**
     * Scope: исходящие сообщения от пользователя
     */
    public function scopeSent($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: непрочитанные сообщения
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: важные сообщения
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Scope: неархивированные сообщения
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope: архивированные сообщения
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
