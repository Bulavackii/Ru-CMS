@extends('layouts.admin')

@section('title', 'Страницы')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📄 Страницы</h1>
        <a href="{{ route('admin.pages.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Новая страница
        </a>
    </div>

    {{-- @if (session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded shadow mb-6">
            ✅ {{ session('success') }}
        </div>
    @endif --}}

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden shadow-md bg-white dark:bg-gray-900">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Заголовок</th>
                    <th class="px-4 py-3 text-left">Slug</th>
                    <th class="px-4 py-3 text-left">Категории</th>
                    <th class="px-4 py-3 text-left">Публикация</th>
                    <th class="px-4 py-3 text-left">Главная</th>
                    <th class="px-4 py-3 text-left">Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($pages as $page)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $page->title }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $page->slug }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            @foreach ($page->categories as $cat)
                                <span class="inline-block bg-gray-200 text-gray-800 text-xs rounded-full px-2 py-0.5 mr-1 mb-1">
                                    🏷️ {{ $cat->title }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-center">{{ $page->published ? '✅' : '❌' }}</td>
                        <td class="px-4 py-3 text-center">{{ $page->show_on_homepage ? '🏠' : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.pages.edit', $page) }}"
                               class="text-blue-600 hover:text-blue-800 text-lg transition">✏️</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
