{{-- 🧩 Зона фрагмента: собственный блок над подвалом сайта.
     Ничего не выводится, если фрагмента нет, он выключен или пуст. --}}
@php $fragmentFooter = \Modules\Visual\Support\FragmentRenderer::zone('frontend.footer'); @endphp
@if($fragmentFooter)
    <div class="fragment-zone fragment-zone--footer">{!! $fragmentFooter !!}</div>
@endif

<footer class="relative text-sm mt-6" style="color:var(--color-text,#6b7280)">
    {{-- Фон сайта проступает сквозь подвал. Слой рисуется ПОВЕРХ заливки
         (см. .f-body::after), иначе непрозрачная заливка гасила узор —
         ровно та же история, что была в шапке. --}}

    {{-- Переход «контент → подвал»: заливка не начинается резко, а
         проявляется на первых 90 пикселях. Сквозь них виден узор страницы,
         поэтому видимой границы не остаётся. --}}
    <div class="relative z-10 backdrop-blur-md transition-colors duration-200 f-body"
        style="--f-color: var(--color-footer, #ffffff)">


        {{-- ===== Колонки подвала. Число столбцов авто-подстраивается: слева
             «Разработчик», справа «Контакты», между ними — по одному столбцу на
             каждое footer-меню (одно меню = один столбец). Сетку и include
             модуля Меню НЕ трогаем. ===== --}}
        <style>
            /* Три смысловых блока: бренд, группа меню, контакты. Раньше
               каждый столбец меню был отдельной ячейкой наравне с ними, и
               ширина делилась поровну — при двух меню получались четыре
               колонки по 316px, а короткие списки ссылок терялись в этой
               ширине. Теперь меню занимают одну ячейку и делят её между
               собой: два столбца стоят рядом, три помещаются так же. */
            .footer-grid{ display:grid; grid-template-columns:1fr; }

            .footer-menus{ display:grid; grid-template-columns:1fr; gap:2rem 1.75rem; }

            @media (min-width:768px){
                .footer-grid{ grid-template-columns:1fr 1fr; }
                /* Столбцы меню держатся рядом: шаг внутри группы заметно
                   меньше, чем между блоками подвала. */
                .footer-menus{ grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); }

                /* На планшете группа занимает всю ширину сетки: зажатая в
                   половину, она ломала три столбца на две строки (замер на
                   820px — 2+1). Во всю ширину они снова встают в ряд. */
                .footer-menus{ grid-column:1 / -1; }
            }

            @media (min-width:1024px){
                /* Бренду и контактам нужно больше места: там адрес, почта и
                   телефон. Меню — списки коротких ссылок, им хватает меньше. */
                .footer-grid{ grid-template-columns:minmax(230px,1fr) minmax(0,1.5fr) minmax(230px,1fr); }
                /* На широком экране группа снова в своей ячейке, между
                   брендом и контактами. */
                .footer-menus{ grid-column:auto; }
            }
        </style>
        <div class="footer-grid max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-9 sm:py-11 md:py-12 gap-8 sm:gap-10 md:gap-12">

            {{-- 1) Бренд / разработчик — карточка --}}
            <section aria-labelledby="dev-info" class="footer-brand">
                <div class="f-brand-card">
                    <div class="flex items-center gap-2.5 mb-2">
                        <span class="f-brand-badge">RU</span>
                        <div class="min-w-0">
                            <h2 id="dev-info" class="text-base font-bold leading-tight text-gray-900 dark:text-white">RU CMS</h2>
                            <div class="text-[11px] leading-tight text-gray-500 dark:text-gray-400">{{ __('frontend.common.cms_tagline') }} · Laravel {{ app()->version() }}</div>
                        </div>
                    </div>

                    {{-- Почта и телефон отсюда убраны: ровно те же значения
                         стоят в колонке «Контакты» справа, и посетитель видел
                         один и тот же адрес дважды в одной полосе. Здесь
                         остаётся то, чего больше нигде нет — кто сделал,
                         на чём собрано и где взять исходники. --}}
                    <p class="f-brand-about">{{ __('frontend.footer.about_line') }}</p>

                    <ul class="f-brand-facts">
                        <li>
                            <span class="f-ico">@themeIcon('user')</span>
                            <span><b>Булавацкий Д.О.</b> — {{ __('frontend.footer.developer') }}</span>
                        </li>
                        <li>
                            <span class="f-ico">@themeIcon('calendar')</span>
                            <span>{{ __('frontend.footer.since') }} {{ date('Y') }}</span>
                        </li>
                    </ul>

                    {{-- Чипы: версия системы и лицензия. Ровно та пара сведений,
                         которую ищут, прежде чем брать проект в работу. --}}
                    <div class="f-brand-chips">
                        <span class="f-brand-chip">v{{ config('app.version', '1.0.0') }}</span>
                        <span class="f-brand-chip">MIT</span>
                        <a href="https://github.com/#" target="_blank" rel="noopener"
                           class="f-brand-chip f-brand-chip--link">
                            <x-icon.github :size="12" /> {{ __('frontend.footer.sources') }}
                        </a>
                    </div>
                </div>
            </section>

            {{-- 2) Столбцы из меню позиции footer (одно меню = один столбец).
                 Партиал модуля Меню — НЕ ТРОГАЕМ. Fallback на прежние ссылки внутри. --}}
            @include('Menu::frontend.footer')

            {{-- 3) Контакты и соцсети --}}
            <section aria-labelledby="footer-links" class="footer-contacts text-center md:text-left">
                {{-- Тот же стиль, что у заголовков колонок меню (footer-col-title
                     в Menu::frontend.footer): раньше «Контакты» выбивались из ряда
                     мелким uppercase-шрифтом. --}}
                <h2 id="footer-links" class="footer-col-title text-base font-semibold mb-4 text-center md:text-left text-gray-900 dark:text-gray-100">
                    {{ __('frontend.footer.contacts') }}
                </h2>

                {{-- Обезличенные тестовые сведения.

                     Раньше каждая строка была рамкой с заливкой во всю ширину и
                     читалась как отключённое поле ввода, а не как ссылка. Теперь
                     это обычные строки: значок в плитке, над значением — подпись,
                     что это за контакт. --}}
                <ul class="f-contacts">
                    <li>
                        <a href="mailto:info@example.com" class="f-contact">
                            <span class="f-contact__ico">@themeIcon('mail')</span>
                            <span class="f-contact__body">
                                <span class="f-contact__label">{{ __('frontend.footer.write') }}</span>
                                <span class="f-contact__value">info@example.com</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="tel:+79001234567" class="f-contact">
                            <span class="f-contact__ico">@themeIcon('phone')</span>
                            <span class="f-contact__body">
                                <span class="f-contact__label">{{ __('frontend.footer.call') }}</span>
                                <span class="f-contact__value">+7 (900) 123-45-67</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="https://yandex.ru/maps/?text={{ urlencode('Москва, Тверская улица, 7') }}"
                           target="_blank" rel="noopener" class="f-contact">
                            <span class="f-contact__ico">@themeIcon('map')</span>
                            <span class="f-contact__body">
                                <span class="f-contact__label">{{ __('frontend.footer.address') }}</span>
                                <span class="f-contact__value">г. Москва, Тверская улица, 7</span>
                            </span>
                            {{-- Значок внешней ссылки только там, где уход со
                                 сайта действительно происходит. --}}
                            <span class="f-contact__ext" aria-hidden="true">@themeIcon('arrow-up-right-from-square')</span>
                        </a>
                    </li>
                </ul>


            </section>
        </div>

        {{-- ===== Нижняя мета-полоса ===== --}}
        {{-- Полоса намеренно узкая: в ней одна строка текста, и прежние
             отступы сверху и снизу делали её выше самой надписи втрое. --}}
        <div class="border-t border-gray-200/80 dark:border-gray-700/80 px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-1.5 backdrop-blur-sm bg-white dark:bg-gray-800 transition-colors duration-200"
            style="background:var(--color-footer,#ffffff)">
            {{-- Копирайт со стеком — по центру полосы.

                 Форма «Напишите нам» отсюда убрана по просьбе владельца: поле
                 и кнопка занимали половину полосы, а вели всего лишь в
                 почтовый клиент через mailto. Адрес для связи и так есть выше,
                 в колонке контактов. --}}
            {{-- Соцсети переехали сюда из колонки контактов: к контактам они
                 отношения не имеют, а высоту подвала съедали заметно —
                 подпись, отступ и ряд плиток. В узкой полосе они занимают
                 ту же строку, что копирайт. --}}
            <div class="max-w-screen-2xl mx-auto f-meta-row text-xs text-gray-500 dark:text-gray-400">
                {{-- Адреса приходят из social_links() (app/helpers.php) — один
                     список на подвал сайта и подвал панели. Записанные в обоих
                     шаблонах, они уже разошлись: здесь стоял vk.com/example,
                     в панели vk.com/ru_cms. --}}
                @if($socialLinks = social_links())
                    <div class="f-socials">
                        <span class="f-social-label">{{ __('frontend.footer.socials') }}</span>
                        @foreach($socialLinks as $social)
                            <a href="{{ $social['href'] }}" target="_blank" rel="noopener"
                               class="f-social f-social--plain" style="--c:{{ $social['color'] }}"
                               title="{{ $social['label'] }}" aria-label="{{ $social['label'] }}">
                                @switch($social['key'])
                                    @case('vk')     <x-icon.vk :size="17" /> @break
                                    @case('max')    <x-icon.max :size="17" /> @break
                                    @case('rutube') <x-icon.rutube :size="17" /> @break
                                    @case('github') <x-icon.github :size="17" /> @break
                                @endswitch
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="f-meta-copy">
                    <span>© {{ date('Y') }} <b class="text-gray-700 dark:text-gray-300 font-medium">RU CMS</b> — {{ __('frontend.footer.rights') }}</span>
                    <span class="f-meta-chip">PHP {{ PHP_VERSION }}</span>
                    <span class="f-meta-chip">Laravel {{ app()->version() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Наверх --}}
    <button id="backToTopBtn"
        class="fixed bottom-6 right-6 z-50 p-3 shadow-md transition transform hover:scale-105 opacity-0 pointer-events-none text-white"
        style="background:var(--fx-grad,#6366f1); box-shadow:0 10px 24px -8px rgba(99,102,241,.6)"
        title="{{ __('frontend.footer.to_top') }}" aria-label="{{ __('frontend.footer.to_top') }}">
        @themeIcon('arrow-up')
    </button>
