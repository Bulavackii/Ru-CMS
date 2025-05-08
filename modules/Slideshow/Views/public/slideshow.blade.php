<div class="w-full my-12">
    <div class="swiper swiper-{{ $slideshow->id }} max-w-screen-xl mx-auto rounded-2xl shadow overflow-hidden relative"
        style="height: 500px;">
        <div class="swiper-wrapper">
            @foreach ($slideshow->items->sortBy('order') as $item)
                <div class="swiper-slide relative group">
                    @if ($item->media_type === 'image')
                        <img src="{{ asset('storage/' . $item->file_path) }}" alt="{{ $item->caption ?? 'Слайд' }}"
                            class="w-full h-full object-cover">
                    @elseif ($item->media_type === 'video')
                        <video controls muted playsinline class="w-full h-full object-contain bg-black">
                            <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                            Ваш браузер не поддерживает видео.
                        </video>
                    @endif

                    {{-- ✨ Подпись слайда --}}
                    @if ($item->caption)
                        <div
                            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 max-w-[90%] bg-black/70 text-white text-base md:text-lg font-semibold italic tracking-wide px-6 py-3 rounded-xl shadow-lg text-center z-10 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 ease-in-out">
                            {{ $item->caption }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Пагинация и стрелки --}}
        <div class="swiper-pagination !bottom-4"></div>
        <div class="swiper-button-prev text-white hover:scale-110 transition"></div>
        <div class="swiper-button-next text-white hover:scale-110 transition"></div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Swiper('.swiper-{{ $slideshow->id }}', {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                speed: 700,
                effect: 'slide',
                pagination: {
                    el: '.swiper-{{ $slideshow->id }} .swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-{{ $slideshow->id }} .swiper-button-next',
                    prevEl: '.swiper-{{ $slideshow->id }} .swiper-button-prev',
                },
            });
        });
    </script>
@endpush
