<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\Notification;

/**
 * 🔔 Сидер для создания тестового уведомления
 *
 * Показывает приветственное сообщение пользователю при заходе на главную страницу.
 */
class NotificationSeeder extends Seeder
{
    /**
     * 🚀 Выполнение сидера
     */
    public function run(): void
    {
        Notification::create([
            // 📝 Заголовок уведомления
            'title' => '🎉 Добро пожаловать!',

            // 📩 Содержимое сообщения (HTML)
            'message' => '<p>Это тестовое уведомление на весь экран. Оно исчезнет через 10 секунд.</p>',

            // 📦 Тип уведомления: может быть "text", "html", "cookie"
            'type' => 'html',

            // 🎯 Аудитория — "all" = все пользователи (в том числе неавторизованные)
            // Другие значения: "admin", "user"
            'target' => 'all',

            // 📍 Позиция на экране: может быть "top", "bottom", "fullscreen"
            'position' => 'fullscreen',

            // ⏱️ Продолжительность отображения (в секундах)
            'duration' => 10,

            // 🖼️ Иконка рядом с сообщением (можно использовать emoji или FontAwesome)
            'icon' => '🎈',

            // 🎨 Цвет фона или текста уведомления
            'color' => '#38bdf8', // синий (Tailwind sky-400)

            // 🛣️ Показывать только на определённом маршруте
            // Например: '/', '/shop', '/dashboard' — или null для всех страниц
            'route_filter' => '/',

            // 🍪 Ключ cookie — если указан, уведомление будет показываться один раз до очистки куки
            'cookie_key' => null,

            // ✅ Включено ли уведомление
            'enabled' => true,
        ]);
    }
}
