{{-- 🧩 Контейнер для всех слайдов --}}
<div id="slides-container" class="space-y-4">
    @foreach ($slideshow->slides ?? [] as $slide)
        @include('Slideshow::admin.components.slide-form', ['slide' => $slide])
    @endforeach
</div>

{{-- ➕ Кнопка добавления нового слайда --}}
<button type="button"
        class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold shadow-sm transition"
        onclick="addSlide()">
    <i class="fas fa-plus-circle"></i> Добавить слайд
</button>

@push('scripts')
<script>
    // 🔄 Асинхронная загрузка шаблона слайда
    function addSlide() {
        const container = document.getElementById('slides-container');

        fetch("{{ route('admin.slideshow.slide-template') }}")
            .then(response => response.text())
            .then(html => {
                container.insertAdjacentHTML('beforeend', html);
            })
            .catch(error => {
                console.error("Ошибка при загрузке шаблона слайда:", error);
            });
    }
</script>
@endpush
