@extends('layouts.frontend')

@section('title', 'Главная')

@section('content')
    <h1 class="text-3xl font-bold mb-6 text-center">Новости</h1>

    {{-- Фильтр по категориям --}}
    <form method="GET" class="mb-6 flex flex-wrap gap-4 justify-center items-center">
        <select name="category" class="border px-3 py-2 rounded">
            <option value="">Все категории</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->title }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Фильтровать
        </button>
    </form>

    {{-- Переводы заголовков блоков --}}
    @php
        $titles = [
            'default' => 'Новости',
            'products' => 'Товары',
            'contacts' => 'Контакты',
            'gallery' => 'Галерея',
        ];
    @endphp

    {{-- Автоматический вывод блоков по шаблонам --}}
    @foreach ($templates as $key => $newsList)
        <x-frontend.news-grid
            :newsList="$newsList"
            :title="$titles[$key] ?? ucfirst($key)"
        />
    @endforeach
@endsection
