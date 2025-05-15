@extends('layouts.frontend')

@section('title', 'Результаты поиска')

@section('content')
    <h1 class="text-3xl font-extrabold text-center text-blue-900 mb-8 px-2 sm:px-0">
        🔍 Результаты поиска: <span class="text-gray-800 break-words">{{ $query }}</span>
    </h1>

    @if ($results->count())
        <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($results as $news)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-5 flex flex-col transition hover:shadow-xl">
                    {{-- 📰 Заголовок --}}
                    <h2 class="text-lg font-bold text-gray-900 leading-tight mb-2 break-words">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                            {{ $news->title }}
                        </a>
                    </h2>

                    {{-- 💬 Краткое описание --}}
                    <p class="text-sm text-gray-600 mb-3 line-clamp-4 break-words">
                        {!! Str::limit(strip_tags($news->content), 180) !!}
                    </p>

                    {{-- 📅 Дата и ссылка --}}
                    <div class="mt-auto text-sm text-gray-500 flex justify-between items-center flex-wrap gap-2">
                        <span class="whitespace-nowrap">📅 {{ $news->created_at->format('d.m.Y') }}</span>
                        <a href="{{ route('news.show', $news->slug) }}"
                           class="text-blue-600 hover:underline whitespace-nowrap">
                            Подробнее →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 🔽 Пагинация --}}
        <div class="mt-10">
            {{ $results->appends(['q' => $query])->links('vendor.pagination.tailwind') }}
        </div>
    @else
        <div class="text-center text-gray-500 text-lg py-10">
            😞 Ничего не найдено
        </div>
    @endif
@endsection