</footer>

<script>
    (function() {
        const btn = document.getElementById('backToTopBtn');
        const toggle = (show) => {
            btn.classList.toggle('opacity-100', show);
            btn.classList.toggle('opacity-0', !show);
            btn.classList.toggle('pointer-events-auto', show);
            btn.classList.toggle('pointer-events-none', !show);
        };
        btn.addEventListener('click', () => {
            const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        });
        const onScroll = () => toggle((window.scrollY || document.documentElement.scrollTop) > 300);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();
</script>

<style>
    .list-none{ list-style:none; }
    #backToTopBtn{ transition:opacity .2s ease, transform .2s ease; }
    @media (prefers-reduced-motion: reduce){ #backToTopBtn{ transition:none; } }

    /* Переход «контент → подвал». Отдельной полосы-шва нет: заливка
       подвала сама проявляется сверху вниз, и узор страницы просвечивает
       сквозь неё. Первые 90px — плавный набор непрозрачности. */
    .f-body{
        position:relative;
        /* Заливка полупрозрачная — как стекло шапки, чтобы фон сайта
           читался сквозь подвал. */
        background:linear-gradient(to bottom,
            transparent 0,
            color-mix(in srgb, var(--f-color) 35%, transparent) 45px,
            color-mix(in srgb, var(--f-color) 62%, transparent) 90px);
        padding-top:1.5rem;
        -webkit-backdrop-filter:blur(18px) saturate(180%);
        backdrop-filter:blur(18px) saturate(180%);
        border-top:1px solid rgba(255,255,255,.5);
    }

    /* Фоновая картинка сайта поверх заливки — та же схема, что в шапке.
       pointer-events:none, иначе слой перехватывал бы клики по ссылкам. */
    .f-body::after{
        content:''; position:absolute; inset:0; z-index:0;
        background-image:var(--bg-image); background-repeat:repeat; background-size:auto;
        opacity:.85; pointer-events:none;
    }

    /* Содержимое подвала — над подложкой. */
    .f-body > *{ position:relative; z-index:1; }
    /* Запасной вариант для браузеров без color-mix: переход всё равно
       плавный, просто без промежуточной ступени. */
    @supports not (color: color-mix(in srgb, red 50%, blue)){
        .f-body{ background:linear-gradient(to bottom, transparent 0, var(--f-color) 90px); }
        /* Без color-mix заливка непрозрачна — узор поверх неё делаем мягче,
           иначе подвал становится пёстрым. */
        .f-body::after{ opacity:.35; }
    }

    /* ===== Оформление подвала (стиль проекта, акцент из ТЕМЫ) ===== */
    .f-brand-badge{ width:2.4rem; height:2.4rem; display:inline-flex; align-items:center; justify-content:center;
        background:var(--fx-grad,#6366f1); color:#fff; font-weight:700; font-size:.85rem; letter-spacing:.02em;
        flex:0 0 auto; box-shadow:0 8px 18px -8px rgba(99,102,241,.6); }
    .f-brand-card{ padding:.75rem .85rem; border:1px solid rgba(17,24,39,.08); background:rgba(255,255,255,.5);
        -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px); }
    .f-devlist--tight li{ padding:.08rem 0; font-size:.78rem; }
    .f-devlist--tight .f-ico svg, .f-devlist--tight .f-ico i{ width:.9rem; height:.9rem; font-size:.9rem; }
    :root.dark .f-brand-card{ border-color:rgba(255,255,255,.08); background:rgba(30,41,59,.4); }
    .f-heading{ font-size:.78rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase;
        color:#6b7280; margin-bottom:1rem; }
    :root.dark .f-heading{ color:#9ca3af; }

    .f-devlist{ list-style:none; margin:0; padding:0; }
    .f-devlist li{ display:flex; align-items:center; gap:.55rem; font-size:.82rem; padding:.22rem 0; color:#6b7280; }
    :root.dark .f-devlist li{ color:#9ca3af; }
    .f-ico{ display:inline-flex; color:var(--color-primary,#6366f1); flex:0 0 auto; }
    .f-ico svg, .f-ico i{ width:1rem; height:1rem; font-size:1rem; line-height:1; }

    /* Контакты в подвале.
       Рамка с заливкой во всю ширину делала из ссылки отключённое поле
       ввода — теперь это строка со значком, подписью и значением. */
    /* Столбцы меню.
       Значок в плитке и подчёркивание значения — те же, что у контактов
       в соседней колонке: раньше ссылки меню подсвечивались собственной
       подложкой и выбивались из общего ряда. Число столбцов на вид не
       влияет: их рисует партиал модуля Меню по числу footer-меню. */
    .footer-col-title{ margin:0 0 .9rem; font-size:.95rem; font-weight:700;
        color:#111827; }
    :root.dark .footer-col-title{ color:#f3f4f6; }

    .f-menu-list{ display:grid; gap:.15rem; margin:0; padding:0; list-style:none; }
    .f-menu-list li{ margin:0; }

    .f-menu-link{ display:flex; align-items:center; gap:.65rem; padding:.42rem .3rem;
        text-decoration:none; color:inherit; transition:color .15s ease; }

    .f-menu-ico{ display:inline-flex; align-items:center; justify-content:center;
        width:1.85rem; height:1.85rem; flex:0 0 auto; color:var(--color-primary,#6366f1);
        background:rgba(99,102,241,.1);
        transition:color .15s ease, background .15s ease; }
    .f-menu-ico svg, .f-menu-ico i{ width:.9rem; height:.9rem; font-size:.9rem; line-height:1; }

    .f-menu-text{ position:relative; font-size:.85rem; color:#374151;
        transition:color .15s ease; }
    .f-menu-text::after{ content:''; position:absolute; left:0; right:0; bottom:-2px;
        height:1px; background:var(--color-primary,#6366f1);
        transform:scaleX(0); transform-origin:left; transition:transform .2s ease; }
    .f-menu-link:hover .f-menu-text{ color:var(--color-primary,#6366f1); }
    .f-menu-link:hover .f-menu-text::after{ transform:scaleX(1); }
    .f-menu-link:hover .f-menu-ico{ color:#fff; background:var(--color-primary,#6366f1); }
    .f-menu-link:focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px; }
    :root.dark .f-menu-text{ color:#d1d5db; }
    :root.dark .f-menu-ico{ background:rgba(99,102,241,.2); }

    /* Вложенный уровень — мельче и со сдвигом, чтобы читалась подчинённость. */
    .f-menu-list--child{ margin:.1rem 0 .25rem 1.1rem; }
    .f-menu-link--child .f-menu-ico{ width:1.4rem; height:1.4rem; }
    .f-menu-link--child .f-menu-ico svg, .f-menu-link--child .f-menu-ico i{
        width:.72rem; height:.72rem; font-size:.72rem; }
    .f-menu-link--child .f-menu-text{ font-size:.78rem; }

    /* На телефоне столбцы выравниваются по центру, как остальные блоки. */
    @media (max-width:767px){
        .footer-col{ text-align:center; }
        .f-menu-link{ justify-content:center; }
        .f-menu-list--child{ margin-left:0; }
    }

    /* Блок разработчика.
       Раньше он повторял почту и телефон из колонки «Контакты» и занимал
       высоту втрое больше пользы. Теперь несёт то, чего нет рядом:
       строку о проекте, автора, год и чипы «версия / лицензия / исходники». */
    .f-brand-about{ margin:.1rem 0 .7rem; font-size:.78rem; line-height:1.5; color:#6b7280; }
    :root.dark .f-brand-about{ color:#9ca3af; }

    .f-brand-facts{ display:grid; gap:.4rem; margin:0 0 .8rem; padding:0; list-style:none;
        font-size:.8rem; }
    .f-brand-facts li{ display:flex; align-items:center; gap:.5rem; margin:0; }
    .f-brand-facts b{ font-weight:600; color:#374151; }
    :root.dark .f-brand-facts b{ color:#d1d5db; }

    .f-brand-chips{ display:flex; flex-wrap:wrap; gap:.35rem; }
    .f-brand-chip{ display:inline-flex; align-items:center; gap:.3rem;
        padding:.22rem .5rem; font-size:.66rem; font-weight:700; letter-spacing:.03em;
        color:#4338ca; background:rgba(99,102,241,.1); text-decoration:none;
        transition:background .15s ease, color .15s ease; }
    .f-brand-chip--link:hover{ color:#fff; background:var(--color-primary,#6366f1); }
    :root.dark .f-brand-chip{ color:#c7d2fe; background:rgba(99,102,241,.2); }

    .f-contacts{ display:grid; gap:.15rem; margin:0; padding:0; list-style:none; }
    .f-contacts li{ margin:0; }

    .f-contact{ display:flex; align-items:center; gap:.65rem; padding:.45rem .3rem;
        text-decoration:none; color:inherit; transition:color .15s ease; }

    /* Значок в плитке цвета темы: он же связывает блок с карточками
       контактов на самой странице «Контакты». */
    .f-contact__ico{ display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; flex:0 0 auto; color:var(--color-primary,#6366f1);
        background:rgba(99,102,241,.1);
        transition:color .15s ease, background .15s ease; }
    .f-contact__ico svg, .f-contact__ico i{ width:.95rem; height:.95rem; font-size:.95rem; }

    .f-contact__body{ display:flex; flex-direction:column; min-width:0; line-height:1.25; }
    .f-contact__label{ font-size:.62rem; font-weight:700; letter-spacing:.1em;
        text-transform:uppercase; color:#9ca3af; }
    .f-contact__value{ font-size:.85rem; font-weight:500; color:#374151;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        transition:color .15s ease; }

    /* Подчёркивание, как у пунктов меню рядом: блок перестаёт быть
       чужеродным в ряду колонок подвала. */
    .f-contact__value{ position:relative; }
    .f-contact__value::after{ content:''; position:absolute; left:0; right:0; bottom:-2px;
        height:1px; background:var(--color-primary,#6366f1);
        transform:scaleX(0); transform-origin:left; transition:transform .2s ease; }
    .f-contact:hover .f-contact__value{ color:var(--color-primary,#6366f1); }
    .f-contact:hover .f-contact__value::after{ transform:scaleX(1); }
    .f-contact:hover .f-contact__ico{ color:#fff; background:var(--color-primary,#6366f1); }
    .f-contact:focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px; }

    .f-contact__ext{ margin-left:auto; flex:0 0 auto; opacity:.35; transition:opacity .15s ease; }
    .f-contact__ext svg, .f-contact__ext i{ width:.7rem; height:.7rem; font-size:.7rem; }
    .f-contact:hover .f-contact__ext{ opacity:.75; }

    :root.dark .f-contact__value{ color:#d1d5db; }
    :root.dark .f-contact__label{ color:#6b7280; }
    :root.dark .f-contact__ico{ background:rgba(99,102,241,.2); }

    /* Соцсети.
       Значки стояли голым рядом без подписи и без опоры — читались как
       набор случайных кнопок. Теперь над ними подпись в том же стиле, что
       у подписей контактов, а сами они лежат в плитках такого же размера,
       как значки контактов и меню: подвал перестал распадаться на три
       разных языка оформления. */
    /* Нижняя полоса: соцсети слева, копирайт справа. На узком экране
       обе группы встают по центру друг под другом. */
    .f-meta-row{ display:flex; align-items:center; justify-content:space-between;
        gap:.75rem 1rem; flex-wrap:wrap; }
    .f-meta-copy{ display:flex; align-items:center; flex-wrap:wrap;
        gap:.4rem .6rem; }
    @media (max-width:767px){
        .f-meta-row{ justify-content:center; }
        .f-meta-copy{ justify-content:center; width:100%; }
        .f-socials{ justify-content:center; width:100%; }
    }

    /* Подпись стоит в строку со значками, а не над ними: в узкой полосе
       второй строке места нет. */
    .f-social-label{ margin:0 .15rem 0 0; font-size:.6rem; font-weight:700;
        letter-spacing:.1em; text-transform:uppercase; color:#9ca3af; }
    :root.dark .f-social-label{ color:#6b7280; }

    .f-socials{ display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }

    .f-social{ display:inline-flex; align-items:center; justify-content:center;
        width:1.6rem; height:1.6rem; flex:0 0 auto; font-size:.9rem; text-decoration:none;
        color:#6b7280; background:rgba(99,102,241,.08);
        transition:color .15s ease, background .15s ease, transform .15s ease; }
    :root.dark .f-social{ color:#9ca3af; background:rgba(99,102,241,.18); }

    /* Плитка заливается ФИРМЕННЫМ цветом сети, а не акцентом темы: так
       узнаваемость знака работает на сайте с любым оформлением. */
    .f-social:hover{ color:#fff; background:var(--c,#6366f1); transform:translateY(-2px); }
    .f-social:focus-visible{ outline:2px solid var(--c,#6366f1); outline-offset:2px; }

    /* У ВК, MAX, Rutube и GitHub собственные цветные глифы: заливать
       плитку под ними нельзя, знак потеряется. Им — только подъём и
       мягкая подложка фирменного цвета. */
    .f-social--plain{ background:rgba(99,102,241,.08); }
    .f-social--plain:hover{ background:rgba(99,102,241,.16); }
    :root.dark .f-social--plain:hover{ background:rgba(99,102,241,.28); }
    .f-social--plain svg{ transition:transform .15s ease; }
    .f-social--plain:hover svg{ transform:scale(1.08); }

    .f-meta-chip{ display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .5rem; font-size:.66rem; font-weight:600;
        background:rgba(99,102,241,.1); color:var(--color-primary,#6366f1); letter-spacing:.02em; }
    :root.dark .f-meta-chip{ background:rgba(99,102,241,.2); color:#c7d2fe; }

        color:#111827; font-size:.85rem; transition:border-color .15s ease, box-shadow .15s ease; }
        background:var(--fx-grad,#6366f1); color:#fff; font-weight:500; font-size:.85rem; white-space:nowrap;
        box-shadow:0 8px 18px -10px rgba(99,102,241,.7); transition:filter .15s ease, transform .15s ease; }
</style>
