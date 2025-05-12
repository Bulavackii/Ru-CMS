<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Администратор
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('123456'), // автоматическое шифрование
                'is_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Администратор2
        User::updateOrCreate(
            ['email' => 'admin2@example.com'],
            [
                'name' => 'Admin2',
                'email_verified_at' => now(),
                'password' => Hash::make('123456'), // автоматическое шифрование
                'is_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Обычный пользователь
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'email_verified_at' => now(),
                'password' => Hash::make('123456'),
                'is_admin' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
