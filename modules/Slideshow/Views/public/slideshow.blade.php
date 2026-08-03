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
                     alt="{{ $item->t('alt_text') ?? $item->t('caption') ?? 'Слайд' }}"
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
                    {{ $item->t('caption') }}
                  </a>
                @else
                  <span class="inline-block text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full shadow-md"
                        style="color: {{ $textColor }}; background-color: {{ $bgColor }};">
                    {{ $item->t('caption') }}
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

  {{-- Подпись слайдшоу.
       Раньше здесь стояло захардкоженное «RU CMS - слайдшоу» — одно и то
       же под любым слайдшоу на любом языке. Теперь берётся название самого
       слайдшоу, а если его нет — подпись из словаря. --}}
  @php $badge = trim((string) ($slideshow->title ?? '')); @endphp

  <div class="sld-badge__row">
    <span class="sld-badge">
      <span class="sld-badge__ico"><i class="fas fa-images"></i></span>
      <span class="sld-badge__text">{{ $badge !== '' ? $badge : __('frontend.slideshow.badge') }}</span>
      <span class="sld-badge__count">{{ $slideshow->items->count() }}</span>
    </span>
  </div>
</div>

{{-- 🧩 Стили Swiper --}}
@push('styles')
<style>
    /* Подпись под слайдшоу. Литеральный CSS: в статической сборке Tailwind
       нет ни прозрачности через /NN, ни произвольных значений. Цвета — из
       активной темы, поэтому бейдж не спорит с оформлением. */
    .sld-badge__row{ display:flex; justify-content:flex-end; margin-top:.75rem }

    .sld-badge{ display:inline-flex; align-items:center; gap:.5rem; padding:.35rem .4rem .35rem .4rem;
        font-size:.8rem; font-weight:600; color:#334155;
        background:rgba(255,255,255,.72); border:1px solid rgba(17,24,39,.08);
        -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px);
        box-shadow:0 4px 14px rgba(15,23,42,.08); transition:border-color .15s, transform .15s }
    .sld-badge:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-1px) }

    .sld-badge__ico{ display:inline-flex; align-items:center; justify-content:center;
        width:1.6rem; height:1.6rem; flex:none; font-size:.75rem; color:#fff;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }

    .sld-badge__text{ padding-right:.15rem }

    /* Число слайдов — сразу видно, сколько их, без счёта точек. */
    .sld-badge__count{ display:inline-flex; align-items:center; justify-content:center;
        min-width:1.5rem; height:1.5rem; padding:0 .35rem; font-size:.72rem; font-weight:700;
        color:var(--color-primary,#6366f1); background:rgba(99,102,241,.12) }

    @media (prefers-color-scheme: dark){
        .sld-badge{ background:rgba(17,24,39,.72); border-color:rgba(255,255,255,.1); color:#e2e8f0 }
        .sld-badge__count{ background:rgba(99,102,241,.24); color:#c7d2fe }
    }
</style>
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
