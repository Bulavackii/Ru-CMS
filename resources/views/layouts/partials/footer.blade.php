<footer class="relative text-sm mt-16" style="color:var(--color-text,#6b7280)">
    <!-- фон-паттерн темы -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
        style="background-image:var(--bg-image); background-repeat:repeat; background-size:auto;"></div>

    <div class="relative z-10 backdrop-blur-md border-t border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-800 transition-colors duration-200"
        style="background:var(--color-footer,#ffffff)">

        {{-- Верхняя градиентная акцент-полоса подвала (единый стиль) --}}
        <div class="fx-accent-bar"></div>

        {{-- ===== Колонки подвала. Число столбцов авто-подстраивается: слева
             «Разработчик», справа «Контакты», между ними — по одному столбцу на
             каждое footer-меню (одно меню = один столбец). Сетку и include
             модуля Меню НЕ трогаем. ===== --}}
        <style>
            .footer-grid{ display:grid; grid-template-columns:1fr; }
            @media (min-width:768px){ .footer-grid{ grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); } }
        </style>
        <div class="footer-grid max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-9 sm:py-11 md:py-12 gap-8 sm:gap-10 md:gap-12">

            {{-- 1) Бренд / разработчик — карточка --}}
            <section aria-labelledby="dev-info" class="footer-brand">
                <div class="f-brand-card">
                    <div class="flex items-center gap-2.5 mb-2">
                        <span class="f-brand-badge">RU</span>
                        <div class="min-w-0">
                            <h2 id="dev-info" class="text-base font-bold leading-tight text-gray-900 dark:text-white">RU CMS</h2>
                            <div class="text-[11px] leading-tight text-gray-500 dark:text-gray-400">Модульная CMS · Laravel {{ app()->version() }}</div>
                        </div>
                    </div>

                    {{-- Обезличенные тестовые сведения (компактно) --}}
                    <ul class="f-devlist f-devlist--tight">
                        <li><span class="f-ico">@themeIcon('user')</span>
                            <span><b class="text-gray-700 dark:text-gray-300 font-medium">Иван Иванов</b> — разработчик</span></li>
                        <li><span class="f-ico">@themeIcon('mail')</span>
                            <a href="mailto:info@example.com" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">info@example.com</a></li>
                        <li><span class="f-ico">@themeIcon('phone')</span>
                            <a href="tel:+79001234567" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">+7 (900) 123-45-67</a></li>
                    </ul>
                </div>
            </section>

            {{-- 2) Столбцы из меню позиции footer (одно меню = один столбец).
                 Партиал модуля Меню — НЕ ТРОГАЕМ. Fallback на прежние ссылки внутри. --}}
            @include('Menu::frontend.footer')

            {{-- 3) Контакты и соцсети --}}
            <section aria-labelledby="footer-links" class="footer-contacts text-center md:text-left">
                <h2 id="footer-links" class="f-heading">Контакты</h2>

                {{-- Обезличенные тестовые сведения --}}
                <div class="space-y-2">
                    <a href="mailto:info@example.com" class="f-contact">
                        <span class="f-ico">@themeIcon('mail')</span><span>info@example.com</span>
                        <span class="f-ext">@themeIcon('arrow-up-right-from-square')</span>
                    </a>
                    <a href="tel:+79001234567" class="f-contact">
                        <span class="f-ico">@themeIcon('phone')</span><span>+7 (900) 123-45-67</span>
                    </a>
                    <a href="https://yandex.ru/maps/?text={{ urlencode('Москва, улица Примерная, 1') }}"
                       target="_blank" rel="noopener" class="f-contact">
                        <span class="f-ico">@themeIcon('map')</span><span>г. Москва, ул. Примерная, 1</span>
                        <span class="f-ext">@themeIcon('arrow-up-right-from-square')</span>
                    </a>
                </div>

                {{-- Соцсети — реальные бренд-иконки FontAwesome, бренд-цвет при наведении.
                     Адреса обезличены (демо). --}}
                <div class="mt-4 flex items-center gap-2.5 justify-center md:justify-start">
                    <a href="https://vk.com/example" target="_blank" rel="noopener" class="f-social" style="--c:#0077FF" title="ВКонтакте" aria-label="ВКонтакте"><i class="fab fa-vk"></i></a>
                    <a href="https://wa.me/79001234567" target="_blank" rel="noopener" class="f-social" style="--c:#25D366" title="WhatsApp" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://t.me/example" target="_blank" rel="noopener" class="f-social" style="--c:#26A5E4" title="Telegram" aria-label="Telegram"><i class="fab fa-telegram"></i></a>
                </div>
            </section>
        </div>

        {{-- ===== Нижняя мета-полоса ===== --}}
        <div class="border-t border-gray-200/80 dark:border-gray-700/80 px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-4 sm:py-5 backdrop-blur-sm bg-white dark:bg-gray-800 transition-colors duration-200"
            style="background:var(--color-footer,#ffffff)">
            <div class="max-w-screen-2xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4 text-xs">

                {{-- Мета: копирайт + стек --}}
                <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-gray-500 dark:text-gray-400">
                    <span>© {{ date('Y') }} <b class="text-gray-700 dark:text-gray-300 font-medium">RU CMS</b> — все права защищены</span>
                    <span class="f-meta-chip">PHP {{ PHP_VERSION }}</span>
                    <span class="f-meta-chip">Laravel {{ app()->version() }}</span>
                </div>

                {{-- Напишите нам (mailto) — Alpine-логика без изменений --}}
                <form method="GET" action="#" id="footerMailForm" x-data="{ email: '', busy: false, msg: '' }"
                    @submit.prevent="
                msg='';
                if(!email.match(/^[^@\s]+@[^@\s]+\.[^@\s]+$/)){ msg='Введите корректный e-mail'; return; }
                busy=true;
                const to='Suglobov2015@mail.ru';
                const subject=encodeURIComponent('Сообщение с сайта');
                const body=encodeURIComponent('Мой e-mail: '+email+'\n\nСообщение:');
                window.location.href = 'mailto:'+to+'?subject='+subject+'&body='+body;
                setTimeout(()=>busy=false,800);
              "
                    class="w-full md:flex-1 md:max-w-xl md:ml-6 flex flex-col sm:flex-row gap-2 text-sm">
                    <label for="newsletter" class="sr-only">Напишите нам</label>
                    <input id="newsletter" type="email" name="email" x-model="email"
                        placeholder="Ваш e-mail"
                        class="f-mail-input flex-1" required>
                    <button type="submit" :disabled="busy" class="f-submit">
                        <span x-show="!busy" class="inline-flex items-center gap-2">@themeIcon('mail') Отправить</span>
                        <span x-show="busy">Открываю…</span>
                    </button>
                    <p x-text="msg" class="text-red-600 text-[12px] sm:self-center"></p>
                </form>
            </div>
        </div>
    </div>

    {{-- Наверх --}}
    <button id="backToTopBtn"
        class="fixed bottom-6 right-6 z-50 p-3 shadow-md transition transform hover:scale-105 opacity-0 pointer-events-none text-white"
        style="background:var(--fx-grad,#6366f1); box-shadow:0 10px 24px -8px rgba(99,102,241,.6)"
        title="Наверх" aria-label="Наверх">
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

        // Фолбэк для Telegram, если deep-link не сработал
        document.querySelectorAll('a[href^="tg://"][data-fallback]').forEach(a => {
            a.addEventListener('click', () => {
                setTimeout(() => {
                    if (!a.getAttribute('data-opened')) window.open(a.getAttribute('data-fallback'), '_blank');
                }, 600);
            }, { passive: true });
        });
    })();
