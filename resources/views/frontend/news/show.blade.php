@extends('layouts.frontend')

@section('title', $news->title)

@section('content')
    <article class="max-w-3xl mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-lg px-6 py-8 transition-all duration-300 overflow-hidden">
        {{-- Заголовок --}}
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white leading-tight break-words mb-6 text-center">
            {{ $news->title }}
        </h1>

        {{-- Категории --}}
        @if ($news->categories->isNotEmpty())
            <div class="mb-4 text-sm flex flex-wrap gap-2 justify-center">
                @foreach ($news->categories as $category)
                    <a href="{{ url('/?category=' . $category->id) }}"
                       class="inline-block bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-medium hover:underline transition">
                        {{ $category->title }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Контент --}}
        <div class="prose prose-sm sm:prose lg:prose-lg max-w-none news-content text-gray-800 dark:text-gray-100 mb-8">
            {!! $news->content !!}
        </div>

        {{-- Блок с ценой, остатком, количеством и кнопкой --}}
        @if($news->price)
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-xl mb-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                    <div class="space-y-3">
                        <div class="bg-green-100 text-green-900 px-4 py-2 rounded-full font-semibold shadow-sm text-sm inline-block">
                            💰 {{ number_format($news->price, 2, ',', ' ') }} ₽
                        </div>
                        @if (!is_null($news->stock))
                            <div class="bg-yellow-100 text-yellow-900 px-4 py-2 rounded-full font-semibold shadow-sm text-sm inline-block">
                                📦 Осталось: {{ $news->stock }}
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3 flex flex-col items-end">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Кол-во:</span>
                            <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded overflow-hidden">
                                <button type="button"
                                        class="px-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 font-bold text-lg decrement"
                                        data-id="{{ $news->id }}">−</button>
                                <input type="text"
                                       id="qty-{{ $news->id }}"
                                       value="1"
                                       readonly
                                       class="w-12 text-center border-x border-gray-200 dark:border-gray-600 text-sm qty-input"
                                       data-id="{{ $news->id }}">
                                <button type="button"
                                        class="px-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 font-bold text-lg increment"
                                        data-id="{{ $news->id }}"
                                        data-stock="{{ $news->stock }}">+</button>
                            </div>
                        </div>

                        <button type="button"
                                class="add-to-cart bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-2 px-4 rounded-lg text-sm shadow transition"
                                data-id="{{ $news->id }}"
                                data-title="{{ $news->title }}"
                                data-price="{{ $news->price }}"
                                data-stock="{{ $news->stock }}">
                            🛒 В корзину
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Назад --}}
        <div class="text-center">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-2.5 rounded-lg font-medium text-sm shadow-md transition">
                ⬅️ На главную
            </a>
        </div>
    </article>
@endsection

@push('styles')
    <style>
        .news-content {
            word-break: break-word;
        }

        .news-content * {
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .news-content img,
        .news-content video,
        .news-content iframe {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1.5rem auto;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .news-content table {
            display: block;
            width: 100%;
            overflow-x: auto;
            border-collapse: collapse;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .news-content table th,
        .news-content table td {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
        }

        .news-content h1, .news-content h2, .news-content h3 {
            font-weight: 700;
            margin-top: 1.5rem;
        }

        @media (max-width: 640px) {
            .news-content img,
            .news-content video,
            .news-content iframe {
                max-width: 100%;
                height: auto;
            }
        }
    </style>
@endpush
