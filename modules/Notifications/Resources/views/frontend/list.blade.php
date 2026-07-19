@if ($notifications->count())
    @php
        $position = $notifications->first()->position ?? 'top';
        $verticalStyles = match ($position) {
            'fullscreen' => 'top: 50%; left: 50%; transform: translate(-50%, -50%);',
            'bottom' => 'bottom: 40px; left: 50%; transform: translateX(-50%);',
            default => 'top: 40px; left: 50%; transform: translateX(-50%);',
        };
        $arrowDirection = $position === 'bottom' ? 'up' : ($position === 'top' ? 'down' : null);
        $currentPath = request()->path();
    @endphp

    <div id="notification-container"
         style="position: fixed; z-index: 9999; {{ $verticalStyles }}
                display: flex; flex-direction: column; gap: 20px;
                align-items: center; pointer-events: none;
                width: 100%; max-width: 100vw; padding: 0 16px;">
        @foreach ($notifications as $n)
            @php
                $cookieKey = 'notif_' . $n->id;
                $filterPath = ltrim($n->route_filter ?? '', '/');
                $show = false;

                // 🔒 Строгая проверка маршрута
                $routeOk = $filterPath === '' || $currentPath === $filterPath;

                if ($routeOk) {
                    if ($n->target === 'all') {
                        $show = true;
                    } elseif (auth()->check()) {
                        $user = auth()->user();
                        if ($n->target === 'admin' && $user->is_admin) {
                            $show = true;
                        } elseif ($n->target === 'user' && !$user->is_admin) {
                            $show = true;
                        }
                    }
                }
            @endphp

            @if ($show)
                <div class="notification relative"
                     data-duration="{{ $n->duration ?? 0 }}"
                     data-cookie="{{ $n->type === 'cookie' ? $cookieKey : '' }}"
                     style="
                        pointer-events: all;
                        padding: 20px 28px;
                        width: 100%;
                        max-width: min(768px, 92vw);
                        backdrop-filter: blur(30px);
                        background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
                        background-color: {{ $n->bg_color ?? 'rgba(255,255,255,0.05)' }};
                        color: {{ $n->text_color ?? '#111827' }};
                        border-radius: 18px;
                        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.3);
                        border: 1px solid rgba(255, 255, 255, 0.15);
                        transition: all 0.3s ease;
                        position: relative;
                    ">

                    {{-- 🔽 Стрелка --}}
                    @if ($arrowDirection)
                        <div style="
                            position: absolute;
                            {{ $arrowDirection === 'up' ? 'bottom: -10px' : 'top: -10px' }};
                            left: 50%;
                            transform: translateX(-50%);
                            width: 0; height: 0;
                            border-left: 10px solid transparent;
                            border-right: 10px solid transparent;
                            {{ $arrowDirection === 'up'
                                ? 'border-top: 10px solid rgba(255, 255, 255, 0.15);'
                                : 'border-bottom: 10px solid rgba(255, 255, 255, 0.15);' }}
                        "></div>
                    @endif

                    {{-- ❌ Закрытие --}}
                    <button class="close-btn"
                            style="position: absolute; top: 12px; right: 16px;
                                   font-size: 20px; font-weight: bold;
                                   background: transparent; border: none;
                                   color: inherit; cursor: pointer;
                                   transition: transform 0.2s ease;"
                            aria-label="Закрыть">&times;</button>

                    {{-- 🔔 Заголовок --}}
                    @if ($n->icon || $n->title)
                        <div class="flex items-center gap-3 mb-2 text-base font-semibold text-gray-800 dark:text-white">
                            @if ($n->icon)
                                <span class="text-xl">{{ $n->icon }}</span>
                            @endif
                            @if ($n->title)
                                <h3 class="text-base font-bold uppercase tracking-wide text-blue-800 dark:text-blue-300 bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded shadow-sm">
                                    {{ $n->title }}
                                </h3>
                            @endif
                        </div>
                    @endif

                    {{-- 📃 Текст --}}
                    <div class="text-sm leading-relaxed text-gray-800 dark:text-gray-100 bg-white/10 dark:bg-black/10 p-3 rounded shadow-inner">
                        {!! $n->message !!}
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- 🎨 Стили --}}
    <style>
        #notification-container .notification a {
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 6px;
            background-color: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            text-decoration: none;
            transition: background 0.3s, color 0.3s;
        }

        #notification-container .notification a:hover {
            background-color: rgba(59, 130, 246, 0.2);
            color: #1d4ed8;
        }
    </style>

    {{-- 📜 JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const boxes = document.querySelectorAll('.notification');
            boxes.forEach(box => {
                const duration = parseInt(box.dataset.duration, 10);
                const cookieKey = box.dataset.cookie;
                const closeBtn = box.querySelector('.close-btn');

                if (cookieKey && document.cookie.includes(cookieKey + '=1')) {
                    box.remove();
                    return;
                }

                box.style.opacity = '0';
                box.style.transform = 'translateY(-5px) scale(0.98)';
                setTimeout(() => {
                    box.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    box.style.opacity = '1';
                    box.style.transform = 'translateY(0) scale(1)';
                }, 100);

                closeBtn?.addEventListener('click', () => {
                    if (cookieKey) {
                        document.cookie = `${cookieKey}=1; path=/; max-age=31536000`;
                    }
                    box.style.opacity = '0';
                    box.style.transform = 'translateY(-10px) scale(0.95)';
                    setTimeout(() => box.remove(), 300);
                });

                if (duration > 0) {
                    setTimeout(() => {
                        if (cookieKey) {
                            document.cookie = `${cookieKey}=1; path=/; max-age=31536000`;
                        }
                        box.style.opacity = '0';
                        box.style.transform = 'translateY(-10px) scale(0.95)';
                        setTimeout(() => box.remove(), 300);
                    }, duration * 1000);
                }
            });
        });
    </script>
@endif