</script>

<style>
    .list-none{ list-style:none; }
    #backToTopBtn{ transition:opacity .2s ease, transform .2s ease; }
    @media (prefers-reduced-motion: reduce){ #backToTopBtn{ transition:none; } }

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

    .f-contact{ display:flex; align-items:center; gap:.6rem; padding:.5rem .65rem; font-size:.82rem; text-decoration:none;
        border:1px solid rgba(17,24,39,.1); background:rgba(255,255,255,.55); color:#374151;
        transition:border-color .15s ease, background .15s ease, transform .15s ease, color .15s ease; }
    :root.dark .f-contact{ border-color:rgba(255,255,255,.1); background:rgba(30,41,59,.45); color:#d1d5db; }
    .f-contact:hover{ border-color:var(--color-primary,#6366f1); background:rgba(99,102,241,.06); transform:translateY(-1px); }
    .f-contact > span:nth-child(2){ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .f-ext{ margin-left:auto; opacity:.4; }
    .f-ext svg, .f-ext i{ width:.72rem; height:.72rem; font-size:.72rem; }

    .f-social{ display:inline-flex; align-items:center; justify-content:center; width:2.35rem; height:2.35rem;
        border:1px solid rgba(17,24,39,.1); background:rgba(255,255,255,.55); color:#6b7280; font-size:1.05rem;
        text-decoration:none; transition:color .15s ease, background .15s ease, border-color .15s ease, transform .15s ease; }
    :root.dark .f-social{ border-color:rgba(255,255,255,.1); background:rgba(30,41,59,.45); color:#9ca3af; }
    .f-social:hover{ color:#fff; background:var(--c,#6366f1); border-color:var(--c,#6366f1); transform:translateY(-2px); }

    .f-meta-chip{ display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .5rem; font-size:.66rem; font-weight:600;
        background:rgba(99,102,241,.1); color:var(--color-primary,#6366f1); letter-spacing:.02em; }
    :root.dark .f-meta-chip{ background:rgba(99,102,241,.2); color:#c7d2fe; }

    .f-mail-input{ padding:.5rem .8rem; border:1px solid rgba(17,24,39,.14); background:rgba(255,255,255,.7);
        color:#111827; font-size:.85rem; transition:border-color .15s ease, box-shadow .15s ease; }
    :root.dark .f-mail-input{ background:rgba(30,41,59,.7); border-color:rgba(255,255,255,.12); color:#f3f4f6; }
    .f-mail-input::placeholder{ color:#9ca3af; }
    .f-mail-input:focus{ outline:none; border-color:#818cf8; box-shadow:0 0 0 3px rgba(99,102,241,.18); }
    .f-submit{ display:inline-flex; align-items:center; justify-content:center; gap:.4rem; padding:.5rem 1rem;
        background:var(--fx-grad,#6366f1); color:#fff; font-weight:500; font-size:.85rem; white-space:nowrap;
        box-shadow:0 8px 18px -10px rgba(99,102,241,.7); transition:filter .15s ease, transform .15s ease; }
    .f-submit:hover{ filter:brightness(1.07); transform:translateY(-1px); }
    .f-submit:disabled{ opacity:.6; }
</style>
