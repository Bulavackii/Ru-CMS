<?php

// ✅ 1. Список меню — admin.menu.index
// 📁 modules/Menu/Views/admin/menu/index.blade.php

@extends('layouts.admin')

@section('title', 'Меню')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📋 Меню</h1>
    </div>

    // @if (session('success'))
    //     <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded shadow mb-6">
    //         ✅ {{ session('success') }}
    //     </div>
    // @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($menus as $menu)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 rounded-lg shadow">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ $menu->title }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Позиция: <span class="font-medium">{{ ucfirst($menu->position) }}</span><br>
                    Статус: <span class="font-medium">{{ $menu->active ? '✅ Включено' : '❌ Выключено' }}</span>
                </p>
                <a href="{{ route('admin.menus.edit', $menu) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white hover:bg-gray-800 text-sm rounded shadow">
                    ✏️ Редактировать
                </a>
            </div>
        @endforeach
    </div>
@endsection
