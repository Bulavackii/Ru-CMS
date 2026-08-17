{{--
    Выдвижное меню панели для экранов меньше lg (там сайдбар скрыт).

    26.07.2026: раньше здесь лежал СВОЙ захардкоженный список из пяти пунктов
    (Панель, Новости, Страницы, Категории, Файлы) — он давно разъехался с
    сайдбаром, и с телефона половина разделов была недоступна в принципе.
    Теперь источник тот же, что у сайдбара, шапки и глобального поиска:
    App\Support\AdminSections.
--}}
@php
    $mobileDashboard = \App\Support\AdminSections::dashboard();
    $mobileGroups    = \App\Support\AdminSections::groups();
    $mobileCounters  = \App\Support\AdminCounters::all();

    $mobileActive = function (array $link): bool {
        $active = $link['is_route']
            ? request()->routeIs($link['pattern'])
            : request()->is($link['pattern']);

        return $active || (isset($link['also']) && request()->is($link['also']));
    };

    $mobileDashboardActive = $mobileDashboard ? $mobileActive($mobileDashboard) : false;
@endphp

{{-- ⚠️ Своей кнопки открытия здесь БОЛЬШЕ НЕТ. Она была плавающей
     (`fixed top-4 left-4`) и попадала ровно под шапку панели, которая тоже
     прибита к верху: меню на телефоне и планшете не открывалось вообще
     никак. Кнопка переехала в саму шапку, а сюда приходит событие окна. --}}
{{-- ⚠️ ОТКРЫТИЕ И ЗАКРЫТИЕ — ОБЫЧНЫМ КОДОМ, БЕЗ ALPINE.

     Меню не открывалось: состояние честно становилось true, а панель
     оставалась скрытой. Замер показал причину — у панели и у корня оказались
     РАЗНЫЕ области данных (Alpine.$data(корень) !== Alpine.$data(панель)),
     то есть эффект показа следил за одним объектом, а событие меняло другой.
     Копать дальше смысла нет: здесь нужен один переключатель класса, и
     двадцать строк обычного кода делают это надёжнее и понятнее, чем
     реактивность, которую приходится отлаживать через внутренности
     фреймворка.

     Проверяемость: состояние — это класс `is-open` на самом узле, его видно
     в инспекторе и легко проверить замером. --}}
