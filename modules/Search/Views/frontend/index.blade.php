@extends('layouts.app')

@section('title', $query ? "Поиск: {$query}" : 'Поиск по сайту')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        {{-- 🔎 Форма поиска с автодополнением --}}
        <form method="GET" action="{{ route('search.index') }}" class="mb-8" x-data="searchForm()">
            <div class="relative">
                <input type="text" 
                       name="q" 
                       value="{{ $query }}"
                       x-model="query"
                       @input.debounce.300ms="autocomplete()"
                       @focus="showSuggestions = true"
                       @blur="setTimeout(() => showSuggestions = false, 200)"
                       class="w-full border border-gray-300 rounded-full py-3 pl-6 pr-12 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base"
                       placeholder="🔍 Поиск по сайту...">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600 transition">
                    <i class="fas fa-search text-lg"></i>
                </button>
                
                {{-- Автодополнение --}}
                <div x-show="showSuggestions && suggestions.length > 0" 
                     x-transition
                     class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto">
                    <template x-for="suggestion in suggestions" :key="suggestion.text">
                        <button type="button"
                                @click="selectSuggestion(suggestion.text)"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center justify-between">
                            <span x-text="suggestion.text"></span>
                            <span class="text-xs text-gray-500" x-text="suggestion.type === 'news' ? '📰' : '🛒'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </form>

        @php
            function highlight($text, $query) {
                if (empty($query) || empty($text)) return e($text);
                $words = explode(' ', $query);
                $highlighted = e($text);
                foreach ($words as $word) {
                    if (strlen($word) > 2) {
                        $highlighted = preg_replace(
                            '/' . preg_quote($word, '/') . '/iu',
                            '<mark class="bg-yellow-200 px-1 rounded">$0</mark>',
                            $highlighted
                        );
                    }
                }
                return $highlighted;
            }
        @endphp

        {{-- 📋 Результаты поиска --}}
        @if ($query && strlen($query) >= 2)
            <div class="mb-4 text-sm text-gray-600">
                Найдено результатов: <strong>{{ $totalCount ?? 0 }}</strong>
            </div>

            {{-- 📰 Новости --}}
            @if ($news->count() > 0)
                <section class="mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center gap-2">
                        <i class="fas fa-newspaper text-indigo-600"></i>
                        Новости <span class="text-sm font-normal text-gray-500">({{ $news->total() }})</span>
                    </h2>
                    <div class="space-y-4">
                        @foreach ($news as $item)
                            <article class="border border-gray-200 rounded-xl p-5 shadow-sm bg-white hover:shadow-md transition">
                                <h3 class="text-lg font-bold text-indigo-700 mb-2">
                                    <a href="{{ route('news.show', $item->slug) }}" class="hover:underline">
                                        {!! highlight($item->title, $query) !!}
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    {!! highlight(\Illuminate\Support\Str::limit(strip_tags($item->content), 200), $query) !!}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <span><i class="far fa-calendar"></i> {{ $item->created_at->format('d.m.Y') }}</span>
                                    @if($item->categories->count() > 0)
                                        <span>
                                            <i class="fas fa-tag"></i> 
                                            {{ $item->categories->pluck('title')->implode(', ') }}
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    {{ $news->links('vendor.pagination.tailwind') }}
                </section>
            @endif

            {{-- 🛒 Товары --}}
            @if ($products->count() > 0)
                <section class="mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center gap-2">
                        <i class="fas fa-shopping-cart text-green-600"></i>
                        Товары <span class="text-sm font-normal text-gray-500">({{ $products->total() }})</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($products as $product)
                            <article class="border border-gray-200 rounded-xl p-5 shadow-sm bg-white hover:shadow-md transition">
                                <h3 class="text-lg font-bold text-green-700 mb-2">
                                    <a href="{{ route('news.show', $product->slug) }}" class="hover:underline">
                                        {!! highlight($product->title, $query) !!}
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-600 mb-3">
                                    {!! highlight(\Illuminate\Support\Str::limit(strip_tags($product->content), 150), $query) !!}
                                </p>
                                @if($product->price)
                                    <div class="text-lg font-bold text-green-600">
                                        {{ number_format($product->price, 0, ',', ' ') }} ₽
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    {{ $products->links('vendor.pagination.tailwind') }}
                </section>
            @endif

            {{-- 🚫 Ничего не найдено --}}
            @if ($news->count() === 0 && $products->count() === 0)
                <div class="text-center py-12">
                    <div class="mx-auto w-16 h-16 rounded-full bg-yellow-100 grid place-items-center mb-4">
                        <i class="fas fa-search text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Ничего не найдено</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        По запросу <strong>"{{ $query }}"</strong> ничего не найдено.
                    </p>
                    <div class="text-sm text-gray-500">
                        <p class="mb-2">Попробуйте:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Использовать другие ключевые слова</li>
                            <li>Проверить правильность написания</li>
                            <li>Упростить запрос</li>
                        </ul>
                    </div>
                </div>
            @endif
        @elseif($query && strlen($query) < 2)
            <div class="text-center py-8 text-gray-600">
                <p>Запрос должен содержать минимум 2 символа</p>
            </div>
        @else
            {{-- Пустое состояние --}}
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 rounded-full bg-indigo-100 grid place-items-center mb-4">
                    <i class="fas fa-search text-indigo-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Начните поиск</h3>
                <p class="text-sm text-gray-600">
                    Введите запрос в поле выше для поиска по новостям и товарам
                </p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="{{ local_js('alpine.min.js') }}" defer></script>
    <script>
        function searchForm() {
            return {
                query: '{{ $query ?? '' }}',
                suggestions: [],
                showSuggestions: false,
                autocomplete() {
                    if (this.query.length < 2) {
                        this.suggestions = [];
                        return;
                    }
                    fetch(`{{ route('search.autocomplete') }}?q=${encodeURIComponent(this.query)}`)
                        .then(res => res.json())
                        .then(data => this.suggestions = data)
                        .catch(() => this.suggestions = []);
                },
                selectSuggestion(text) {
                    this.query = text;
                    this.showSuggestions = false;
                    document.querySelector('form').submit();
                }
            }
        }
    </script>
    @endpush
@endsection
