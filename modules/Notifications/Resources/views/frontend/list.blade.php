@if ($notifications->count())
    <div class="fixed inset-x-0 top-0 z-50 space-y-4 p-4" id="notification-container">
        @foreach ($notifications as $n)
            <div class="relative p-4 rounded shadow text-white @if($n->color) bg-[{{ $n->color }}] @else bg-blue-600 @endif notification"
                 data-duration="{{ $n->duration ?? 0 }}"
                 style="max-width: 700px; margin: 0 auto;">
                {{-- Закрыть --}}
                <button class="absolute top-2 right-2 text-white text-xl leading-none focus:outline-none close-btn" aria-label="Закрыть">
                    &times;
                </button>

                {{-- Иконка и заголовок --}}
                <div class="flex items-center mb-2 space-x-2">
                    @if($n->icon)
                        <span class="text-2xl">{{ $n->icon }}</span>
                    @endif
                    @if($n->title)
                        <h3 class="text-lg font-semibold">{{ $n->title }}</h3>
                    @endif
                </div>

                {{-- Содержимое --}}
                <div class="text-sm">
                    {!! $note->message !!}
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.notification').forEach(function (box) {
                const closeBtn = box.querySelector('.close-btn');
                closeBtn.addEventListener('click', () => box.remove());

                const duration = parseInt(box.dataset.duration);
                if (duration > 0) {
                    setTimeout(() => box.remove(), duration * 1000);
                }
            });
        });
    </script>
@endif
