@extends('layouts.admin')

@section('title', 'Новости')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Список новостей</h1>
        <a href="{{ route('admin.news.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
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

        <a href="{{ route('admin.news.index') }}"
            class="px-3 py-1.5 rounded-full text-sm font-medium border transition
              {{ !$current ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            📂 Все
        </a>

        @foreach ($templates as $key => $label)
            <a href="{{ route('admin.news.index', ['template' => $key]) }}"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition
                  {{ $current === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                {{ $icons[$key] ?? '🔖' }} {{ $label }}
            </a>
        @endforeach
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
            <thead class="bg-gray-100 border-b text-sm text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">📝 Заголовок</th>
                    <th class="text-left px-4 py-3">📂 Категории</th>
                    <th class="text-center px-4 py-3">📢 Статус</th>
                    <th class="text-center px-4 py-3">📦 Шаблон</th>
                    <th class="text-center px-4 py-3">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($newsList as $index => $news)
                    <tr class="transition duration-150 ease-in-out {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-blue-50">
                        <td class="px-4 py-3 max-w-xs truncate" title="{{ $news->title }}">
                            {{ $news->title }}
                        </td>
                        <td class="px-4 py-3">
                            @foreach ($news->categories as $category)
                                <span class="inline-block bg-gray-200 text-gray-700 text-xs rounded-full px-2 py-1 mr-1 mb-1">
                                    {{ $category->title }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($news->published)
                                <span class="text-green-600 animate-pulse" title="Опубликовано">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            @else
                                <span class="text-gray-400" title="Черновик">
                                    <i class="fas fa-clock"></i>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @include('News::admin.template-badge', ['template' => $news->template])
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <a href="{{ route('admin.news.edit', $news->id) }}"
                               class="text-blue-600 hover:text-blue-800 mr-2 transition-transform transform hover:scale-110" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>

                            <form action="{{ route('admin.news.destroy', $news->id) }}" method="POST" class="inline-block"
                                  onsubmit="return confirm('Удалить эту новость?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 transition-transform transform hover:scale-110" title="Удалить">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
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

        <div class="mt-4">
            {{ $newsList->withQueryString()->links() }}
        </div>
    </div>
@endsection
