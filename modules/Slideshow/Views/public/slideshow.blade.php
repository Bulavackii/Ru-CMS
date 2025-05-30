<div class="w-full my-8">
    {{-- 🎞️ Обёртка слайдера --}}
    <div
        class="swiper swiper-{{ $slideshow->id }} max-w-screen-xl mx-auto rounded-xl shadow-md overflow-hidden relative h-[120px] sm:h-[160px] md:h-[200px]">
        {{-- 🔁 Слайды --}}
        <div class="swiper-wrapper">
            @foreach ($slideshow->items->sortBy('order') as $item)
                <div class="swiper-slide relative group">
                    @if ($item->media_type === 'image')
                        {{-- 🖼️ Изображение --}}
                        <img src="{{ asset('storage/' . $item->file_path) }}" alt="{{ $item->caption ?? 'Слайд' }}"
                            class="w-full h-full object-cover transition-all duration-300">
                    @elseif ($item->media_type === 'video')
                        {{-- 🎥 Видео --}}
                        <video controls muted playsinline class="w-full h-full object-contain bg-black">
                            <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                            Ваш браузер не поддерживает видео.
                        </video>
                    @endif

                    {{-- 💬 Подпись/ссылка --}}
                    @if ($item->caption)
                        @php
                            $isLink = !empty($item->link);
                        @endphp

                        <div class="absolute bottom-5 right-5 z-20">
                            @if ($isLink)
                                <a href="{{ $item->link }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full shadow-md shadow-blue-300/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ $item->caption }}
                                </a>
                            @else
                                <span
                                    class="inline-block bg-blue-600 text-white text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full shadow-md shadow-blue-300/40 transition-all duration-200">
                                    {{ $item->caption }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- 🔘 Пагинация и стрелки --}}
        <div class="swiper-pagination !bottom-2"></div>
        <div class="swiper-button-prev text-white hover:scale-110 transition-transform"></div>
        <div class="swiper-button-next text-white hover:scale-110 transition-transform"></div>
    </div>

    {{-- 🚀 CMS нового поколения --}}
    <div class="flex justify-end mt-4">
        <span
            class="text-sm font-semibold px-3 py-1 rounded-full bg-blue-100 text-blue-700 shadow inline-flex items-center gap-2">
            ✨ CMS нового поколения
        </span>
    </div>
</div>

{{-- 🧩 Стили Swiper --}}
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .swiper-pagination-bullets {
            @apply flex justify-center gap-2 pb-2;
        }

        .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            border-radius: 9999px;
            background-color: rgba(255, 255, 255, 0.4);
            opacity: 1;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .swiper-pagination-bullet:hover {
            transform: scale(1.1);
            background-color: rgba(255, 255, 255, 0.7);
        }

        .swiper-pagination-bullet-active {
            background-color: #2563eb;
            transform: scale(1.3);
            box-shadow: 0 0 4px rgba(37, 99, 235, 0.5);
        }
    </style>
@endpush

{{-- ⚙️ Swiper Init --}}
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
                speed: 600,
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
