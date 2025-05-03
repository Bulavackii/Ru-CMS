@extends('layouts.frontend')

@section('title', 'Главная')

@section('content')
    @php
        $titles = [
            'default' => 'Новости',
            'products' => 'Товары',
            'contacts' => 'Контакты',
            'gallery' => 'Галерея',
            'test' => 'Тест',
        ];
    @endphp

    @foreach ($templates as $key => $newsList)
        @if ($newsList->isNotEmpty())
            @php
                $templateView = 'frontend.templates.' . $key;
            @endphp

            @if (View::exists($templateView))
                {{-- ✅ Кастомный шаблон из resources/views/frontend/templates --}}
                @include($templateView, ['newsList' => $newsList, 'title' => $titles[$key] ?? ucfirst($key)])
            @else
                {{-- 🔁 Общий шаблон карточек --}}
                <div class="mb-10">
                    <h2 class="text-2xl font-bold mb-4 text-center">{{ $titles[$key] ?? ucfirst($key) }}</h2>

                    {{-- Фильтр --}}
                    <form method="GET" class="mb-6 flex flex-wrap gap-4 justify-center items-center">
                        <select name="category_{{ $key }}" class="border px-3 py-2 rounded">
                            <option value="">Все категории</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ request("category_$key") == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->title }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Фильтровать
                        </button>
                    </form>

                    {{-- Компонент карточек --}}
                    <x-frontend.news-grid :newsList="$newsList" :title="null" />
                </div>
            @endif
        @endif
    @endforeach
@endsection
