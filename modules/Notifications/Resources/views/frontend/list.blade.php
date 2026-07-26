{{--
    🔔 Баннеры-уведомления для посетителя сайта.

    Подбор (аудитория, период показа, фильтр по странице) целиком выполнен в
    NotificationsComponent — здесь только отрисовка. Раньше вьюха фильтровала
    второй раз и по более грубому правилу: точное совпадение пути. Из-за этого
    маршруты со звёздочкой (/news/*) проходили компонент, но выбрасывались тут.
--}}
@if ($notifications->count())
    @php
        // Уведомления группируются по позиции: раньше позиция бралась у первого
        // в списке и применялась ко всем, поэтому «снизу» и «сверху» слипались.
        $groups = $notifications->groupBy(fn ($n) => $n->position ?: 'top');

        $boxStyles = [
            'top'        => 'top:40px; left:50%; transform:translateX(-50%);',
            'bottom'     => 'bottom:40px; left:50%; transform:translateX(-50%);',
            'fullscreen' => 'top:50%; left:50%; transform:translate(-50%,-50%);',
        ];
    @endphp

    @foreach ($groups as $position => $items)
        <div class="notif-stack" data-position="{{ $position }}"
             style="position:fixed; z-index:9999; {{ $boxStyles[$position] ?? $boxStyles['top'] }}
                    display:flex; flex-direction:column; gap:16px; align-items:center;
                    pointer-events:none; width:100%; max-width:100vw; padding:0 16px;">
            @foreach ($items as $n)
                <div class="notif-item"
                     data-duration="{{ (int) ($n->duration ?? 0) }}"
                     data-cookie="{{ $n->type === 'cookie' ? ($n->cookie_key ?: 'notif_' . $n->id) : '' }}"
                     style="pointer-events:auto; width:100%; max-width:min(720px, 92vw);
                            padding:18px 44px 18px 20px; position:relative;
                            background:{{ $n->bg_color ?: '#ffffff' }};
                            color:{{ $n->text_color ?: '#111827' }};
                            border:1px solid rgba(17,24,39,.1);
                            box-shadow:0 18px 40px -18px rgba(17,24,39,.45);">

                    <button class="notif-close" aria-label="Закрыть"
                            style="position:absolute; top:10px; right:12px; font-size:20px; line-height:1;
                                   background:transparent; border:0; color:inherit; cursor:pointer; opacity:.6;">&times;</button>

                    @if ($n->icon || $n->title)
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                            @if ($n->icon)
                                <span style="font-size:1.15rem; line-height:1;">
                                    @if (str_starts_with(trim($n->icon), 'fa'))
                                        <i class="{{ $n->icon }}"></i>
                                    @else
                                        {{ $n->icon }}
                                    @endif
                                </span>
                            @endif
                            @if ($n->title)
                                <strong style="font-size:.95rem; letter-spacing:-.01em;">{{ $n->title }}</strong>
                            @endif
                        </div>
                    @endif

                    <div class="notif-text" style="font-size:.88rem; line-height:1.55;">
                        {{-- Тип «текст» показывается как текст, «html»/«cookie» — с разметкой:
                             раньше поле type не влияло вообще ни на что --}}
                        @if ($n->type === 'text')
                            {{ strip_tags((string) $n->message) }}
                        @else
                            {!! $n->message !!}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <style>
        .notif-item a{ font-weight:600; color:#4f46e5; text-decoration:underline; }
        .notif-item a:hover{ color:#4338ca; }
        .notif-close:hover{ opacity:1; }
        /* Появление — чистой CSS-анимацией. Раньше видимость включал JS (класс
           is-visible поверх opacity:0), и если скрипт не успевал отработать,
           баннер молча оставался прозрачным — то есть невидимым. */
        .notif-item{ animation: notif-in .35s ease both; }
        @keyframes notif-in{ from{ opacity:0; transform:translateY(-6px); } to{ opacity:1; transform:translateY(0); } }
        .notif-item.is-hiding{ opacity:0; transform:translateY(-10px); transition:opacity .3s ease, transform .3s ease; }
    </style>

    <script>
        (function () {
            // Инициализируемся сразу, если разметка уже разобрана: ждать
            // DOMContentLoaded нельзя — событие может уже пройти к этому моменту
            const init = () => {
            document.querySelectorAll('.notif-item').forEach(box => {
                const duration = parseInt(box.dataset.duration, 10) || 0;
                const cookieKey = box.dataset.cookie;

                // Уже закрытое «одноразовое» уведомление не показываем совсем
                if (cookieKey && document.cookie.split('; ').some(c => c.startsWith(cookieKey + '='))) {
                    box.remove();
                    return;
                }

                const hide = () => {
                    if (cookieKey) {
                        document.cookie = `${cookieKey}=1; path=/; max-age=31536000; samesite=lax`;
                    }
                    box.classList.add('is-hiding');
                    setTimeout(() => box.remove(), 320);
                };

                box.querySelector('.notif-close')?.addEventListener('click', hide);

                if (duration > 0) {
                    setTimeout(hide, duration * 1000);
                }
            });

            // Пустые контейнеры не должны перехватывать клики по странице
            document.querySelectorAll('.notif-stack').forEach(stack => {
                const observer = new MutationObserver(() => {
                    if (!stack.querySelector('.notif-item')) stack.remove();
                });
                observer.observe(stack, { childList: true });
            });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endif
