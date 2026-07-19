@extends('layouts.admin')

@section('title', 'Настройки учётной записи')

@section('content')
    {{-- 🔰 Заголовок страницы --}}
    <h1 class="text-3xl font-extrabold mb-6 text-gray-800 dark:text-white flex items-center gap-3">
        👤 Моя учётная запись
    </h1>

    {{-- 🧩 Сетка карточек --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 max-w-6xl">

        {{-- 👤 Имя пользователя --}}
        <x-admin-info-card icon="fas fa-user text-blue-500" title="Имя">
            {{ $user->name }}
        </x-admin-info-card>

        {{-- 📧 Email --}}
        <x-admin-info-card icon="fas fa-envelope text-indigo-600" title="Email">
            {{ $user->email }}
        </x-admin-info-card>

        {{-- 🔑 Смена пароля --}}
        <x-admin-info-card icon="fas fa-key text-yellow-500" title="Безопасность">
            <a href="{{ route('password.change.form') }}"
               class="text-blue-600 hover:underline font-medium">
                Сменить пароль
            </a>
        </x-admin-info-card>

        {{-- 📨 Напоминание пароля (в разработке) --}}
        <x-admin-info-card icon="fas fa-envelope-open-text text-cyan-500" title="Восстановление">
            <span class="text-gray-400 text-xs italic">Отправка пароля на почту — скоро</span>
        </x-admin-info-card>

        {{-- 🕓 Последняя активность --}}
        <x-admin-info-card icon="fas fa-clock text-orange-500" title="Последняя активность">
            {{ $user->updated_at->format('d.m.Y H:i') }}
        </x-admin-info-card>

        {{-- 🆔 ID пользователя --}}
        <x-admin-info-card icon="fas fa-hashtag text-gray-600" title="ID пользователя">
            {{ $user->id }}
        </x-admin-info-card>

        {{-- 💾 Версия БД --}}
        <x-admin-info-card icon="fas fa-database text-blue-400" title="Версия базы данных">
            {{ $dbVersion }}
        </x-admin-info-card>
    </div>
@endsection
