<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Пользователь через updateOrCreate — не создаёт дубликат
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => bcrypt('123456'),
            ]
        );

        // Создание администратора, если он ещё не существует
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true, // Если поле для админа есть в модели
            ]
        );

        // Ручные пользователи (админ и обычный) из сидера
        $this->call([
            UsersTableSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
