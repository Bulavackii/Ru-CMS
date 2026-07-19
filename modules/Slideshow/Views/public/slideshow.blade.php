@php
  $height = $slideshow->height ?? 'clamp(240px, 42vh, 560px)';
  $autoplayDelay = $slideshow->autoplay_delay ?? 5000;
  $transitionEffect = $slideshow->transition_effect ?? 'slide';
  $showPagination = $slideshow->show_pagination ?? true;
  $showNavigation = $slideshow->show_navigation ?? true;
  
  // Функция для получения позиции текста
  $getTextPositionClass = function($position) {
    return match($position ?? 'bottom-right') {
      'top-left' => 'top-4 left-4',
      'top-center' => 'top-4 left-1/2 -translate-x-1/2',
      'top-right' => 'top-4 right-4',
      'center' => 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
      'bottom-left' => 'bottom-4 left-4',
      'bottom-center' => 'bottom-4 left-1/2 -translate-x-1/2',
      'bottom-right' => 'bottom-4 right-4',
      default => 'bottom-4 right-4',
    };
  };
@endphp

<div class="w-full my-8">
  {{-- 🎞️ Обёртка слайдера --}}
  <div class="ru-swiper swiper swiper-{{ $slideshow->id }} max-w-screen-xl mx-auto rounded-xl shadow-md overflow-hidden relative">
    {{-- 🔁 Слайды --}}
    <div class="swiper-wrapper">
      @foreach ($slideshow->items->sortBy('order') as $item)
        @php 
          $src = asset('storage/'.$item->file_path);
          $textPosition = $getTextPositionClass($item->text_position);
          $textColor = $item->text_color ?? '#ffffff';
          $bgColor = $item->background_color ?? '#2563eb';
        @endphp
        <div class="swiper-slide">
          <div class="ru-slide relative w-full" style="height: {{ $height }};">
            {{-- размытый фон под «пустые поля» --}}
            <div aria-hidden="true" class="absolute inset-0 scale-110 blur-xl opacity-40"
                 style="background:center/cover no-repeat url('{{ $src }}');"></div>

            {{-- медиа всегда целиком в кадре --}}
            <div class="absolute inset-0 flex items-center justify-center">
              @if ($item->media_type === 'image')
                <img src="{{ $src }}" 
                     alt="{{ $item->alt_text ?? $item->caption ?? 'Слайд' }}"
                     loading="lazy" 
                     decoding="async"
                     class="w-full h-full object-contain rounded-md">
              @elseif ($item->media_type === 'video')
                <video controls muted playsinline class="w-full h-full object-contain bg-black rounded-md">
                  <source src="{{ $src }}" type="video/mp4">
                  Ваш браузер не поддерживает видео.
                </video>
              @endif
            </div>

            {{-- 💬 Подпись/ссылка с настраиваемой позицией и цветами --}}
            @if ($item->caption)
              <div class="absolute {{ $textPosition }} z-10">
                @if (!empty($item->link))
                  <a href="{{ $item->link }}" target="_blank" rel="noopener"
                     class="inline-block text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full shadow-md transition hover:opacity-90"
                     style="color: {{ $textColor }}; background-color: {{ $bgColor }};">
                    {{ $item->caption }}
                  </a>
                @else
                  <span class="inline-block text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full shadow-md"
                        style="color: {{ $textColor }}; background-color: {{ $bgColor }};">
                    {{ $item->caption }}
                  </span>
                @endif
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>

    {{-- 🔘 Пагинация и стрелки --}}
    @if($showPagination)
      <div class="swiper-pagination !bottom-2"></div>
    @endif
    @if($showNavigation)
      <div class="swiper-button-prev text-white hover:scale-110 transition-transform"></div>
      <div class="swiper-button-next text-white hover:scale-110 transition-transform"></div>
    @endif
  </div>

  {{-- 🚀 CMS нового поколения --}}
  <div class="flex justify-end mt-4">
    <span class="text-sm font-semibold px-3 py-1 rounded-full bg-blue-100 text-blue-700 shadow inline-flex items-center gap-2">
      📹 RU CMS - слайдшоу
    </span>
  </div>
</div>

{{-- 🧩 Стили Swiper --}}
@push('styles')
  <link rel="stylesheet" href="{{ local_css('swiper-bundle.min.css') }}"/>
  <style>
    .ru-swiper .swiper-slide{display:block}
    .ru-swiper .swiper-pagination-bullets{display:flex;justify-content:center;gap:.5rem;padding-bottom:.25rem}
    .ru-swiper .swiper-pagination-bullet{width:10px;height:10px;border-radius:9999px;background:rgba(255,255,255,.45);opacity:1;transition:.2s}
    .ru-swiper .swiper-pagination-bullet:hover{transform:scale(1.1);background:rgba(255,255,255,.75)}
    .ru-swiper .swiper-pagination-bullet-active{background:#2563eb;transform:scale(1.25);box-shadow:0 0 4px rgba(37,99,235,.5)}
  </style>
@endpush

{{-- ⚙️ Swiper Init --}}
@push('scripts')
  <script src="{{ local_js('swiper-bundle.min.js') }}"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const config = {
        loop: true,
        autoplay: { 
          delay: {{ $autoplayDelay }}, 
          disableOnInteraction: false 
        },
        speed: 600,
        effect: '{{ $transitionEffect }}',
        @if($showPagination)
        pagination: { 
          el: '.swiper-{{ $slideshow->id }} .swiper-pagination', 
          clickable: true 
        },
        @endif
        @if($showNavigation)
        navigation: {
          nextEl: '.swiper-{{ $slideshow->id }} .swiper-button-next',
          prevEl: '.swiper-{{ $slideshow->id }} .swiper-button-prev',
        },
        @endif
      };

      // Дополнительные настройки для эффектов
      @if($transitionEffect === 'cube')
      config.cubeEffect = {
        shadow: true,
        slideShadows: true,
        shadowOffset: 20,
        shadowScale: 0.94,
      };
      @elseif($transitionEffect === 'coverflow')
      config.coverflowEffect = {
        rotate: 50,
        stretch: 0,
        depth: 100,
        modifier: 1,
        slideShadows: true,
      };
      @elseif($transitionEffect === 'flip')
      config.flipEffect = {
        slideShadows: true,
        limitRotation: true,
      };
      @endif

      new Swiper('.swiper-{{ $slideshow->id }}', config);
    });
  </script>
@endpush
