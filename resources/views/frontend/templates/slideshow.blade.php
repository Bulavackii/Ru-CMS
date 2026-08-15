{{-- resources/views/frontend/templates/slideshow.blade.php --}}

@if ($newsList->isNotEmpty())
    <section class="my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
        {{-- Заголовок секции с анимацией и стилем --}}
        {{-- Плашка раздела — общая для всех шаблонов (см. макет сайта).
         Раньше здесь стоял центрированный заголовок с зашитым
         text-gray-800: на тёмных темах текст пропадал, а название
         шло мимо словаря. --}}
    <div class="fx-section-head">
        <span class="fx-badge"><i class="fas fa-images"></i></span>
        <div>
            <h2 class="fx-section-title">{{ $title ?? __('frontend.templates.slideshow') }}</h2>
        </div>
    </div>

        {{-- Перебираем элементы новостей --}}
        @foreach ($newsList as $item)
            {{-- Выводим только элементы с шаблоном 'slideshow' и существующим слайдшоу --}}
            @if ($item->template === 'slideshow' && $item->slideshow)
                <div class="mb-16 animate-fade-in-down">
                    {{-- Вставка компонента слайдера из модуля Slideshow --}}
                    @include('Slideshow::public.slideshow', ['slideshow' => $item->slideshow])

                    {{-- Если есть подпись к слайдшоу, выводим её снизу --}}
                    @if (!empty($item->slideshow->caption))
                        <p class="text-center text-sm text-gray-600 mt-4 max-w-2xl mx-auto italic select-text">
                            {{ $item->slideshow->caption }}
                        </p>
                    @endif
                </div>
            @endif
        @endforeach
    </section>
@endif
