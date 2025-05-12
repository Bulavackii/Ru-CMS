@if ($notifications->count())
    <div
        id="notification-container"
        class="fixed z-50 space-y-4 px-4 py-6 w-full
               @switch($notifications->first()->position)
                   @case('bottom') inset-x-0 bottom-0 @break
                   @case('fullscreen') inset-0 flex items-center justify-center @break
                   @default inset-x-0 top-4 @break
               @endswitch"
    >
        @foreach ($notifications as $n)
            <div
                class="relative p-5 rounded-lg shadow-xl transition-all duration-500 ease-in-out transform hover:scale-105 notification w-full max-w-xl mx-auto"
                style="
                    background-color: {{ $n->bg_color ?? '#cccaca' }};
                    color: {{ $n->text_color ?? '#000000' }};
                    opacity: {{ $n->opacity ?? 1 }};
                "
                data-duration="{{ $n->duration ?? 0 }}"
            >
                {{-- Кнопка закрытия --}}
                <button
                    class="absolute top-2 right-2 text-xl leading-none hover:scale-125 focus:outline-none close-btn"
                    aria-label="Закрыть"
                    style="color: {{ $n->text_color ?? '#000000' }};"
                >
                    &times;
                </button>

                {{-- Заголовок и иконка --}}
                <div class="flex items-center gap-2 mb-2">
                    @if ($n->icon)
                        <span class="text-2xl">{{ $n->icon }}</span>
                    @endif
                    @if ($n->title)
                        <h3 class="text-lg font-semibold">{{ $n->title }}</h3>
                    @endif
                </div>

                {{-- Контент --}}
                <div class="text-sm leading-relaxed break-words">
                    {!! $n->message !!}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Скрипт --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.notification').forEach(box => {
                const duration = parseInt(box.dataset.duration, 10);
                const closeBtn = box.querySelector('.close-btn');

                closeBtn?.addEventListener('click', () => {
                    box.classList.add('opacity-0');
                    setTimeout(() => box.remove(), 300);
                });

                if (duration > 0) {
                    setTimeout(() => {
                        box.classList.add('opacity-0');
                        setTimeout(() => box.remove(), 300);
                    }, duration * 1000);
                }
            });
        });
    </script>
@endif
