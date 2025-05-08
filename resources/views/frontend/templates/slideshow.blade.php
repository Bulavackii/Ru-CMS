{{-- resources/views/frontend/templates/slideshow.blade.php --}}
@if ($newsList->isNotEmpty())
    <section class="my-12 max-w-screen-xl mx-auto px-4">
        <h2 class="text-3xl font-extrabold text-center text-gray-800 tracking-tight mb-10 animate-fade-in">
            {{ $title ?? 'Слайдшоу' }}
        </h2>

        @foreach ($newsList as $item)
            @if ($item->template === 'slideshow' && $item->slideshow)
                <div class="mb-16 animate-fade-in-down">
                    {{-- Вставка самого слайдера --}}
                    @include('Slideshow::public.slideshow', ['slideshow' => $item->slideshow])

                    {{-- Подпись к слайдшоу, если есть --}}
                    @if (!empty($item->slideshow->caption))
                        <p class="text-center text-sm text-gray-600 mt-4 max-w-2xl mx-auto italic">
                            {{ $item->slideshow->caption }}
                        </p>
                    @endif
                </div>
            @endif
        @endforeach
    </section>
@endif