<div id="admin-drawer" class="lg:hidden">

    {{-- Затемнение --}}
    <div class="amb-veil" data-drawer-close aria-hidden="true"></div>

    {{-- Панель --}}
    {{-- ⚠️ `click.away` обязан ИСКЛЮЧАТЬ кнопку открытия, иначе меню не
         открывается вообще. Клик по кнопке в шапке делает две вещи сразу:
         шлёт событие «открыть» и, продолжая всплывать до документа, попадает
         в этот обработчик — тот честно считает клик «мимо панели» и закрывает
         её в тот же миг. Внешне это выглядит как «кнопка не работает»:
         состояние успевает стать true и тут же вернуться в false, ошибок в
         консоли нет. --}}
    <div class="amb-panel" role="dialog" aria-modal="true" aria-label="Меню панели">

        <div class="amb-accent" aria-hidden="true"></div>

        {{-- Заголовок --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2">
            {{-- Логотип ведёт на дашборд — так же, как в сайдбаре, отдельного
                 пункта под ним нет --}}
            <a href="{{ $mobileDashboard['url'] ?? url('/admin') }}"
               class="amb-brand flex items-center gap-2.5 min-w-0 {{ $mobileDashboardActive ? 'is-active' : '' }}"
               aria-current="{{ $mobileDashboardActive ? 'page' : 'false' }}">
                <span class="amb-logo" aria-hidden="true"><i class="fas fa-layer-group text-sm"></i></span>
                <span class="min-w-0 leading-tight">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white truncate">Nexum Core</span>
                    <span class="block text-xs text-gray-400 truncate">Панель управления</span>
                </span>
            </a>
            <button type="button" data-drawer-close class="amb-close text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 flex-shrink-0"
                    aria-label="Закрыть меню">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- ── Что переехало из шапки ─────────────────────────────
             На телефоне и планшете шапка разрасталась на три ряда: строка
             действий, лента из семи значков подряд и поиск. Значки без
             подписей стояли вплотную и читались сплошной полосой.

             Здесь у каждого действия есть ПОДПИСЬ, а места хватает. Убрать
             их из шапки, никуда не переложив, было нельзя: тогда с телефона
             они стали бы недостижимы — ровно так уже случилось с самим
             меню, кнопку которого перекрывала шапка. --}}
        <div class="amb-quick" aria-label="Быстрые действия">
            @if (module_enabled('Payments') && Route::has('admin.orders.index'))
                <a href="{{ route('admin.orders.index') }}" class="amb-quick__item">
                    <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                    <span>{{ __('admin.sections.orders') }}</span>
                </a>
            @endif

            @if (module_enabled('Messages') && Route::has('admin.messages.index'))
                <a href="{{ route('admin.messages.index') }}" class="amb-quick__item">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <span>{{ __('admin.header.messages') }}</span>
                </a>
            @endif

            @if (Route::has('admin.system_info'))
                <a href="{{ route('admin.system_info') }}" class="amb-quick__item">
                    <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
                    <span>{{ __('admin.header.tools_short') }}</span>
                </a>
            @endif

            @if (Route::has('admin.visual.themes.index'))
                <a href="{{ route('admin.visual.themes.index') }}" class="amb-quick__item">
                    <i class="fas fa-palette" aria-hidden="true"></i>
                    <span>{{ __('admin.sections.themes') }}</span>
                </a>
            @endif
        </div>

        {{-- Язык интерфейса. Их ровно два, поэтому список, а не выпадашка:
             на два варианта выпадающий список — лишний шаг. --}}
        @if (function_exists('available_locales') && Route::has('frontend.locale.set'))
            <div class="amb-langs" aria-label="{{ __('admin.header.language') }}">
                <span class="amb-langs__cap">{{ __('admin.header.language') }}</span>
                @foreach (available_locales() as $код)
                    <a href="{{ route('frontend.locale.set', $код) }}"
                       class="amb-lang {{ app()->getLocale() === $код ? 'is-active' : '' }}">{{ strtoupper($код) }}</a>
                @endforeach
            </div>
        @endif

        <nav class="p-3 space-y-3" aria-label="Основная навигация">
            @foreach ($mobileGroups as $title => $links)
                @continue(! count($links))
                <div class="{{ $loop->first ? '' : 'pt-3 border-t border-gray-200 dark:border-gray-800' }}">
                    <p class="amb-group">{{ $title }}</p>
                    <div class="space-y-0.5">
                        @foreach ($links as $link)
                            @php
                                $active = $mobileActive($link);
                                $count  = $link['counter'] ? ($mobileCounters[$link['counter']] ?? 0) : 0;
                            @endphp
                            <a href="{{ $link['url'] }}"
                               class="amb-item {{ $active ? 'is-active' : '' }}"
                               aria-current="{{ $active ? 'page' : 'false' }}">
                                @themeIcon($link['icon'], 'w-5 text-center flex-shrink-0')
                                <span class="truncate flex-1">{{ $link['label'] }}</span>
                                @if($count > 0)
                                    <span class="amb-count">{{ $count > 99 ? '99+' : $count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>

    <style>
        /* Те же переменные оформления, что у сайдбара — меню меняется вместе
           с выбранной темой панели. */
        .amb-toggle{color:var(--admin-on-primary,#fff);background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7))}
        .amb-accent{height:3px;background:linear-gradient(90deg,var(--admin-primary),var(--admin-accent),var(--admin-primary))}
        .amb-brand{text-decoration:none}
        .amb-brand.is-active .amb-logo{box-shadow:0 0 0 2px var(--admin-accent,#a855f7)}
        .amb-logo{display:grid;place-items:center;width:2rem;height:2rem;flex:none;color:var(--admin-on-primary,#fff);
            background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7))}
        /* Быстрые действия. Высота 44 — основное действие по стандарту
           Apple HIG.

           ⚠️ ОДНА колонка, а не две. В две подписи не помещались: панель 256
           пикселей, за вычетом полей и зазора на колонку остаётся 113, а
           «Служебные страницы» столько не занимают даже с усечением — элемент
           сетки не сжимается ниже содержимого (min-width:auto) и вылезал за
           край панели. Владелец прислал снимок с обрезанными подписями.
           min-width:0 добавлен по той же причине — без него усечение
           многоточием не включается вовсе. */
        .amb-quick{display:grid;grid-template-columns:1fr;gap:.35rem;padding:.6rem .75rem 0}
        .amb-quick__item{display:flex;align-items:center;gap:.45rem;min-height:44px;min-width:0;
            padding:.35rem .5rem;font-size:12px;font-weight:600;text-decoration:none;
            color:var(--surface-ink,#374151);background:var(--surface-2,#f8fafc);
            border:1px solid var(--surface-bd,#eef2f7)}
        .dark .amb-quick__item{color:#d1d5db;background:#111827;border-color:#1f2937}
        .amb-quick__item i{flex:none;color:var(--admin-primary,#6366f1)}
        .amb-quick__item span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

        /* ⚠️ В АЛЬБОМНОЙ ориентации телефона высота всего 414, и четыре
           действия по 44 плюс строка языка съедали её так, что до самих
           разделов приходилось прокручивать. Там же, где высоты мало, идём
           в две колонки — подписи для этого укорочены до названий разделов
           («Заказы», «Служебные», «Темы»), длинные в 113 пикселей не влезали
           и обрезались. */
        @media (max-height: 500px){
            .amb-quick{grid-template-columns:1fr 1fr}
            .amb-quick__item{min-height:36px}
        }

        /* ⚠️ Перенос обязателен на вырост. Сейчас языков ровно два и они
           влезают в строку, но их список берётся из папки переводов: заведут
           третий-четвёртый в «Локализации» — и без переноса ряд полез бы за
           край панели шириной 256. Чипы по 44 пикселя укладываются по четыре
           в ряд, подпись переезжает на свою строку. */
        .amb-langs{display:flex;flex-wrap:wrap;align-items:center;gap:.35rem;padding:.6rem .75rem 0}
                /* Подпись — отдельной строкой. В одну строку с чипами она занимает
           150 пикселей из 232, и второй язык уже переезжал на новую строку:
           получалось «RU» рядом с подписью и «EN» под ними. Своей строкой
           подпись отдаёт чипам всю ширину — их помещается по четыре, а
           раскладка не зависит от того, сколько языков заведут потом. */
        .amb-langs__cap{flex:0 0 100%;font-size:12px;font-weight:700;letter-spacing:.06em;
            text-transform:uppercase;color:var(--surface-dim,#9ca3af);white-space:nowrap;
            margin-bottom:.1rem}
        .amb-lang{display:inline-flex;align-items:center;justify-content:center;
            min-width:44px;min-height:32px;font-size:12px;font-weight:700;text-decoration:none;
            color:var(--surface-ink,#374151);border:1px solid var(--surface-bd,#e5e7eb)}
        .amb-lang.is-active{color:var(--admin-on-primary,#fff);background:var(--admin-primary,#6366f1);
            border-color:transparent}

        /* Состояние — один класс на корне. Панель и затемнение видны
           только при нём, поэтому «открыто ли меню» проверяется взглядом в
           инспектор, а не раскопками во внутренностях фреймворка. */
        /* ⚠️ Выше шапки. При z-index 50 панель уходила ПОД шапку панели, и
           её собственная верхушка — логотип, кнопка закрытия и первое
           быстрое действие — оказывалась перекрыта: меню открывалось, а
           закрыть его крестиком было нечем. Выдвижное меню на телефоне
           перекрывает всё, это его обычное поведение. */
        #admin-drawer .amb-veil{position:fixed;inset:0;z-index:999;background:rgba(0,0,0,.5);
            opacity:0;visibility:hidden;transition:opacity .2s ease,visibility .2s ease}
        #admin-drawer .amb-panel{position:fixed;left:0;top:0;z-index:1000;
            width:16rem;max-width:85vw;height:100%;overflow-y:auto;
            background:#fff;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);
            transform:translateX(-100%);transition:transform .22s ease}
        .dark #admin-drawer .amb-panel{background:#111827}
        #admin-drawer.is-open .amb-veil{opacity:1;visibility:visible}
        #admin-drawer.is-open .amb-panel{transform:translateX(0)}
        .amb-close{min-width:44px;min-height:44px;display:inline-flex;
            align-items:center;justify-content:center}

        /* Движение отключается по настройке «уменьшить анимацию». */
        @media (prefers-reduced-motion: reduce){
            #admin-drawer .amb-veil, #admin-drawer .amb-panel{transition:none}
        }

        .amb-group{padding:0 .6rem;margin-bottom:.25rem;font-size:12px;font-weight:700;
            letter-spacing:.06em;text-transform:uppercase;color:var(--surface-dim,#9ca3af)}
        .amb-item{display:flex;align-items:center;gap:.7rem;padding:.5rem .6rem;font-size:.875rem;
            color:var(--surface-ink,#374151);text-decoration:none;border-left:2px solid transparent;
            transition:background .15s ease,color .15s ease}
        .dark .amb-item{color:#d1d5db}
        .amb-item:hover{background:var(--admin-primary-soft,color-mix(in srgb, var(--admin-primary, #6366f1) 10%, transparent));
            border-left-color:var(--admin-primary,#6366f1)}
        .amb-item.is-active{color:var(--admin-on-primary,#fff);font-weight:600;border-left-color:var(--admin-accent,#a855f7);
            background:linear-gradient(90deg,var(--admin-primary,#6366f1),transparent 340%)}
        .amb-count{flex:none;min-width:1.25rem;padding:.05rem .3rem;text-align:center;font-size:.65rem;
            font-weight:700;color:var(--admin-on-primary,#fff);background:var(--admin-primary,#6366f1)}
        .amb-item.is-active .amb-count{color:var(--admin-primary,#4f46e5);background:var(--surface,#fff)}
    </style>
</div>

<script>
    /**
     * Выдвижное меню панели: открыть, закрыть, вернуть фокус.
     *
     * Обычный код вместо реактивности — см. пояснение у разметки выше.
     * Кнопка открытия живёт в шапке и шлёт событие окна: партиалы
     * подключаются независимо и знать друг о друге не обязаны.
     */
    (function () {
        var ящик = document.getElementById('admin-drawer');

        if (! ящик) {
            return;
        }

        var открыть = function () {
            ящик.classList.add('is-open');
            // Прокрутку страницы под меню останавливаем: иначе палец водит
            // список позади панели, а не саму панель.
            document.body.style.overflow = 'hidden';

            var первый = ящик.querySelector('.amb-panel a, .amb-panel button');
            if (первый) { первый.focus({ preventScroll: true }); }
        };

        var закрыть = function () {
            ящик.classList.remove('is-open');
            document.body.style.overflow = '';

            var кнопка = document.querySelector('.ahd-burger');
            if (кнопка) { кнопка.focus({ preventScroll: true }); }
        };

        window.addEventListener('admin-menu-open', открыть);

        ящик.addEventListener('click', function (е) {
            if (е.target.closest('[data-drawer-close]')) {
                закрыть();
            }
        });

        // Переход по пункту меню закрывает его сам: на телефоне страница
        // грузится не мгновенно, и открытая панель поверх новой страницы
        // выглядит как зависшая.
        ящик.addEventListener('click', function (е) {
            if (е.target.closest('.amb-item, .amb-quick__item, .amb-lang, .amb-brand')) {
                закрыть();
            }
        });

        document.addEventListener('keydown', function (е) {
            if (е.key === 'Escape' && ящик.classList.contains('is-open')) {
                закрыть();
            }
        });
    })();
</script>
