<footer class="relative text-sm mt-16" style="color:var(--color-text,#6b7280)">
    <!-- фон -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
        style="background-image:var(--bg-image); background-repeat:repeat; background-size:auto;"></div>

    <div class="relative z-10 backdrop-blur-md border-t border-gray-200/80 dark:border-gray-700/80 shadow-inner bg-white dark:bg-gray-800 transition-colors duration-200"
        style="background:var(--color-footer,#ffffff)">

        <!-- ===== 3 колонки ===== -->
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-8 sm:py-10 md:py-12 grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-10 md:gap-12">

            <!-- 1) О разработчике (без изменений) -->
            <section aria-labelledby="dev-info" class="text-center">
                <div class="flex items-center gap-2 mb-3 justify-center">
                    <div class="text-white font-bold w-9 h-9 flex items-center justify-center shadow-inner text-sm tracking-wide rounded"
                        style="background:var(--color-primary,#2563eb)">RU</div>
                    <div>
                        <h2 id="dev-info" class="text-lg font-bold leading-tight text-gray-900 dark:text-gray-100">CMS</h2>
                        <div class="text-[11px] leading-tight opacity-70">Laravel {{ app()->version() }}</div>
                    </div>
                </div>

                <ul class="space-y-2 text-[13px] inline-block text-left">
                    <li class="flex items-start gap-2">
                        @themeIcon('user')
                        <div><span class="font-medium text-gray-900 dark:text-gray-100">Разработчик:</span>
                            <span class="text-gray-700 dark:text-gray-300">Булавацкий Д.О.</span></div>
                    </li>
                    <li class="flex items-start gap-2">
                        @themeIcon('mail')
                        <div><a href="mailto:you@example.com" class="hover:underline text-gray-700 dark:text-gray-300
                                                                      hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                visitorsec@internet.ru</a></div>
                    </li>
                    <li class="flex items-start gap-2">
                        @themeIcon('phone')
                        <div><a href="tel:+7XXXXXXXXXX" class="hover:underline text-gray-700 dark:text-gray-300
                                                                 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                +7 (###) ###-##-##</a></div>
                    </li>
                </ul>
            </section>

            <!-- 2) Навигация -->
            <nav aria-labelledby="footer-nav">
                <h2 id="footer-nav" class="text-base font-semibold mb-4 text-center text-gray-900 dark:text-gray-100">Навигация</h2>
                @php
                    $navLinks = [
                        ['url' => '/terms', 'icon' => 'house', 'text' => 'Соглашение'],
                        ['url' => '/partnership', 'icon' => 'search', 'text' => 'Сотрудничество'],
                        ['url' => '/developers', 'icon' => 'code', 'text' => 'Разработчикам'],
                        ['url' => '/concept', 'icon' => 'star', 'text' => 'О проекте'],
                        ['url' => '/sitemap', 'icon' => 'phone', 'text' => 'Карта сайта'],
                        ['url' => '/donate', 'icon' => 'heart', 'text' => 'Поддержать проект'],
                    ];
                @endphp
                <ul class="grid grid-cols-2 gap-y-2 gap-x-6 list-none m-0 p-0">
                    @foreach ($navLinks as $link)
                        <li>
                            <a href="{{ url($link['url']) }}"
                                class="inline-flex items-center gap-2 px-2 py-1 rounded transition
                                       hover:bg-gray-50 dark:hover:bg-gray-700
                                       text-gray-700 dark:text-gray-300
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                                       focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                                @themeIcon($link['icon'])
                                <span class="text-[13px]">{{ $link['text'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <!-- 3) Контакты и соцсети -->
            <section aria-labelledby="footer-links">
                <h2 id="footer-links" class="text-base font-semibold mb-4 text-center text-gray-900 dark:text-gray-100">Контакты и соцсети</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <!-- E-mail -->
                    <a href="mailto:Suglobov2015@mail.ru" target="_blank" rel="noopener"
                        class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition
                               border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-600
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                        <span class="inline-flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <span class="text-xl leading-none">@themeIcon('envelope')</span> E-mail
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">@themeIcon('arrow-up-right-from-square')</span>
                    </a>

                    <!-- Телефон -->
                    <a href="tel:+79300373536"
                        class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition
                               border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-600
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                        <span class="inline-flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <span class="text-xl leading-none">@themeIcon('phone')</span> Позвонить
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">@themeIcon('arrow-up-right-from-square')</span>
                    </a>

                    <!-- VK -->
                    <a href="https://vk.com/club228649931" target="_blank" rel="noopener"
                        class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition
                               border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-600
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                        <span class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400">
                            <span class="text-xl leading-none">@themeIcon('lock')</span> ВКонтакте
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">@themeIcon('arrow-up-right-from-square')</span>
                    </a>

                    <!-- WhatsApp -->
                    <a href="https://wa.me/79300373536" target="_blank" rel="noopener"
                        class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition
                               border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-600
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                        <span class="inline-flex items-center gap-2 text-green-600 dark:text-green-400">
                            <span class="text-xl leading-none">@themeIcon('whatsapp')</span> WhatsApp
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">@themeIcon('arrow-up-right-from-square')</span>
                    </a>

                    <!-- Telegram -->
                    <a href="tg://resolve?phone=79300373536"
                        onclick="this.href=this.href; this.setAttribute('data-opened','1');"
                        data-fallback="https://t.me/+79300373536" target="_blank" rel="noopener"
                        class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition
                               border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-600
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                        <span class="inline-flex items-center gap-2 text-blue-500 dark:text-blue-400">
                            <span class="text-xl leading-none">@themeIcon('telegram')</span> Telegram
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">@themeIcon('arrow-up-right-from-square')</span>
                    </a>

                    <!-- Адрес (Яндекс.Карты) -->
                    <a href="https://yandex.ru/maps/?text={{ urlencode('Курск, проспект Вячеслава Клыкова, 73') }}"
                        target="_blank" rel="noopener"
                        class="sm:col-span-2 flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition
                               border-gray-200 dark:border-gray-700
                               bg-white dark:bg-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-600
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400">
                        <span class="inline-flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <span class="text-xl leading-none">@themeIcon('map')</span> Курск, проспект Вячеслава
                            Клыкова, 73
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">@themeIcon('arrow-up-right-from-square')</span>
                    </a>
                </div>
            </section>
        </div>

        <!-- ===== Нижняя полоса ===== -->
        <div class="border-t border-gray-200/80 dark:border-gray-700/80 px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-4 sm:py-6 backdrop-blur-sm bg-white dark:bg-gray-800 transition-colors duration-200"
            style="background:var(--color-footer,#ffffff)">
            <div class="max-w-screen-2xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4 sm:gap-6 text-xs">

                <!-- Напишите нам (mailto) -->
                <form method="GET" action="#" id="footerMailForm" x-data="{ email: '', busy: false, msg: '' }"
                    @submit.prevent="
                msg='';
                if(!email.match(/^[^@\s]+@[^@\s]+\.[^@\s]+$/)){ msg='Введите корректный e-mail'; return; }
                busy=true;
                // mailto: нельзя выставить 'От кого', это задаёт почтовый клиент.
                const to='Suglobov2015@mail.ru';
                const subject=encodeURIComponent('Сообщение с сайта');
                const body=encodeURIComponent('Мой e-mail: '+email+'\n\nСообщение:');
                window.location.href = 'mailto:'+to+'?subject='+subject+'&body='+body;
                setTimeout(()=>busy=false,800);
              "
                    class="w-full md:w-[50%] flex flex-col sm:flex-row gap-3 text-sm">
                    <label for="newsletter" class="sr-only">Напишите нам</label>
                    <input id="newsletter" type="email" name="email" x-model="email"
                        placeholder="Напишите нам — ваш e-mail"
                        class="px-4 py-2 rounded border focus:ring-2 focus:outline-none flex-1
                               border-gray-300 dark:border-gray-600 
                               bg-white dark:bg-gray-700 
                               text-gray-900 dark:text-gray-100 
                               placeholder-gray-500 dark:placeholder-gray-400
                               focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors"
                        required>
                    <button type="submit" :disabled="busy"
                        class="px-4 py-2 rounded transition font-semibold disabled:opacity-60
                               bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600
                               text-white transition-colors">
                        <span x-show="!busy">Открыть почту</span>
                        <span x-show="busy">Открываю…</span>
                    </button>
                    <p x-text="msg" class="text-red-600 text-[12px] sm:ml-2"></p>
                </form>

                <!-- Язык + копирайт -->
                <div class="flex flex-col md:flex-row md:items-center gap-4 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-gray-100">Язык:</span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded border
                                    border-gray-200 dark:border-gray-700
                                    bg-white dark:bg-gray-700
                                    text-gray-900 dark:text-gray-100">
                            <span class="text-xs">RU</span> Русский
                        </span>
                    </div>
                    <span class="text-center md:text-left text-gray-600 dark:text-gray-400">
                        © {{ date('Y') }} Все права защищены
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Наверх -->
    <button id="backToTopBtn"
        class="fixed bottom-6 right-6 z-50 p-3 rounded shadow-md transition transform hover:scale-105 opacity-0 pointer-events-none
               bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600
               text-white shadow-lg dark:shadow-gray-900/50"
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
            window.scrollTo({
                top: 0,
                behavior: reduce ? 'auto' : 'smooth'
            });
        });
        const onScroll = () => toggle((window.scrollY || document.documentElement.scrollTop) > 300);
        window.addEventListener('scroll', onScroll, {
            passive: true
        });
        onScroll();

        // Простенький фолбэк для Telegram, если deep-link не сработал
        document.querySelectorAll('a[href^="tg://"][data-fallback]').forEach(a => {
            a.addEventListener('click', () => {
                setTimeout(() => {
                    if (!a.getAttribute('data-opened')) window.open(a.getAttribute(
                        'data-fallback'), '_blank');
                }, 600);
            }, {
                passive: true
            });
        });
    })();
</script>

<style>
    /* утилиты, если Tailwind list-style отключён сборщиком */
    .list-none {
        list-style: none;
    }

    #backToTopBtn {
        transition: opacity .2s ease, transform .2s ease;
    }

    @media (prefers-reduced-motion: reduce) {
        #backToTopBtn {
            transition: none;
        }
    }
</style>
