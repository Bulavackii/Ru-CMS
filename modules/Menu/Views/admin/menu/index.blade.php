@extends('layouts.admin')

@section('title', 'Меню')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📋 Меню</h1>

        <a href="{{ route('admin.menus.create') }}"
            class="inline-flex items-center gap-2 bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md text-sm shadow transition">
            <i class="fas fa-plus"></i> Создать меню
        </a>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($menus as $menu)
            <div
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 rounded-xl shadow-sm transition-all hover:shadow-md">
                {{-- 🧾 Заголовок и статус --}}
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-list-alt text-gray-400"></i> {{ $menu->title }}
                    </h2>
                    <span
                        class="text-xs px-2 py-1 rounded-full font-semibold
                    {{ $menu->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                        {{ $menu->active ? 'Включено' : 'Отключено' }}
                    </span>
                </div>

                {{-- 📌 Позиция --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <i class="fas fa-thumbtack mr-1 text-gray-400"></i>
                    Позиция: <strong>{{ ucfirst($menu->position) }}</strong>
                </p>

                {{-- 🔘 Действия на одной строке --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.menus.edit', $menu) }}"
                        class="inline-flex items-center gap-1 bg-gray-800 text-white hover:bg-gray-900 px-3 py-1.5 rounded-md text-xs shadow transition">
                        <i class="fas fa-pencil-alt text-xs"></i> Редактировать
                    </a>

                    <form method="POST" action="{{ route('admin.menus.toggle', $menu) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium shadow transition
                            {{ $menu->active ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                            <i class="fas fa-power-off text-xs"></i>
                            {{ $menu->active ? 'Отключить' : 'Включить' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

@endsection
