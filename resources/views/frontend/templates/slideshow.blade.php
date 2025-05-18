{{-- resources/views/frontend/templates/slideshow.blade.php --}}

@if ($newsList->isNotEmpty())
    <section class="my-12 max-w-screen-xl mx-auto px-4">
        {{-- Заголовок секции с анимацией и стилем --}}
        <h2 class="text-3xl font-extrabold text-center text-gray-800 tracking-tight mb-10 animate-fade-in flex items-center justify-center gap-2 select-none">
            <i class="fas fa-images text-blue-600"></i>
            {{ $title ?? 'Слайдшоу' }}
        </h2>

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
