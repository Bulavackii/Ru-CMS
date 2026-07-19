<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Создание базовых прав доступа
        $permissions = [
            // Новости
            ['name' => 'Просмотр новостей', 'slug' => 'news.view', 'module' => 'News'],
            ['name' => 'Создание новостей', 'slug' => 'news.create', 'module' => 'News'],
            ['name' => 'Редактирование новостей', 'slug' => 'news.update', 'module' => 'News'],
            ['name' => 'Удаление новостей', 'slug' => 'news.delete', 'module' => 'News'],
            ['name' => 'Публикация новостей', 'slug' => 'news.publish', 'module' => 'News'],

            // Пользователи
            ['name' => 'Просмотр пользователей', 'slug' => 'users.view', 'module' => 'Users'],
            ['name' => 'Создание пользователей', 'slug' => 'users.create', 'module' => 'Users'],
            ['name' => 'Редактирование пользователей', 'slug' => 'users.update', 'module' => 'Users'],
            ['name' => 'Удаление пользователей', 'slug' => 'users.delete', 'module' => 'Users'],

            // Роли и права
            ['name' => 'Управление ролями', 'slug' => 'roles.manage', 'module' => 'System'],
            ['name' => 'Управление правами', 'slug' => 'permissions.manage', 'module' => 'System'],

            // Модули
            ['name' => 'Управление модулями', 'slug' => 'modules.manage', 'module' => 'System'],
            ['name' => 'Установка модулей', 'slug' => 'modules.install', 'module' => 'System'],

            // Настройки
            ['name' => 'Управление настройками', 'slug' => 'settings.manage', 'module' => 'System'],

            // Файлы
            ['name' => 'Просмотр файлов', 'slug' => 'files.view', 'module' => 'Files'],
            ['name' => 'Загрузка файлов', 'slug' => 'files.upload', 'module' => 'Files'],
            ['name' => 'Удаление файлов', 'slug' => 'files.delete', 'module' => 'Files'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Создание базовых ролей
        $roles = [
            [
                'name' => 'Администратор',
                'slug' => 'admin',
                'description' => 'Полный доступ ко всем функциям системы',
                'is_system' => true,
                'priority' => 100,
                'permissions' => Permission::pluck('id')->toArray(),
            ],
            [
                'name' => 'Редактор',
                'slug' => 'editor',
                'description' => 'Может создавать и редактировать контент',
                'is_system' => true,
                'priority' => 50,
                'permissions' => Permission::whereIn('slug', [
                    'news.view', 'news.create', 'news.update', 'news.publish',
                    'files.view', 'files.upload',
                ])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Автор',
                'slug' => 'author',
                'description' => 'Может создавать контент, но не публиковать',
                'is_system' => true,
                'priority' => 30,
                'permissions' => Permission::whereIn('slug', [
                    'news.view', 'news.create', 'news.update',
                    'files.view', 'files.upload',
                ])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Просмотр',
                'slug' => 'viewer',
                'description' => 'Только просмотр контента',
                'is_system' => true,
                'priority' => 10,
                'permissions' => Permission::whereIn('slug', [
                    'news.view',
                    'files.view',
                ])->pluck('id')->toArray(),
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            $role->permissions()->sync($permissions);
        }
    }
}

