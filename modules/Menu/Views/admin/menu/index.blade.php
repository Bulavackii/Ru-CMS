@extends('layouts.admin')

@section('title', 'Меню')

@section('content')
    {{-- 🔹 Заголовок и кнопка --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📋 Меню</h1>

        <a href="{{ route('admin.menus.create') }}"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm shadow transition">
            <i class="fas fa-plus"></i> Создать меню
        </a>
    </div>

    {{-- 🧱 Сетка адаптивных карточек меню --}}
    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($menus as $menu)
            <div class="relative group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">

                {{-- ✅ Индикатор статуса в углу --}}
                <span class="absolute top-3 right-3 text-[11px] px-2 py-1 rounded-full font-semibold z-10
                    {{ $menu->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                    {{ $menu->active ? 'Включено' : 'Отключено' }}
                </span>

                {{-- 🧾 Название меню --}}
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-bars text-blue-500"></i>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white break-words">
                        {{ $menu->title }}
                    </h2>
                </div>

                {{-- 📍 Позиция --}}
                <div class="mb-5 text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <i class="fas fa-thumbtack text-gray-400"></i>
                    <span class="font-medium">Позиция:</span> {{ ucfirst($menu->position) }}
                </div>

                {{-- ⚙️ Действия --}}
                <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 text-xs">

                    {{-- ✏️ Редактирование --}}
                    <a href="{{ route('admin.menus.edit', $menu) }}"
                        class="inline-flex items-center gap-1 bg-gray-800 hover:bg-gray-900 text-white px-3 py-1.5 rounded-md shadow transition focus:outline-none">
                        <i class="fas fa-edit"></i> Редактировать
                    </a>

                    {{-- 🔄 Включить/выключить --}}
                    <form method="POST" action="{{ route('admin.menus.toggle', $menu) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md font-medium shadow transition focus:outline-none
                            {{ $menu->active ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-gray-200 hover:bg-green-600 text-gray-800 hover:text-white' }}">
                            <i class="fas fa-power-off"></i>
                            {{ $menu->active ? 'Отключить' : 'Включить' }}
                        </button>
                    </form>

                    {{-- 🗑️ Удалить --}}
                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}"
                        onsubmit="return confirm('Удалить это меню?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md shadow transition focus:outline-none">
                            <i class="fas fa-trash-alt"></i> Удалить
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-gray-600 dark:text-gray-400 text-sm">❗ Пока нет ни одного меню. Нажмите "Создать меню" выше.</p>
        @endforelse
    </div>
@endsection
