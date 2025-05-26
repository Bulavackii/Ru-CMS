@extends('layouts.app')

@section('title', $slideshow->title)

@section('content')
    {{-- 🖼️ Заголовок слайдшоу --}}
    <h1 class="text-3xl font-extrabold text-gray-800 mb-10 text-center">
        🎞️ {{ $slideshow->title }}
    </h1>

    @if ($slideshow->items->count())
        {{-- 📦 Сетка слайдов --}}
        <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3 max-w-screen-xl mx-auto px-4">
            @foreach ($slideshow->items->sortBy('order') as $item)
                <div
                    class="flex flex-col rounded-2xl overflow-hidden shadow-lg border border-gray-100 bg-white group transition hover:shadow-2xl">

                    {{-- 🖼️ Изображение или видео с подписью --}}
                    <div class="relative w-full aspect-video flex items-center justify-center bg-gray-100">

                        {{-- 💬 Подпись поверх --}}
                        @if ($item->caption)
                            <div
                                class="absolute top-4 left-1/2 transform -translate-x-1/2 max-w-[95%]
                bg-blue-600/90 text-white text-center text-base sm:text-lg md:text-xl
                font-extrabold px-6 py-3 rounded-2xl shadow-2xl z-20 backdrop-blur
                animate-fade-in animate-delay-200">
                                🚀 {{ $item->caption }}
                            </div>
                        @endif

                        {{-- 🎞️ Медиафайл --}}
                        @if ($item->media_type === 'image')
                            <img src="{{ asset('storage/' . $item->file_path) }}" alt="{{ $item->caption ?? 'Слайд' }}"
                                class="max-w-full max-h-full object-contain transition-transform duration-300">
                        @elseif ($item->media_type === 'video')
                            <video controls muted playsinline
                                class="max-w-full max-h-full object-contain bg-black transition-transform duration-300">
                                <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 🚀 CMS нового поколения --}}
        <div class="flex justify-center mt-12">
            <span class="text-sm font-semibold px-4 py-2 rounded-full bg-blue-100 text-blue-600 shadow-md">
                🚀 CMS нового поколения
            </span>
        </div>
    @else
        {{-- 🚫 Нет слайдов --}}
        <div class="text-center text-gray-500 text-lg py-10">
            😔 Пока нет слайдов для отображения.
        </div>
    @endif
@endsection
