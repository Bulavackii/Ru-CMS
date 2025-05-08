@extends('layouts.admin')

@section('title', 'Новости')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Список новостей</h1>
        <a href="{{ route('admin.news.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            + Добавить новость
        </a>
    </div>

    {{-- 🧭 Фильтр по шаблонам в виде табов --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        @php
            $icons = [
                'default' => '📰',
                'products' => '🛍️',
                'contacts' => '📇',
                'gallery' => '🖼️',
                'slideshow' => '🎞️',
                'faq' => '❓',
                'reviews' => '⭐',
                'test' => '🧪',
                'test2' => '⚙️',
            ];

            $current = request('template');
        @endphp

        {{-- Кнопка "Все" --}}
        <a href="{{ route('admin.news.index') }}"
            class="px-3 py-1.5 rounded-full text-sm font-medium border transition
              {{ !$current ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            📂 Все
        </a>

        {{-- Кнопки по шаблонам --}}
        @foreach ($templates as $key => $label)
            <a href="{{ route('admin.news.index', ['template' => $key]) }}"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition
                  {{ $current === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                {{ $icons[$key] ?? '🔖' }} {{ $label }}
            </a>
        @endforeach
    </div>


    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full bg-white shadow rounded overflow-hidden">
        <thead>
            <tr class="bg-gray-100 border-b">
                <th class="text-left px-4 py-2">Заголовок</th>
                <th class="text-left px-4 py-2">Категории</th>
                <th class="text-left px-4 py-2">Статус</th>
                <th class="text-left px-4 py-2">Шаблон</th>
                <th class="text-left px-4 py-2">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($newsList as $news)
                <tr class="border-b">
                    <td class="px-4 py-2 max-w-xs truncate" title="{{ $news->title }}">
                        {{ $news->title }}
                    </td>
                    <td class="px-4 py-2">
                        @foreach ($news->categories as $category)
                            <span class="inline-block bg-gray-200 text-sm rounded px-2 py-1 mr-1">
                                {{ $category->title }}
                            </span>
                        @endforeach
                    </td>
                    <td class="px-4 py-2">
                        @if ($news->published)
                            <span class="text-green-600 font-semibold">Опубликовано</span>
                        @else
                            <span class="text-gray-500">Черновик</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @include('News::admin.template-badge', ['template' => $news->template])
                    </td>
                    <td class="px-4 py-2 space-x-2">
                        <a href="{{ route('admin.news.edit', $news->id) }}"
                            class="text-blue-600 hover:underline">Редактировать</a>

                        <form action="{{ route('admin.news.destroy', $news->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('Удалить эту новость?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Удалить</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-6">Новостей пока нет.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
