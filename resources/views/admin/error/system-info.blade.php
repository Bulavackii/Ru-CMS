@extends('layouts.admin')

@section('title', 'Информация о системе')

@section('content')
    <h1 class="text-3xl font-extrabold mb-6 text-gray-800 flex items-center gap-2">
        💻 Информация о системе
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-6xl">

        {{-- Laravel --}}
        <x-admin-info-card icon="fab fa-laravel text-red-500" title="Laravel">
            {{ App::version() }}
        </x-admin-info-card>

        {{-- PHP --}}
        <x-admin-info-card icon="fab fa-php text-indigo-600" title="PHP">
            {{ phpversion() }}
        </x-admin-info-card>

        {{-- Окружение --}}
        <x-admin-info-card icon="fas fa-cogs text-gray-600" title="Окружение">
            {{ app()->environment() }}
        </x-admin-info-card>

        {{-- Драйвер БД --}}
        <x-admin-info-card icon="fas fa-database text-blue-500" title="Драйвер базы данных">
            {{ config('database.default') }}
        </x-admin-info-card>

        {{-- Версия БД --}}
        <x-admin-info-card icon="fas fa-server text-blue-400" title="Версия базы данных">
            {{ \DB::selectOne('select version() as version')->version ?? 'N/A' }}
        </x-admin-info-card>

        {{-- ОС --}}
        <x-admin-info-card icon="fas fa-desktop text-green-600" title="ОС сервера">
            {{ PHP_OS }} {{ php_uname('r') }}
        </x-admin-info-card>

        {{-- Memory --}}
        <x-admin-info-card icon="fas fa-memory text-purple-600" title="Memory Limit">
            {{ ini_get('memory_limit') }}
        </x-admin-info-card>

        {{-- Upload --}}
        <x-admin-info-card icon="fas fa-upload text-yellow-500" title="Макс. загрузка">
            {{ ini_get('upload_max_filesize') }}
        </x-admin-info-card>

        {{-- Время --}}
        <x-admin-info-card icon="fas fa-clock text-orange-500" title="Время на сервере">
            {{ now()->format('d.m.Y H:i:s') }}
        </x-admin-info-card>

        {{-- Путь к проекту --}}
        <x-admin-info-card icon="fas fa-code-branch text-pink-500" title="Путь к проекту">
            <span class="text-xs break-all">{{ base_path() }}</span>
        </x-admin-info-card>

        {{-- PHP Extensions (сворачиваемый блок) --}}
        <div class="bg-white rounded-xl shadow p-5 border border-gray-200 col-span-full"
             x-data="{ open: false }">

            <div class="flex items-center justify-between cursor-pointer mb-3"
                 @click="open = !open">
                <h2 class="text-sm font-semibold text-gray-600 flex items-center gap-2">
                    <i class="fas fa-puzzle-piece text-cyan-500"></i> Активные расширения PHP
                </h2>
                <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"
                   class="text-gray-400 transition duration-300"></i>
            </div>

            <div x-show="open"
                 x-transition
                 class="text-xs text-gray-700 font-mono grid grid-cols-2 md:grid-cols-4 gap-2">
                @foreach(get_loaded_extensions() as $ext)
                    <span class="bg-gray-100 px-2 py-1 rounded">{{ $ext }}</span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
