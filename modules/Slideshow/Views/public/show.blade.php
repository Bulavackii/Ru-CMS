@extends('layouts.app')

@section('title', $slideshow->title)

@section('content')
    <h1 class="text-3xl font-extrabold text-gray-800 mb-8 text-center">{{ $slideshow->title }}</h1>

    @if($slideshow->items->count())
        <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($slideshow->items->sortBy('order') as $item)
                <div class="flex flex-col rounded-2xl overflow-hidden shadow-lg border border-gray-100 bg-white group transition hover:shadow-2xl">

                    {{-- 📷 Изображение или видео --}}
                    <div class="relative w-full h-64 bg-gray-100">
                        @if ($item->media_type === 'image')
                            <img src="{{ asset('storage/' . $item->file_path) }}"
                                 alt="{{ $item->caption ?? 'Слайд' }}"
                                 class="w-full h-full object-cover">
                        @elseif ($item->media_type === 'video')
                            <video controls class="w-full h-full object-contain bg-black">
                                <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @endif
                    </div>

                    {{-- ✨ Подпись слайда (внизу) --}}
                    @if ($item->caption)
                        <div class="px-4 py-3 bg-gray-50 text-sm text-gray-700 border-t border-gray-200">
                            <span class="block truncate">
                                📝 {{ $item->caption }}
                            </span>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Слайдов пока нет.</p>
    @endif
@endsection
