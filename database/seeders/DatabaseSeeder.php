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

        // Ручные пользователи (админ и обычный) из сидера
        $this->call([
            UsersTableSeeder::class,
        ]);
    }
}
