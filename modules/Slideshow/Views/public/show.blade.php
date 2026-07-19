@extends('layouts.app')

@section('title', $slideshow->title)

@section('content')
  {{-- 🖼️ Заголовок слайдшоу --}}
  <h1 class="text-3xl font-extrabold text-gray-800 mb-10 text-center">
    🎞️ {{ $slideshow->title }}
  </h1>

  @if ($slideshow->items->count())
    {{-- 📦 Сетка карточек с «вписыванием» медиа --}}
    <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3 max-w-screen-xl mx-auto px-4">
      @foreach ($slideshow->items->sortBy('order') as $item)
        @php $src = asset('storage/'.$item->file_path); @endphp
        <article class="flex flex-col rounded-2xl overflow-hidden shadow-lg border border-gray-100 bg-white transition hover:shadow-2xl">
          <div class="relative w-full" style="height: clamp(220px, 36vh, 520px);">
            {{-- размытая подложка --}}
            <div aria-hidden="true" class="absolute inset-0 scale-110 blur-xl opacity-40"
                 style="background:center/cover no-repeat url('{{ $src }}');"></div>

            {{-- медиа целиком в рамке --}}
            <div class="absolute inset-0 flex items-center justify-center">
              @if ($item->media_type === 'image')
                <img src="{{ $src }}" alt="{{ $item->caption ?? 'Слайд' }}"
                     loading="lazy" decoding="async"
                     class="w-full h-full object-contain rounded-md">
              @else
                <video controls muted playsinline
                       class="w-full h-full object-contain bg-black rounded-md">
                  <source src="{{ $src }}" type="video/mp4">
                  Ваш браузер не поддерживает видео.
                </video>
              @endif
            </div>

            {{-- подпись --}}
            @if ($item->caption)
              <div class="absolute top-3 left-1/2 -translate-x-1/2 z-10 max-w-[92%]
                          bg-gradient-to-r from-black/80 via-black/60 to-black/80
                          text-white text-center text-xs sm:text-sm md:text-base font-semibold px-4 py-2
                          rounded-xl shadow-lg backdrop-blur-sm">
                📝 {{ $item->caption }}
              </div>
            @endif
          </div>
        </article>
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
