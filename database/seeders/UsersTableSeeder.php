<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Администратор
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123456'), // автоматическое шифрование
            'is_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Обычный пользователь
        DB::table('users')->insert([
            'name' => 'User',
            'email' => 'user@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123456'),
            'is_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
