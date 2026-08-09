{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🎞️  СЛАЙДШОУ (публичная часть)                                  ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Слайдшоу → нужное слайдшоу → слайды.                 ║
    ║    Там же настраиваются высота, задержка автопрокрутки, эффект   ║
    ║    перехода, стрелки и точки.                                    ║
    ║                                                                  ║
    ║  РАЗМЕР КАДРА                                                    ║
    ║    Кадр следует пропорции первой картинки — так вокруг неё не    ║
    ║    остаётся размытых полос. Высота из настроек слайдшоу задаёт   ║
    ║    НИЖНЮЮ границу: кадр не будет ниже неё, но станет выше, если  ║
    ║    этого требует картинка. На телефоне нижняя граница не         ║
    ║    применяется — заданная под широкий экран высота делала бы     ║
    ║    кадр почти квадратным.                                        ║
    ║                                                                  ║
    ║  ПОДПИСЬ СЛАЙДА                                                  ║
    ║    Положение, цвет текста и цвет фона задаются у каждого слайда. ║
    ║    На широком экране подпись лежит поверх картинки в выбранном   ║
    ║    углу. На телефоне она встаёт ОТДЕЛЬНЫМ блоком под кадром:     ║
    ║    поверх невысокого кадра она закрывала до трети картинки, а    ║
    ║    обрезанная в одну строку теряла смысл.                        ║
    ║                                                                  ║
    ║  ПРОСМОТР ВО ВЕСЬ ЭКРАН                                          ║
    ║    Клик по картинке открывает её целиком, вписанной в экран при  ║
    ║    любой его ширине. Стрелками и кнопками листаются остальные    ║
    ║    слайды ТОГО ЖЕ слайдшоу, Esc и клик по фону закрывают.        ║
    ║    Ссылка на подписи слайда работает как прежде — ведёт по       ║
    ║    заданному адресу, а не открывает просмотр.                    ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
  $autoplayDelay = $slideshow->autoplay_delay ?? 5000;
  $transitionEffect = $slideshow->transition_effect ?? 'slide';
  $showPagination = $slideshow->show_pagination ?? true;
  $showNavigation = $slideshow->show_navigation ?? true;

  // Высота из настроек — только для десктопа, см. шапку файла.
  $height = $slideshow->height ?? 'clamp(240px, 42vh, 560px)';

  // Показываемые слайды. Отдельного флага «активен» у пункта в базе нет,
  // поэтому показанным считается тот, у которого есть файл и понятный тип
  // медиа. Пункт со сломанным media_type раньше давал ПУСТОЙ кадр: место в
  // карусели занимал, точку в пагинации получал, а показывать было нечего.
  // Тот же список идёт в счётчик подписи — иначе он врал бы про число.
  $slides = $slideshow->items
      ->filter(fn ($i) => in_array($i->media_type, ['image', 'video'], true) && filled($i->file_path))
      ->sortBy('order')
      ->values();

  // Один слайд — это не карусель. Листать нечего, поэтому стрелки, точки,
  // зацикливание и автопрокрутка отключаются: иначе Swiper на пустом месте
  // клонирует единственный слайд и «перелистывает» его сам в себя.
  $isSingle = $slides->count() < 2;

  $getTextPositionClass = function ($position) {
    return match ($position ?? 'bottom-right') {
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

@if ($slides->isNotEmpty())
<div class="w-full my-4">
  <div class="ru-swiper swiper swiper-{{ $slideshow->id }} max-w-screen-xl mx-auto rounded-xl shadow-md overflow-hidden relative"
       style="--ru-slide-h: {{ $height }};">

    {{-- 🔁 Слайды --}}
    <div class="swiper-wrapper">
      @foreach ($slides as $index => $item)
        @php
          $src = asset('storage/' . $item->file_path);
          $textPosition = $getTextPositionClass($item->text_position);
          $textColor = $item->text_color ?? '#ffffff';
          $bgColor = $item->background_color ?? '#2563eb';
        @endphp

        <div class="swiper-slide">
          <div class="ru-slide">
            {{-- Размытая заглушка под «пустые поля»: картинка вписывается в
                 кадр целиком, и по краям остаётся место. Заполняем его ею же. --}}
            <div aria-hidden="true" class="ru-slide__bg" style="background-image:url('{{ $src }}');"></div>

            <div class="ru-slide__media">
              @if ($item->media_type === 'image')
                {{-- Первый слайд грузится сразу и в приоритете: он почти всегда
                     самый крупный элемент первого экрана, и отложенная загрузка
                     напрямую ухудшала бы показатель LCP. Остальные — отложенно. --}}
                <img src="{{ $src }}"
                     alt="{{ $item->t('alt_text') ?? $item->t('caption') ?? __('frontend.slideshow.slide') }}"
                     class="ru-zoomable" tabindex="0" role="button"
                     aria-label="{{ __('frontend.slideshow.zoom') }}"
                     @if ($index === 0) fetchpriority="high" decoding="async"
                     @else loading="lazy" decoding="async" @endif>
              @elseif ($item->media_type === 'video')
                {{-- Без autoplay: звук и движение по своей воле — плохая идея,
                     а браузеры такое всё равно блокируют. Останавливается при
                     уходе слайда, см. слушатель в скрипте ниже. --}}
                <video controls muted playsinline preload="metadata">
                  <source src="{{ $src }}" type="video/mp4">
                  {{ __('frontend.slideshow.no_video') }}
                </video>
              @endif
            </div>

          </div>

          {{-- 💬 Подпись слайда. Лежит РЯДОМ с кадром, а не внутри него:
               на широком экране она накладывается на картинку (коробка у
               слайда та же, позиционирование не меняется), а на телефоне
               встаёт обычным блоком под кадром. Внутри кадра этого было не
               сделать: там жёсткая пропорция и обрезка по краям, и подпись
               могла только висеть поверх картинки, закрывая её. --}}
          @if ($item->caption)
            <div class="ru-slide__cap absolute {{ $textPosition }} z-10">
              @if (!empty($item->link))
                <a href="{{ $item->link }}" target="_blank" rel="noopener"
                   class="ru-slide__capInner"
                   style="color: {{ $textColor }}; background-color: {{ $bgColor }};">
                  {{ $item->t('caption') }}
                </a>
              @else
                <span class="ru-slide__capInner"
                      style="color: {{ $textColor }}; background-color: {{ $bgColor }};">
                  {{ $item->t('caption') }}
                </span>
              @endif
            </div>
          @endif
        </div>
      @endforeach
    </div>

    {{-- Подпись слайдшоу — накладка в левом верхнем углу. Он единственный
         свободный: стрелки стоят по центру краёв, точки внизу. --}}
    @php $badge = trim((string) ($slideshow->title ?? '')); @endphp

    <div class="sld-badge">
      <span class="sld-badge__ico"><i class="fas fa-images" aria-hidden="true"></i></span>
      <span class="sld-badge__text">{{ $badge !== '' ? $badge : __('frontend.slideshow.badge') }}</span>

      @unless ($isSingle)
        {{-- Счётчик обновляется на каждом переключении (см. слушатель ниже).
             Атрибут aria-live тут намеренно НЕ ставится: слайды листаются
             сами, и скринридер зачитывал бы новый номер каждые несколько
             секунд поверх остальной страницы. --}}
        <span class="sld-badge__count">
          <b class="sld-badge__cur">1</b><i>/</i>{{ $slides->count() }}
        </span>
      @endunless
    </div>

    @unless ($isSingle)
      @if ($showPagination)
        <div class="swiper-pagination"></div>
      @endif

      {{-- Полоса прогресса до смены слайда. Показывается только при
           работающей автопрокрутке, поэтому её разметка тоже под условием. --}}
      <div class="ru-progress" aria-hidden="true"><div class="ru-progress__fill"></div></div>

      @if ($showNavigation)
        {{-- Кнопки, а не div-ы: только так они попадают в обход по Tab и
             срабатывают на пробел/Enter. Размер 44px — минимум, за который
             уверенно попадают пальцем. --}}
        <button type="button" class="ru-nav ru-nav--prev swiper-button-prev"
                aria-label="{{ __('frontend.slideshow.prev') }}"></button>
        <button type="button" class="ru-nav ru-nav--next swiper-button-next"
                aria-label="{{ __('frontend.slideshow.next') }}"></button>
      @endif
    @endunless
  </div>
</div>
@endif

{{-- 🧩 Стили. Обёрнуты в once: партиал подключается по разу на каждое
     слайдшоу, и без этого и таблица Swiper, и весь блок ниже уезжали в
     страницу по нескольку раз (проверено — три копии таблицы). --}}
@once
@push('styles')
  <link rel="stylesheet" href="{{ local_css('swiper-bundle.min.css') }}"/>
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни прозрачности
       через дробь, ни произвольных значений, ни половины цветов. Акценты
       берутся из активной темы через переменные. */

    /* ── Кадр слайда ────────────────────────────────────────────────── */
    .ru-swiper .swiper-slide{ display:block; position:relative }

    /* Кадр следует пропорции картинки, а не фиксированной высоте. Иначе
       вокруг неё остаются размытые полосы: при кадре 1280x420 и баннере
       1600x600 это 80px с каждого бока на десктопе и 38% площади на
       телефоне. Пропорция проставляется скриптом после загрузки первого
       слайда; до этого работает запасная 16/9.
       Высота из настроек слайдшоу стала НИЖНЕЙ границей — кадр не будет
       ниже неё, но может стать выше, чтобы вместить картинку целиком.
       Потолок в 78vh не даёт вертикальному баннеру занять весь экран. */
    .ru-slide{ position:relative; width:100%; height:auto;
        aspect-ratio:var(--ru-ar, 16/9);
        min-height:var(--ru-slide-h, 420px); max-height:78vh;
        overflow:hidden; background:#0f172a }

    /* Заглушка по краям — та же картинка, увеличенная и размытая. */
    .ru-slide__bg{ position:absolute; inset:0; background-position:center;
        background-size:cover; background-repeat:no-repeat;
        transform:scale(1.1); filter:blur(18px); opacity:.4 }

    .ru-slide__media{ position:absolute; inset:0; display:flex;
        align-items:center; justify-content:center }
    .ru-slide__media img,
    .ru-slide__media video{ width:100%; height:100%; object-fit:contain; display:block }
    .ru-slide__media video{ background:#000 }

    /* ── Подпись слайда ─────────────────────────────────────────────── */
    .ru-slide__cap{ max-width:calc(100% - 2rem) }
    .ru-slide__capInner{ display:inline-block; padding:.375rem 1rem;
        font-size:.875rem; font-weight:600; line-height:1.35;
        box-shadow:0 4px 14px rgba(2,6,23,.28); transition:opacity .15s }
    a.ru-slide__capInner:hover{ opacity:.9 }

    /* ── Стрелки ────────────────────────────────────────────────────── */
    /* Swiper рисует свой значок псевдоэлементом; собственный размер кнопки
       он при этом не задаёт, отсюда прежние 27x44 — мимо пальца. */
    .ru-swiper .ru-nav{ width:44px; height:44px; margin-top:-22px; padding:0;
        color:#fff; background:rgba(15,23,42,.42); border:1px solid rgba(255,255,255,.22);
        -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px);
        transition:background-color .15s, border-color .15s }
    .ru-swiper .ru-nav:hover{ background:rgba(15,23,42,.62); border-color:rgba(255,255,255,.4) }
    .ru-swiper .ru-nav:focus-visible{ outline:2px solid #fff; outline-offset:2px }
    .ru-swiper .ru-nav::after{ font-size:16px; font-weight:700 }
    .ru-swiper .ru-nav.swiper-button-disabled{ opacity:0; pointer-events:none }

    /* ── Точки ──────────────────────────────────────────────────────── */
    .ru-swiper .swiper-pagination-bullets{ display:flex; justify-content:center;
        align-items:center; gap:.5rem; padding-bottom:.25rem }
    /* Сама точка 10px, но кликается площадка 24px — иначе по ней трудно
       попасть пальцем, а увеличивать саму точку некрасиво. */
    /* Радиусы тут не задаются: на фронте прямые углы включены глобально
       (body.fx-sharp с important в лейауте), и любой border-radius здесь
       был бы мёртвым кодом. */
    .ru-swiper .swiper-pagination-bullet{ position:relative; width:22px; height:4px;
        background-color:rgba(255,255,255,.45); opacity:1;
        transition:background-color .2s, width .2s }
    .ru-swiper .swiper-pagination-bullet::before{ content:''; position:absolute;
        top:-10px; left:-4px; right:-4px; bottom:-10px }
    .ru-swiper .swiper-pagination-bullet:hover{ background-color:rgba(255,255,255,.8) }
    /* Селектор из трёх классов и длинная форма background-color намеренно:
       у базового правила рядом та же специфичность, а таблица Swiper
       подключается на странице дважды, и вторая копия идёт ПОСЛЕ этого
       блока. Сокращённое background с var() тут проигрывало (проверено
       замером: активная точка оставалась белой). */
    .ru-swiper .swiper-pagination-bullet.swiper-pagination-bullet-active{
        background-color:var(--color-primary,#6366f1); width:38px;
        box-shadow:0 0 0 1px rgba(255,255,255,.35) }

    /* ── Полоса прогресса автопрокрутки ─────────────────────────────── */
    /* Показывает, сколько осталось до смены слайда. Ширина задаётся из
       скрипта, а не CSS-анимацией: в фоновой вкладке время анимаций стоит,
       и полоса застыла бы на месте (эта грабля уже ловилась на подписи). */
    .ru-progress{ position:absolute; left:0; right:0; top:0; z-index:11;
        height:3px; background:rgba(255,255,255,.18); pointer-events:none }
    .ru-progress__fill{ height:100%; width:0;
        background:linear-gradient(90deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }

    /* ── Подпись слайдшоу ───────────────────────────────────────────── */
    /* Стекло тёмное: под ним произвольное изображение, на светлом фоне
       подпись терялась бы. Анимации появления здесь намеренно НЕТ — в
       фоновой вкладке время анимаций стоит, и элемент застрял бы в
       стартовом кадре невидимым, пока вкладка не станет активной. */
    .sld-badge{ position:absolute; top:.85rem; left:.85rem; z-index:10;
        display:inline-flex; align-items:center; gap:.55rem; max-width:calc(100% - 7rem);
        padding:.3rem; font-size:.78rem; font-weight:600; color:#fff;
        background:rgba(15,23,42,.42); border:1px solid rgba(255,255,255,.22);
        -webkit-backdrop-filter:blur(12px) saturate(160%); backdrop-filter:blur(12px) saturate(160%);
        box-shadow:0 6px 20px rgba(2,6,23,.28);
        transition:background-color .15s, border-color .15s }
    .sld-badge:hover{ background:rgba(15,23,42,.58); border-color:rgba(255,255,255,.34) }

    .sld-badge__ico{ display:inline-flex; align-items:center; justify-content:center;
        width:1.55rem; height:1.55rem; flex:none; font-size:.72rem; color:var(--on-accent,#fff);
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }

    .sld-badge__text{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        letter-spacing:.01em; text-shadow:0 1px 2px rgba(2,6,23,.35) }

    .sld-badge__count{ display:inline-flex; align-items:baseline; gap:.1rem; flex:none;
        padding:.2rem .45rem; font-size:.72rem; font-variant-numeric:tabular-nums;
        background:rgba(255,255,255,.16) }
    .sld-badge__count b{ font-weight:700 }
    .sld-badge__count i{ font-style:normal; opacity:.55; margin:0 .1rem }

    /* ── Планшет и уже ──────────────────────────────────────────────── */
    @media (max-width:767px){
        /* Высота из настроек рассчитана на десктоп. На узком экране она
           делает кадр почти квадратным: широкий баннер занимает треть, а
           две трети — размытая заглушка. Считаем кадр по пропорции. */
        /* Пропорция та же, что и на десктопе (её проставляет скрипт), но
           нижняя граница из настроек тут не применяется: заданная под
           широкий экран высота делала бы кадр почти квадратным. */
        .ru-swiper .ru-slide{ height:auto; aspect-ratio:var(--ru-ar, 16/9);
            min-height:0; max-height:60vh }

        /* Плавающая пилюля в 375px не помещается и обрезается краем кадра.
           Разворачиваем её в полосу во всю ширину внизу — читается лучше и
           не зависит от выбранного положения. */
        /* Подпись перестаёт быть накладкой и встаёт под кадром обычным
           блоком. Поверх картинки на телефоне она закрывала до трети
           невысокого кадра, а обрезанная в одну строку теряла смысл —
           теперь читается целиком и ничего не перекрывает.
           Селектор из двух классов намеренно: таблица Tailwind грузится
           ПОЗЖЕ этого блока, и при равной специфичности её absolute и
           bottom-4/right-4 перебили бы правило (проверено замером). */
        .swiper-slide .ru-slide__cap{ position:static; max-width:none; transform:none }
        .swiper-slide .ru-slide__capInner{ display:block; width:100%;
            padding:.5rem .75rem; font-size:.8125rem; line-height:1.35;
            text-align:center; box-shadow:none }

        /* Точки остаются на своём месте: подпись уехала из кадра, и
           перекрывать друг друга им больше нечем. */

        /* Бейдж с названием слайдшоу на телефоне убран. Кадр широкого
           баннера тут невысокий, и подпись поверх него закрывает картинку;
           это украшение, а не содержимое. Позицию показывают точки. */
        .ru-swiper .sld-badge{ display:none }

        /* Стрелки убираем: на узком экране они закрывают картинку, а листать
           там естественнее пальцем. Точки и свайп остаются. */
        .ru-swiper .ru-nav.swiper-button-prev,
        .ru-swiper .ru-nav.swiper-button-next{ display:none }
    }

    @media (max-width:480px){
        .sld-badge{ top:.5rem; left:.5rem; max-width:calc(100% - 1rem) }
        .sld-badge__text{ display:none }
    }
</style>
@endpush
@endonce

{{-- ⚙️ Инициализация Swiper. Сама библиотека — один раз на страницу,
     а настройка ниже своя у каждого слайдшоу. --}}
@once
@push('scripts')
  <script src="{{ local_js('swiper-bundle.min.js') }}"></script>

@endpush
@endonce

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const root = document.querySelector('.swiper-{{ $slideshow->id }}');
      if (!root) return;

      const single = {{ $isSingle ? 'true' : 'false' }};

      // Посетитель мог попросить систему не анимировать интерфейс. Карусель,
      // которая листается сама, — ровно то, от чего это спасает.
      const calmMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      const config = {
        loop: !single,
        speed: calmMotion ? 0 : 600,
        effect: '{{ $transitionEffect }}',
        // Пустой хвост в конце — известная беда loop-каруселей на узких
        // экранах; watchOverflow заодно гасит листание, если слайд один.
        watchOverflow: true,
        keyboard: { enabled: true, onlyInViewport: true },
        // На узком экране подпись стоит ПОД кадром, и слайды с разной длиной
        // подписи получаются разной высоты. Без autoHeight контейнер держал бы
        // высоту самого длинного, оставляя под короткими пустоту.
        breakpoints: {
          0: { autoHeight: true },
          768: { autoHeight: false },
        },
        a11y: {
          prevSlideMessage: @js(__('frontend.slideshow.prev')),
          nextSlideMessage: @js(__('frontend.slideshow.next')),
          paginationBulletMessage: @js(__('frontend.slideshow.go_to')),
        },
      };

      if (!single && !calmMotion) {
        config.autoplay = {
          delay: {{ $autoplayDelay }},
          disableOnInteraction: false,
          // Иначе слайд уезжает из-под курсора, пока человек читает подпись
          // или целится в ссылку на ней.
          pauseOnMouseEnter: true,
        };
      }

      @if($showPagination)
      if (!single) {
        config.pagination = {
          el: '.swiper-{{ $slideshow->id }} .swiper-pagination',
          clickable: true,
        };
      }
      @endif

      @if($showNavigation)
      if (!single) {
        config.navigation = {
          nextEl: '.swiper-{{ $slideshow->id }} .swiper-button-next',
          prevEl: '.swiper-{{ $slideshow->id }} .swiper-button-prev',
        };
      }
      @endif

      // Дополнительные настройки для эффектов
      @if($transitionEffect === 'cube')
      config.cubeEffect = { shadow: true, slideShadows: true, shadowOffset: 20, shadowScale: 0.94 };
      @elseif($transitionEffect === 'coverflow')
      config.coverflowEffect = { rotate: 50, stretch: 0, depth: 100, modifier: 1, slideShadows: true };
      @elseif($transitionEffect === 'flip')
      config.flipEffect = { slideShadows: true, limitRotation: true };
      @endif

      // Пропорция кадра берётся у первой картинки. Без этого кадр живёт по
      // фиксированной высоте, картинка вписывается в него целиком, и по краям
      // остаются размытые полосы — на телефоне это была треть площади.
      // Слайды одного слайдшоу практически всегда одного размера, поэтому
      // первой достаточно; при ошибке загрузки остаётся запасная 16/9.
      // Объявлено заранее: пропорция проставляется после загрузки картинки,
      // то есть уже ПОСЛЕ создания слайдера, и его нужно об этом уведомить.
      let swiper = null;

      // Высота слайда меняется дважды после запуска: когда становится
      // известна пропорция кадра и когда переносится подпись под ним. Swiper
      // замеряет высоту один раз при инициализации и сам её не пересчитывает
      // — без этого вызова нижняя строка подписи уходила под край и
      // обрезалась (кадр уже вырос, а контейнер остался прежним).
      const pagination = root.querySelector('.swiper-pagination');
      const narrow = window.matchMedia('(max-width: 767px)');

      // Точки прижаты к низу слайдера. На узком экране в слайд входит ещё и
      // подпись под кадром, и точки оказывались поверх её текста. Поднимаем
      // их над подписью — её высота зависит от длины текста, в CSS такое не
      // вычислить, поэтому отступ задаётся здесь.
      const placePagination = () => {
        if (!pagination) return;

        if (!narrow.matches) { pagination.style.bottom = ''; return; }

        const cap = root.querySelector('.swiper-slide-active .ru-slide__cap');
        pagination.style.bottom = cap ? (Math.round(cap.getBoundingClientRect().height) + 8) + 'px' : '';
      };

      const remeasure = () => {
        if (!swiper || swiper.destroyed) return;
        swiper.update();
        if (swiper.params.autoHeight) swiper.updateAutoHeight(0);
        placePagination();
      };

      const setRatio = (img) => {
        if (!img || !img.naturalWidth || !img.naturalHeight) return;
        root.style.setProperty('--ru-ar', img.naturalWidth + ' / ' + img.naturalHeight);
        remeasure();
      };

      const firstImg = root.querySelector('.swiper-slide img');
      if (firstImg) {
        if (firstImg.complete) setRatio(firstImg);
        else firstImg.addEventListener('load', () => setRatio(firstImg), { once: true });
      }

      const badgeCur = root.querySelector('.sld-badge__cur');
      const progress = root.querySelector('.ru-progress');
      const progressFill = root.querySelector('.ru-progress__fill');

      config.on = {
        // Swiper сам сообщает, сколько осталось до смены слайда. Ведём полосу
        // отсюда, а не CSS-анимацией: в фоновой вкладке анимации стоят, и
        // полоса застревала бы (та же грабля, что была с подписью).
        autoplayTimeLeft(sw, time, percentage) {
          if (progressFill) progressFill.style.width = ((1 - percentage) * 100).toFixed(2) + '%';
        },
        autoplayStop() { if (progress) progress.style.opacity = '0'; },
        autoplayStart() { if (progress) progress.style.opacity = ''; },
        slideChange() {
          // У соседнего слайда подпись может быть другой длины — значит и
          // место для точек другое.
          placePagination();

          // realIndex, а не activeIndex: при loop:true Swiper добавляет клоны
          // по краям, и activeIndex считает их тоже.
          if (badgeCur) badgeCur.textContent = this.realIndex + 1;

          // Видео уехавшего слайда продолжало играть за кадром — было слышно,
          // но не видно. Останавливаем всё, что не на экране.
          root.querySelectorAll('video').forEach((v) => {
            if (!v.closest('.swiper-slide').classList.contains('swiper-slide-active')) {
              v.pause();
            }
          });
        },
      };

      // Без автопрокрутки полоса прогресса бессмысленна — показывать нечего.
      if (!config.autoplay && progress) progress.remove();

      swiper = new Swiper(root, config);

      // Пропорция могла быть известна ещё до создания слайдера (картинка из
      // кеша) — тогда remeasure выше отработал вхолостую, повторяем.
      remeasure();

      // Отложенные картинки соседних слайдов приходят позже и тоже меняют
      // высоту, а поворот экрана меняет и ширину, и пропорцию подписи.
      root.querySelectorAll('img').forEach((img) => {
        if (!img.complete) img.addEventListener('load', remeasure, { once: true });
      });
      window.addEventListener('orientationchange', () => setTimeout(remeasure, 120));
      narrow.addEventListener('change', remeasure);

      // Пока человек проигрывает видео, карусель не должна его перелистывать.
      root.querySelectorAll('video').forEach((v) => {
        v.addEventListener('play', () => swiper.autoplay && swiper.autoplay.stop());
        v.addEventListener('pause', () => swiper.autoplay && swiper.autoplay.start());
        v.addEventListener('ended', () => swiper.autoplay && swiper.autoplay.start());
      });

      // Автопрокрутка не должна уводить слайд, пока фокус внутри карусели:
      // человек листает с клавиатуры или целится в ссылку на подписи.
      root.addEventListener('focusin', () => swiper.autoplay && swiper.autoplay.stop());
      root.addEventListener('focusout', () => swiper.autoplay && swiper.autoplay.start());
    });
  </script>
@endpush
