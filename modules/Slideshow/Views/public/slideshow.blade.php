<div class="w-full my-12">
    {{-- 🎞️ Обёртка слайдера --}}
    <div class="swiper swiper-{{ $slideshow->id }} max-w-screen-xl mx-auto rounded-2xl shadow-lg overflow-hidden relative"
         style="height: 500px;">
        {{-- 🔁 Слайды --}}
        <div class="swiper-wrapper">
            @foreach ($slideshow->items->sortBy('order') as $item)
                <div class="swiper-slide relative group">
                    @if ($item->media_type === 'image')
                        {{-- 🖼️ Изображение --}}
                        <img src="{{ asset('storage/' . $item->file_path) }}"
                             alt="{{ $item->caption ?? 'Слайд' }}"
                             class="w-full h-full object-cover transition-all duration-300">
                    @elseif ($item->media_type === 'video')
                        {{-- 🎥 Видео --}}
                        <video controls muted playsinline class="w-full h-full object-contain bg-black">
                            <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                            Ваш браузер не поддерживает видео.
                        </video>
                    @endif

                    {{-- 💬 Подпись к слайду (появляется при наведении) --}}
                    @if ($item->caption)
                        @if ($item->caption)
    <div
        class="absolute top-6 left-1/2 transform -translate-x-1/2 max-w-[90%] bg-gradient-to-r from-black/80 via-black/60 to-black/80 text-white text-center text-xs sm:text-sm md:text-base font-semibold px-6 py-2 rounded-xl shadow-lg z-10 backdrop-blur-sm">
        ╰┈➤  {{ $item->caption }}
    </div>
@endif

                    @endif
                </div>
            @endforeach
        </div>

        {{-- 🔘 Пагинация и 🔄 стрелки --}}
        <div class="swiper-pagination !bottom-4"></div>
        <div class="swiper-button-prev text-white hover:scale-110 transition-transform"></div>
        <div class="swiper-button-next text-white hover:scale-110 transition-transform"></div>
    </div>
</div>

{{-- 🧩 Стили Swiper --}}
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
@endpush

{{-- ⚙️ Инициализация Swiper --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Swiper('.swiper-{{ $slideshow->id }}', {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                speed: 600,
                pagination: {
                    el: '.swiper-{{ $slideshow->id }} .swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-{{ $slideshow->id }} .swiper-button-next',
                    prevEl: '.swiper-{{ $slideshow->id }} .swiper-button-prev',
                },
                breakpoints: {
                    640: { height: 300 },
                    768: { height: 400 },
                    1024: { height: 500 }
                }
            });
        });
    </script>
@endpush
@push('styles')
<style>
    .swiper-pagination-bullets {
        @apply flex justify-center gap-2 pb-4;
    }

    .swiper-pagination-bullet {
        width: 12px;
        height: 12px;
        border-radius: 9999px;
        background-color: rgba(255, 255, 255, 0.5);
        opacity: 1;
        transition: transform 0.3s ease, background-color 0.3s ease;
    }

    .swiper-pagination-bullet:hover {
        transform: scale(1.2);
        background-color: rgba(255, 255, 255, 0.8);
    }

    .swiper-pagination-bullet-active {
        background-color: #3b82f6; /* Синий цвет — в стиле кнопок Tailwind */
        transform: scale(1.4);
        box-shadow: 0 0 6px rgba(59, 130, 246, 0.5);
    }
</style>
@endpush
