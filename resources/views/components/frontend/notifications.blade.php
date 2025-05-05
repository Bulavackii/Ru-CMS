@if ($notifications->count())
    @php
        $userType = auth()->check() ? (auth()->user()->is_admin ? 'admin' : 'user') : 'guest';
    @endphp

    @foreach ($notifications as $note)
        @php
            $audienceAllowed =
                $note->target === 'all' ||
                ($note->target === 'admin' && $userType === 'admin') ||
                ($note->target === 'user' && $userType === 'user');

            $shouldShow = true;

            // 🔍 Фильтрация по маршруту/URL
            if ($note->route_filter) {
                $shouldShow = request()->is($note->route_filter) || url()->current() === $note->route_filter;
            }

            // 🍪 Проверка cookie (если тип = cookie)
            if ($note->type === 'cookie' && $note->cookie_key && request()->cookie($note->cookie_key)) {
                $shouldShow = false;
            }
        @endphp

        @if ($audienceAllowed && $shouldShow)
            <div <div
                class="notification fixed {{ $note->position === 'top' ? 'top-0' : ($note->position === 'bottom' ? 'bottom-0' : 'inset-0') }} left-0 w-full z-50 flex items-center justify-center px-4 py-3"
                style="background-color: {{ $note->bg_color ?? '#ebebeb' }}; color: {{ $note->text_color ?? '#000000' }};"
                data-id="{{ $note->id }}" data-type="{{ $note->type }}" data-cookie="{{ $note->cookie_key }}"
                data-timeout="{{ $note->duration ?? 0 }}">
                <div class="max-w-4xl w-full flex items-center justify-between gap-4 relative rounded shadow px-6 py-4">
                    @if ($note->icon)
                        <div class="text-2xl mr-2">{!! $note->icon !!}</div>
                    @endif

                    <div class="flex-1">
                        @if ($note->title)
                            <div class="font-bold mb-1 text-lg">{{ $note->title }}</div>
                        @endif

                        {!! $note->message !!}
                    </div>

                    <button
                        class="close-btn ml-4 px-3 py-1 text-xl font-bold border border-white rounded-full hover:bg-white hover:text-black transition"
                        aria-label="Закрыть">
                        ×
                    </button>

                </div>
            </div>
        @endif
    @endforeach

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.notification').forEach(box => {
                const id = box.dataset.id;
                const closeBtn = box.querySelector('.close-btn');
                const timeout = parseInt(box.dataset.timeout);
                const cookieKey = box.dataset.cookie;
                const type = box.dataset.type;

                // ✅ Проверка: если уже закрыто (по ID), удалить элемент
                if (sessionStorage.getItem('notification_closed_' + id)) {
                    box.remove();
                    return;
                }

                // 🔘 Закрытие вручную
                closeBtn?.addEventListener('click', () => {
                    box.remove();
                    sessionStorage.setItem('notification_closed_' + id, '1');
                    if (type === 'cookie' && cookieKey) {
                        document.cookie = `${cookieKey}=1; path=/; max-age=31536000`;
                    }
                });

                // ⏱ Автоматическое скрытие
                if (timeout > 0) {
                    setTimeout(() => {
                        box.remove();
                        sessionStorage.setItem('notification_closed_' + id, '1');
                    }, timeout * 1000);
                }
            });
        });
    </script>
@endpush
@endif
