<div class="w-full my-8">
    <div class="relative max-w-screen-xl mx-auto slideshow-container overflow-hidden rounded shadow" style="height: 500px;">

        {{-- Слайды --}}
        @foreach($slideshow->items->sortBy('order') as $index => $item)
            <div class="slide absolute inset-0 {{ $index === 0 ? '' : 'hidden' }}">
                @if ($item->media_type === 'image')
                    <img src="{{ asset('storage/' . $item->file_path) }}"
                         alt="{{ $item->caption ?? 'Слайд' }}"
                         class="w-full h-full object-cover rounded">
                @elseif ($item->media_type === 'video')
                    <video controls
                           class="w-full h-full rounded object-contain bg-black">
                        <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                        Ваш браузер не поддерживает видео.
                    </video>
                @endif
            </div>
        @endforeach

        {{-- Стрелки --}}
        <button class="prev absolute top-1/2 left-3 transform -translate-y-1/2 bg-black/50 text-white text-3xl px-3 py-2 rounded-full z-10 hover:bg-black/70">‹</button>
        <button class="next absolute top-1/2 right-3 transform -translate-y-1/2 bg-black/50 text-white text-3xl px-3 py-2 rounded-full z-10 hover:bg-black/70">›</button>

        {{-- Точки-индикаторы --}}
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-10">
            @foreach($slideshow->items as $i => $dot)
                <button class="dot w-3 h-3 rounded-full bg-white/60 hover:bg-white @if($i === 0) bg-white @endif" data-index="{{ $i }}"></button>
            @endforeach
        </div>
    </div>
</div>

<style>
    .slide {
        transition: opacity 0.5s ease-in-out;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slides = document.querySelectorAll('.slide');
        const prevBtn = document.querySelector('.prev');
        const nextBtn = document.querySelector('.next');
        const dots = document.querySelectorAll('.dot');
        let index = 0;
        let interval = null;

        function showSlide(i) {
            slides.forEach((slide, j) => {
                slide.classList.toggle('hidden', j !== i);
            });
            dots.forEach((dot, j) => {
                dot.classList.toggle('bg-white', j === i);
                dot.classList.toggle('bg-white/60', j !== i);
            });
            index = i;
        }

        function nextSlide() {
            showSlide((index + 1) % slides.length);
        }

        function prevSlide() {
            showSlide((index - 1 + slides.length) % slides.length);
        }

        prevBtn.addEventListener('click', prevSlide);
        nextBtn.addEventListener('click', nextSlide);
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                showSlide(parseInt(dot.dataset.index));
            });
        });

        if (slides.length > 1) {
            showSlide(index);
            interval = setInterval(nextSlide, 6000);
        }
    });
</script>
