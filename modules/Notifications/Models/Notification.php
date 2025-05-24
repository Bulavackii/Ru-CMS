<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * 💾 Массово заполняемые поля (для методов create/update)
     */
    protected $fillable = [
        'title',        // 📌 Заголовок уведомления
        'message',      // 💬 Основной текст (HTML/TinyMCE)
        'type',         // 📋 Тип (text | cookie)
        'target',       // 🎯 Целевая аудитория (all | admin | user)
        'position',     // 📍 Расположение (top | bottom | fullscreen)
        'duration',     // ⏱️ Время показа (в секундах, 0 = бесконечно)
        'icon',         // 🖼️ Иконка (emoji или FontAwesome)
        'route_filter', // 🗺️ URL или имя маршрута (для фильтрации)
        'cookie_key',   // 🍪 Ключ cookie (если type = cookie)
        'enabled',      // ✅ Включено или нет
        'bg_color',     // 🎨 Цвет фона (HEX)
        'text_color',   // 🖋️ Цвет текста (HEX)
    ];

    /* 🔧 Здесь можно добавить касты, если нужно:*/
    protected $casts = [
        'enabled' => 'boolean',
        'duration' => 'integer',
        'is_admin' => 'boolean',
    ];
}
