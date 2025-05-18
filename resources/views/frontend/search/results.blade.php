@extends('layouts.frontend')

@section('title', 'Результаты поиска')

@section('content')
    <h1 class="text-3xl font-extrabold text-center text-blue-900 mb-10 px-4 sm:px-0 leading-tight">
        🔍 Результаты поиска: <span class="text-gray-800 break-words">{{ $query }}</span>
    </h1>

    @if ($results->count())
        {{-- 🧾 Обёртка: flex + wrap + центрирование --}}
        <div class="flex flex-wrap justify-center gap-6">
            @foreach ($results as $news)
                <div class="w-full sm:w-[90%] md:w-[45%] lg:w-[30%] max-w-sm bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition p-5 flex flex-col">

                    {{-- 📰 Заголовок --}}
                    <h2 class="text-lg font-bold text-gray-900 mb-2 leading-snug break-words">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                            {{ $news->title }}
                        </a>
                    </h2>

                    {{-- 📄 Краткое описание --}}
                    <p class="text-sm text-gray-600 mb-4 line-clamp-4 break-words">
                        {!! Str::limit(strip_tags($news->content), 180) !!}
                    </p>

                    {{-- 📅 Дата и ссылка --}}
                    <div class="mt-auto flex justify-between items-center text-sm text-gray-500 gap-2 flex-wrap">
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            <i class="fas fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                        </span>
                        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline whitespace-nowrap">
                            Подробнее →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 📄 Пагинация --}}
        <div class="mt-12">
            {{ $results->appends(['q' => $query])->links('vendor.pagination.tailwind') }}
        </div>
    @else
        {{-- 🙁 Ничего не найдено --}}
        <div class="text-center text-gray-500 text-lg py-20">
            😞 Ничего не найдено по запросу <br><span class="text-blue-600 font-medium">"{{ $query }}"</span>
        </div>
    @endif
@endsection
