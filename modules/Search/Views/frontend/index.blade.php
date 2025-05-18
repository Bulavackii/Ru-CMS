@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8">

        {{-- 🔎 Форма поиска --}}
        <form method="GET" action="{{ route('search.index') }}" class="mb-6">
            <div class="relative">
                <input type="text" name="q" value="{{ request('q') }}"
                    class="w-full border border-gray-300 rounded-full py-2 pl-4 pr-10 shadow-sm focus:outline-none focus:ring focus:ring-indigo-200 text-sm"
                    placeholder="🔍 Поиск по сайту...">
                <button type="submit" class="absolute right-2 top-1.5 text-gray-500 hover:text-indigo-600">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        {{-- 📋 Результаты поиска --}}
        @if ($query)
            <h2 class="text-lg font-semibold mb-4 text-gray-800">
                Результаты для запроса: <span class="text-indigo-600">"{{ $query }}"</span>
            </h2>

            {{-- 📰 Статьи --}}
            @if ($posts->count())
                <h3 class="text-md font-semibold text-gray-700 mb-2">📰 Статьи</h3>
                <div class="flex flex-wrap justify-center gap-6">
                    @foreach ($posts as $post)
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($posts as $post)
                                <div
                                    class="w-full sm:w-[90%] md:w-[70%] lg:w-[60%] max-w-xl mx-auto
                    border border-gray-200 rounded-xl p-4 shadow-sm bg-white hover:shadow-md transition">
                                    <h4 class="text-base font-bold text-indigo-700 mb-1">{{ $post->title }}</h4>
                                    <p class="text-sm text-gray-600">{{ Str::limit(strip_tags($post->content), 150) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- 🛍️ Товары --}}
            @if ($products->count())
                <h3 class="text-md font-semibold text-gray-700 mt-6 mb-2">🛒 Товары</h3>
                <div class="flex flex-wrap justify-center gap-6">
                    @foreach ($products as $product)
                        <div
                            class="w-full sm:w-[90%] md:w-[70%] lg:w-[60%] max-w-xl mx-auto
                    border border-gray-200 rounded-xl p-4 shadow-sm bg-white hover:shadow-md transition">
                            <h4 class="text-base font-bold text-green-700 mb-1">{{ $product->name }}</h4>
                            <p class="text-sm text-gray-600">{{ Str::limit(strip_tags($product->description), 150) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- 🚫 Пусто --}}
            @if (!$posts->count() && !$products->count())
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
                    Ничего не найдено. Попробуйте изменить запрос.
                </div>
            @endif
        @endif
    </div>
@endsection
