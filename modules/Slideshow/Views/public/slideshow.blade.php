<div class="w-full my-8">
    <div class="swiper swiper-{{ $slideshow->id }} max-w-screen-xl mx-auto rounded shadow overflow-hidden" style="height: 500px;">
        <div class="swiper-wrapper">
            @foreach($slideshow->items->sortBy('order') as $item)
                <div class="swiper-slide">
                    @if ($item->media_type === 'image')
                        <img src="{{ asset('storage/' . $item->file_path) }}"
                             alt="{{ $item->caption ?? 'Слайд' }}"
                             class="w-full h-full object-cover rounded">
                    @elseif ($item->media_type === 'video')
                        <video controls class="w-full h-full object-contain bg-black rounded">
                            <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                            Ваш браузер не поддерживает видео.
                        </video>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Навигация и пагинация --}}
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev text-white"></div>
        <div class="swiper-button-next text-white"></div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Swiper('.swiper-{{ $slideshow->id }}', {
                loop: true,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                },
                speed: 800,
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
