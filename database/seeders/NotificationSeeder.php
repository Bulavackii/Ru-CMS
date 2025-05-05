<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\Notification;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        Notification::create([
            'title'        => '🎉 Добро пожаловать!',
            'message'      => '<p>Это тестовое уведомление на весь экран. Оно исчезнет через 10 секунд.</p>',
            'type'         => 'html',
            'target'       => 'all',
            'position'     => 'fullscreen',
            'duration'     => 10,
            'icon'         => '🎈',
            'color'        => '#38bdf8', // синий
            'route_filter' => '/',
            'cookie_key'   => null,
            'enabled'      => true,
        ]);
    }
}
