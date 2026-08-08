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

                    {{-- Крестик есть у всех уведомлений, включая согласие.
                         У согласия он не «промолчать»: закрытие засчитывается
                         как «только необходимые» — то есть отказ от лишнего.
                         Так решение остаётся однозначным, счётчики не
                         запускаются, а баннер не пристаёт снова и снова. --}}
                    <button type="button" class="notif-close"
                            aria-label="{{ __('frontend.consent.close') }}"
                            title="{{ __('frontend.consent.close') }}">
                        <svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false">
                            <path d="M2 2 L14 14 M14 2 L2 14" stroke="currentColor" stroke-width="2"
                                  stroke-linecap="round" fill="none"/>
                        </svg>
                    </button>

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

                    @if ($n->type === 'cookie')
                        {{-- Кнопки рисуются самим уведомлением, а не пишутся
                             в тексте: так согласие нельзя случайно испортить
                             правкой из панели, а обработчик всегда на месте. --}}
                        <div class="notif-consent">
                            <button type="button" class="notif-consent__yes" data-consent="1">
                                {{ __('frontend.consent.accept') }}
                            </button>
                            <button type="button" class="notif-consent__no" data-consent="0">
                                {{ __('frontend.consent.essential') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    <style>
        .notif-item a{ font-weight:600; color:#4f46e5; text-decoration:underline; }

        /* Согласие: кнопки в строку, на узком экране — друг под другом. */
        .notif-consent{ display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .notif-consent button{
            flex:1 1 auto; min-width:150px; padding:9px 16px; font-size:.85rem; font-weight:600;
            line-height:1.2; cursor:pointer; border:1px solid transparent; transition:filter .15s ease, background .15s ease;
        }
        .notif-consent__yes{ color:#fff; background:#4f46e5; }
        .notif-consent__yes:hover{ filter:brightness(1.1); }
        .notif-consent__no{ color:#374151; background:#fff; border-color:#d1d5db; }
        .notif-consent__no:hover{ background:#f3f4f6; }


        .notif-item a:hover{ color:#4338ca; }

        /* Крестик. Не голый символ, а кнопка с полем нажатия: у знака «×»
           шрифтом попасть в него трудно, особенно пальцем. Цвет наследуется
           от уведомления — крестик остаётся читаемым на любом фоне, который
           владелец выберет в панели. */
        .notif-close{
            position:absolute; top:10px; right:10px;
            display:inline-flex; align-items:center; justify-content:center;
            width:26px; height:26px; padding:0;
            color:inherit; background:transparent; border:0; cursor:pointer;
            opacity:.45; transition:opacity .15s ease, background .15s ease;
        }
        .notif-close:hover{ opacity:1; background:rgba(127,127,127,.14); }
        .notif-close:focus-visible{ outline:2px solid currentColor; outline-offset:2px; opacity:1; }
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
                const consent = box.querySelector('.notif-consent');

                // Уже отвеченное уведомление не показываем совсем
                if (cookieKey && document.cookie.split('; ').some(c => c.startsWith(cookieKey + '='))) {
                    box.remove();
                    return;
                }

                // Согласие живёт до закрытия браузера: cookie БЕЗ max-age
                // стирается вместе с сеансом. Обычные баннеры закрываются на
                // год — там это разумно, а решение о персональных данных
                // разумнее спрашивать заново.
                const remember = (value) => {
                    if (!cookieKey) return;
                    const life = consent ? '' : ' max-age=31536000;';
                    document.cookie = `${cookieKey}=${value}; path=/;${life} samesite=lax`;
                };

                const hide = (value) => {
                    remember(value === undefined ? 1 : value);
                    box.classList.add('is-hiding');
                    setTimeout(() => box.remove(), 320);
                };

                // У согласия закрытие крестиком — это отказ от лишнего, а не
                // «спросим потом»: молчание согласием не считается, а
                // переспрашивать на каждой странице — изводить посетителя.
                box.querySelector('.notif-close')?.addEventListener('click', () => {
                    hide(consent ? 0 : 1);

                    if (consent) {
                        document.dispatchEvent(new CustomEvent('ru:consent', { detail: { accepted: false } }));
                    }
                });

                if (consent) {
                    box.classList.add('is-consent');

                    consent.querySelectorAll('[data-consent]').forEach(button => {
                        button.addEventListener('click', () => {
                            const yes = button.dataset.consent === '1';

                            hide(yes ? 1 : 0);

                            // Счётчики запускаются ТОЛЬКО после согласия и сразу
                            // же, без перезагрузки: иначе первый просмотр — тот,
                            // ради которого человек и пришёл, — теряется.
                            if (yes && typeof window.ruStartAnalytics === 'function') {
                                window.ruStartAnalytics();
                            }

                            document.dispatchEvent(new CustomEvent('ru:consent', { detail: { accepted: yes } }));
                        });
                    });

                    // У согласия таймер не работает: молчание согласием не
                    // считается, ответ должен быть осознанным.
                    return;
                }

                if (duration > 0) {
                    setTimeout(() => hide(), duration * 1000);
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
