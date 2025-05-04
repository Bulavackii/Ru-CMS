<div id="slides-container" class="space-y-4">
    @foreach ($slideshow->slides ?? [] as $slide)
        @include('Slideshow::admin.components.slide-form', ['slide' => $slide])
    @endforeach
</div>

<button type="button"
        class="mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
        onclick="addSlide()">
    ➕ Добавить слайд
</button>

@push('scripts')
<script>
    function addSlide() {
        const container = document.getElementById('slides-container');
        fetch("{{ route('admin.slideshow.slide-template') }}")
            .then(response => response.text())
            .then(html => {
                container.insertAdjacentHTML('beforeend', html);
            });
    }
</script>
@endpush
