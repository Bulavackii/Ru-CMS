@extends('layouts.frontend')

@section('title', 'Главная')

@section('content')
{{-- Контейнер прозрачный: фоновый паттерн темы просвечивает под контентом,
     а карточки внутри — стеклянные (.fx-card). Раньше здесь был сплошной
     bg-white, из-за чего центр страницы был белым «блоком» без фона. --}}
{{-- ⚠️ Боковые отступы здесь НЕ дублируем. Их уже даёт контейнер макета
     (layouts/frontend: `container mx-auto px-4 sm:px-6 …`). Раньше поверх
     него стояли ещё два слоя по 16 пикселей с каждой стороны, и на экране
     iPhone XR из 414 под содержимое оставалось 318 — почти четверть ширины
     уходила в поля. Внутренняя обёртка теперь только ограничивает ширину
     на больших экранах. --}}
<div>
  <div class="max-w-screen-2xl mx-auto">
    @php
      // Подписи блоков берём из того же источника, что и список шаблонов
      // в панели. Своя копия здесь уже разошлась: новые шаблоны выводились
      // с заголовком вроде «Gaming», потому что подписи для них тут не было.
      $titles = \Modules\News\Controllers\Admin\NewsController::TEMPLATES;
    @endphp

    {{-- Верхние слайдшоу --}}
    @foreach ($slideshows->where('position', 'top') as $slideshow)
      @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach

    {{-- Страницы, отмеченные для главной --}}
    @if (!empty($homePages) && $homePages->count())
      @include('Menu::frontend.homepage-pages', ['pages' => $homePages])
    @endif

    {{-- Шаблоны --}}
    @foreach ($templates as $key => $newsList)
      @if ($newsList->isEmpty()) @continue @endif

      @php $templateView = 'frontend.templates.' . $key; @endphp

      @if (View::exists($templateView))
        @include($templateView, ['newsList' => $newsList, 'title' => $titles[$key] ?? ucfirst($key)])
      @elseif ($key === 'slideshow')
        <div class="my-8">
          @foreach ($newsList as $news)
            @if ($news->slideshow)
              @include('Slideshow::public.slideshow', ['slideshow' => $news->slideshow])
            @endif
          @endforeach
        </div>
      @else
        <div class="mb-8 sm:mb-10 md:mb-12">
          <h2 class="text-xl sm:text-2xl md:text-3xl font-bold mb-4 sm:mb-6 text-center">{{ $titles[$key] ?? ucfirst($key) }}</h2>
          <x-frontend.news-grid :newsList="$newsList" :title="null" />
        </div>
      @endif
    @endforeach

    {{-- Нижние слайдшоу --}}
    @foreach ($slideshows->where('position', 'bottom') as $slideshow)
      @include('Slideshow::public.slideshow', ['slideshow' => $slideshow])
    @endforeach
  </div>
</div>
@endsection
