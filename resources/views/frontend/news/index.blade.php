@extends('layouts.frontend')

@section('title', 'Новости')

@section('content')
    <div class="my-12 max-w-screen-xl mx-auto px-4">
        {{-- 📰 Заголовок --}}
        <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 dark:text-white tracking-tight flex items-center justify-center gap-3">
            🗞️ {{ $title ?? 'Последние новости' }}
        </h2>

        @if ($newsList->count())
            {{-- 📦 Сетка карточек новостей --}}
            <div class="flex flex-wrap justify-center gap-8">
                @foreach ($newsList as $news)
                    @php
                        // 🖼️ Определяем изображение (обложка, <img>, <source>, заглушка)
                        $imgSrc = $news->cover
                            ? asset('storage/' . $news->cover)
                            : (
                                preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)
                                    ? $imgMatch[1]
                                    : (
                                        preg_match('/<source[^>]+src="([^">]+)"/i', $news->content, $sourceMatch)
                                            ? $sourceMatch[1]
                                            : asset('images/no-image.png')
                                    )
                            );

                        $isVideo = \Illuminate\Support\Str::endsWith(Str::lower($imgSrc), ['.mp4', '.webm']);
                    @endphp

                    {{-- 📄 Карточка новости --}}
                    <div class="news-card flex flex-col w-full max-w-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm hover:shadow-lg transition-all duration-300">
                        {{-- 🏷️ Бейдж новость --}}
                        <div class="absolute -top-3 right-3 z-10 bg-white dark:bg-gray-800 border border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow animate-pulse">
                            📰 NEWS
                        </div>

                        {{-- 📸 Обложка/видео --}}
                        <div class="w-full h-44 overflow-hidden mb-4 rounded-xl border border-gray-200 relative">
                            @if ($isVideo)
                                <video controls muted class="w-full h-full object-cover rounded-xl">
                                    <source src="{{ $imgSrc }}" type="video/mp4">
                                    Ваш браузер не поддерживает видео.
                                </video>
                            @else
                                <img src="{{ $imgSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl">
                            @endif
                        </div>

                        {{-- 📝 Заголовок --}}
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 leading-snug line-clamp-2 break-words">
                            <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                                {{ $news->title }}
                            </a>
                        </h3>

                        {{-- 📅 Дата публикации --}}
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                            📅 {{ $news->created_at->format('d.m.Y') }}
                        </div>

                        {{-- 🗂️ Категории --}}
                        <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                            🏷️
                            @forelse ($news->categories as $category)
                                <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                                    {{ $category->title }}
                                </a>{{ !$loop->last ? ',' : '' }}
                            @empty
                                <span class="text-gray-400">Без категории</span>
                            @endforelse
                        </div>

                        {{-- 💬 Краткое описание --}}
                        <div class="text-sm text-gray-700 dark:text-gray-100 mb-4 leading-relaxed line-clamp-4 break-words">
                            {!! strip_tags($news->content) !!}
                        </div>

                        {{-- 🔗 Кнопка "Читать далее" --}}
                        <a href="{{ route('news.show', $news->slug) }}"
                           aria-label="Читать новость {{ $news->title }}"
                           class="mt-auto text-sm text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow block">
                            Читать далее →
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- 📄 Пагинация --}}
            <div class="mt-12">
                {{ $newsList->withQueryString()->links('vendor.pagination.tailwind') }}
            </div>
        @else
            {{-- 🚫 Нет новостей --}}
            <p class="text-center text-gray-500 dark:text-gray-400 text-lg mt-10">Нет опубликованных новостей.</p>
        @endif
    </div>
@endsection
