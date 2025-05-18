@extends('layouts.app')

@section('title', $slideshow->title)

@section('content')
    {{-- 🖼️ Заголовок слайдшоу --}}
    <h1 class="text-3xl font-extrabold text-gray-800 mb-10 text-center">
        🎞️ {{ $slideshow->title }}
    </h1>

    @if($slideshow->items->count())
        {{-- 📦 Сетка слайдов --}}
        <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3 max-w-screen-xl mx-auto px-4">
            @foreach ($slideshow->items->sortBy('order') as $item)
                <div class="flex flex-col rounded-2xl overflow-hidden shadow-lg border border-gray-100 bg-white group transition hover:shadow-2xl">

                    {{-- 🖼️ Изображение или видео с подписью вверху --}}
                    <div class="relative w-full h-64 bg-gray-100">
                        {{-- 💬 Подпись поверх (вверху, по центру) --}}
                        @if ($item->caption)
                            <div class="absolute top-3 left-1/2 transform -translate-x-1/2 max-w-[90%]
                                        bg-gradient-to-r from-black/80 via-black/60 to-black/80 text-white
                                        text-center text-xs sm:text-sm md:text-base font-semibold px-5 py-2
                                        rounded-xl shadow-lg z-10 backdrop-blur-sm">
                                📝 {{ $item->caption }}
                            </div>
                        @endif

                        {{-- 🎞️ Медиафайл --}}
                        @if ($item->media_type === 'image')
                            <img src="{{ asset('storage/' . $item->file_path) }}"
                                 alt="{{ $item->caption ?? 'Слайд' }}"
                                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                        @elseif ($item->media_type === 'video')
                            <video controls muted playsinline class="w-full h-full object-contain bg-black">
                                <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- 🚫 Нет слайдов --}}
        <div class="text-center text-gray-500 text-lg py-10">
            😔 Пока нет слайдов для отображения.
        </div>
    @endif
@endsection
