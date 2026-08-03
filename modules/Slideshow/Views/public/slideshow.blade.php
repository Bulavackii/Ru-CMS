@php
  $height = $slideshow->height ?? 'clamp(240px, 42vh, 560px)';
  $autoplayDelay = $slideshow->autoplay_delay ?? 5000;
  $transitionEffect = $slideshow->transition_effect ?? 'slide';
  $showPagination = $slideshow->show_pagination ?? true;
  $showNavigation = $slideshow->show_navigation ?? true;

  // Показываемые слайды. Отдельного флага «активен» у пункта в базе нет,
  // поэтому показанным считается тот, у которого есть файл и понятный тип
  // медиа. Пункт со сломанным media_type раньше давал ПУСТОЙ кадр: место в
  // карусели занимал, точку в пагинации получал, а показывать было нечего.
  // Тот же список идёт в счётчик подписи — иначе он врал бы про число.
  $slides = $slideshow->items
      ->filter(fn ($i) => in_array($i->media_type, ['image', 'video'], true) && filled($i->file_path))
      ->sortBy('order')
      ->values();
  
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
      @foreach ($slides as $item)
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

    {{-- Подпись слайдшоу.
         Раньше она висела отдельной строкой ПОД слайдером и смотрелась
         оторванной от него. Теперь это накладка в левом верхнем углу —
         единственный свободный: стрелки стоят по центру краёв, точки внизу.
         Название берётся у самого слайдшоу, а не захардкожено. --}}
    @php $badge = trim((string) ($slideshow->title ?? '')); @endphp

    <div class="sld-badge">
      <span class="sld-badge__ico"><i class="fas fa-images" aria-hidden="true"></i></span>
      <span class="sld-badge__text">{{ $badge !== '' ? $badge : __('frontend.slideshow.badge') }}</span>

      {{-- Счётчик обновляется на каждом переключении (см. слушатель ниже),
           поэтому сразу видно, где мы в череде слайдов. Атрибут aria-live тут
           намеренно НЕ ставится: слайды листаются сами, и скринридер зачитывал
           бы новый номер каждые несколько секунд поверх остальной страницы. --}}
      <span class="sld-badge__count">
        <b class="sld-badge__cur">1</b><i>/</i>{{ $slides->count() }}
      </span>
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

</div>

{{-- 🧩 Стили Swiper --}}
@push('styles')
<style>
    /* Подпись слайдшоу — накладка поверх картинки. Литеральный CSS: в
       статической сборке Tailwind нет ни прозрачности через /NN, ни
       произвольных значений. Стекло тёмное: под ним произвольное
       изображение, и на светлом оно бы потерялось. */
    .sld-badge{ position:absolute; top:.85rem; left:.85rem; z-index:10;
        display:inline-flex; align-items:center; gap:.55rem; max-width:calc(100% - 5.5rem);
        padding:.3rem; font-size:.78rem; font-weight:600; color:#fff;
        background:rgba(15,23,42,.42); border:1px solid rgba(255,255,255,.22);
        -webkit-backdrop-filter:blur(12px) saturate(160%); backdrop-filter:blur(12px) saturate(160%);
        box-shadow:0 6px 20px rgba(2,6,23,.28);
        transition:background-color .15s, border-color .15s }

    /* Анимации появления здесь намеренно НЕТ. Она была, и отказ у неё скверный:
       в фоновой вкладке время анимаций стоит, элемент застревает в стартовом
       кадре с opacity:0 — бейджа не видно вовсе, пока вкладка не станет
       активной. Ради декоративного въезда так рисковать нечем. */
    .sld-badge:hover{ background:rgba(15,23,42,.58); border-color:rgba(255,255,255,.34) }

    .sld-badge__ico{ display:inline-flex; align-items:center; justify-content:center;
        width:1.55rem; height:1.55rem; flex:none; font-size:.72rem; color:#fff;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }

    /* Длинное название не растягивает накладку на весь слайд, а обрезается. */
    .sld-badge__text{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        letter-spacing:.01em; text-shadow:0 1px 2px rgba(2,6,23,.35) }

    .sld-badge__count{ display:inline-flex; align-items:baseline; gap:.1rem; flex:none;
        padding:.2rem .45rem; font-size:.72rem; font-variant-numeric:tabular-nums;
        background:rgba(255,255,255,.16) }
    .sld-badge__count b{ font-weight:700 }
    .sld-badge__count i{ font-style:normal; opacity:.55; margin:0 .1rem }

    /* На узком экране название прячется — остаётся значок и счётчик. */
    @media (max-width:480px){
        .sld-badge{ top:.5rem; left:.5rem }
        .sld-badge__text{ display:none }
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

      // Счётчик в подписи. realIndex, а не activeIndex: при loop:true
      // Swiper добавляет клоны по краям, и activeIndex считает их тоже.
      const badgeCur = document.querySelector('.swiper-{{ $slideshow->id }} .sld-badge__cur');

      if (badgeCur) {
        config.on = Object.assign({}, config.on, {
          slideChange() { badgeCur.textContent = this.realIndex + 1; },
        });
      }

      new Swiper('.swiper-{{ $slideshow->id }}', config);
    });
  </script>
@endpush
