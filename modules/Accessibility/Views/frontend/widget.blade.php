<!-- ♿ Виджет доступности -->
<div x-data="accessibilityWidget()" class="a11y-fab flex flex-col items-start"
     @click.outside="open = false" @keydown.escape.window="open = false">
    <!-- Кнопка -->
    <button @click="open = !open"
        class="a11y-fab__btn"
        title="{{ __('frontend.a11y.widget') }}" :aria-expanded="open.toString()">
        <i class="fas fa-universal-access text-2xl"></i>
    </button>

    <!-- Панель -->
    <div x-show="open" role="dialog" aria-modal="false"
        :aria-label="@js(__('frontend.a11y.title'))"
        class="a11y-panel mt-4 w-80 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl shadow-xl p-4 space-y-3 text-sm text-gray-800 dark:text-gray-100"
        x-cloak>

        <h3 class="font-semibold text-base text-blue-700 flex items-center gap-2 mb-1">
            <i class="fas fa-eye"></i> {{ __('frontend.a11y.title') }}
        </h3>

        <!-- Размер текста (x-show) -->
        <div class="flex items-center justify-between" x-show="settings.enable_font_size">
            <span class="flex items-center gap-2">
                <i class="fas fa-text-height mr-1"></i> {{ __('frontend.a11y.text_size') }}
            </span>
            <div class="flex items-center gap-2">
                <button @click="decreaseFontSize"
                    class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-semibold">−</button>
                <span x-text="fontSize + 'px'" class="text-xs w-10 text-center"></span>
                <button @click="increaseFontSize"
                    class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-semibold">+</button>
            </div>
        </div>

        <!-- Опции -->
        <template x-for="(option, index) in options" :key="index">
            <div class="flex items-center justify-between" x-show="option.enabled">
                <div class="flex items-center gap-2">
                    <i :class="option.icon + ' mr-1'"></i>
                    <span x-text="option.label"></span>
                </div>
                <button @click="option.action()" class="px-2 py-1 rounded text-xs font-semibold"
                    :class="option.active ? 'bg-red-100 hover:bg-red-200' : 'bg-green-100 hover:bg-green-200'"
                    x-text="option.active ? option.disableText : option.enableText">
                </button>
            </div>
        </template>

        <button type="button" @click="resetAll()"
            class="w-full mt-2 px-3 py-2 rounded text-xs font-semibold bg-gray-100 hover:bg-gray-200
                   dark:bg-gray-700 dark:hover:bg-gray-600 flex items-center justify-center gap-2">
            <i class="fas fa-rotate-left"></i> {{ __('frontend.a11y.reset') }}
        </button>
    </div>
</div>

<style>
    /* ── Режимы, включаемые классом на #wrapper ─────────────────────── */
    .a11y-soft-bg, .a11y-soft-bg .fx-card, .a11y-soft-bg .admin-card { background-color: #fdf6e3 !important }
    .a11y-colorblind { filter: saturate(1.4) hue-rotate(-12deg) }
    .a11y-dyslexia, .a11y-dyslexia * {
        font-family: "Comic Sans MS", "Trebuchet MS", Verdana, sans-serif !important;
        letter-spacing: .04em; word-spacing: .12em;
    }
    .a11y-spacing, .a11y-spacing * { line-height: 1.9 !important; letter-spacing: .06em; word-spacing: .18em }
    /* Режим чтения: убираем всё, что не текст материала */
    .a11y-read-mode header, .a11y-read-mode footer, .a11y-read-mode aside,
    .a11y-read-mode .header-nav, .a11y-read-mode .fx-topbar, .a11y-read-mode .footer-grid { display: none !important }
    .a11y-read-mode main, .a11y-read-mode #wrapper > .container { max-width: 46rem; margin: 0 auto }

    .highlight-link {
        background-color: #fefcbf;
        text-decoration: underline;
        padding: 0 2px;
        border-radius: 2px;
    }

    /* Режимы-фильтры классами, а не inline-стилем: раньше монохром и сепия
       писали в один и тот же style.filter и затирали друг друга. */
    .a11y-mono { filter: grayscale(1) !important; }
    .a11y-sepia { filter: sepia(1) !important; }

    .contrast {
        filter: contrast(1.8) invert(1);
    }

    .reading-mask {
        position: fixed;
        top: 25%;
        left: 0;
        width: 100%;
        height: 15em;
        background-color: rgba(0, 0, 0, 0.2);
        z-index: 9999;
        pointer-events: none;
    }
</style>

<script>
    function accessibilityWidget() {
        return {
            open: false,
            fontSize: parseInt(localStorage.getItem('fontSize')) || 16,
            highlightLinks: localStorage.getItem('highlightLinks') === 'true',
            readingMaskActive: false,
            speaking: false,
            maskEl: null,
            settings: {!! json_encode($settings ?? []) !!},
            options: [],

            init() {
                this.applyFontSize();

                this.options = [
                    {
                        key: 'speak_selection',
                        label: @js(__('frontend.a11y.speak_selection')),
                        icon: 'fas fa-comment-dots',
                        active: false,
                        enableText: @js(__('frontend.a11y.speak')),
                        disableText: @js(__('frontend.a11y.stop')),
                        enabled: this.settings.enable_selected_text_speech,
                        action: () => {
                            if (!this.speaking) {
                                const selectedText = window.getSelection().toString();
                                if (selectedText) {
                                    const msg = new SpeechSynthesisUtterance(selectedText);
                                    speechSynthesis.speak(msg);
                                    this.speaking = true;
                                    this.markOption('speak_selection', true);
                                    msg.onend = () => {
                                        this.speaking = false;
                                        this.markOption('speak_selection', false);
                                    };
                                }
                            } else {
                                speechSynthesis.cancel();
                                this.speaking = false;
                                this.markOption('speak_selection', false);
                            }
                        }
                    },
                    {
                        key: 'speak_page',
                        label: @js(__('frontend.a11y.speak_page')),
                        icon: 'fas fa-volume-up',
                        active: false,
                        enableText: @js(__('frontend.a11y.speak')),
                        disableText: @js(__('frontend.a11y.stop')),
                        enabled: this.settings.enable_speech,
                        action: () => {
                            if (!this.speaking) {
                                const msg = new SpeechSynthesisUtterance(document.body.innerText);
                                speechSynthesis.speak(msg);
                                this.speaking = true;
                                this.markOption('speak_page', true);
                                msg.onend = () => {
                                    this.speaking = false;
                                    this.markOption('speak_page', false);
                                };
                            } else {
                                speechSynthesis.cancel();
                                this.speaking = false;
                                this.markOption('speak_page', false);
                            }
                        }
                    },
                    {
                        key: 'mask',
                        label: @js(__('frontend.a11y.reading_mask')),
                        icon: 'fas fa-minus',
                        active: false,
                        enableText: @js(__('frontend.a11y.show')),
                        disableText: @js(__('frontend.a11y.hide')),
                        enabled: this.settings.enable_reading_mask,
                        action: () => {
                            if (!this.readingMaskActive) {
                                this.maskEl = document.createElement('div');
                                this.maskEl.className = 'reading-mask';
                                document.body.appendChild(this.maskEl);
                                this.readingMaskActive = true;
                                this.markOption('mask', true);
                            } else {
                                if (this.maskEl) this.maskEl.remove();
                                this.readingMaskActive = false;
                                this.markOption('mask', false);
                            }
                        }
                    },
                    {
                        key: 'links',
                        label: @js(__('frontend.a11y.link_highlight')),
                        icon: 'fas fa-link',
                        active: this.highlightLinks,
                        enableText: @js(__('frontend.a11y.on')),
                        disableText: @js(__('frontend.a11y.off')),
                        enabled: this.settings.enable_highlight_links,
                        action: () => {
                            this.highlightLinks = !this.highlightLinks;
                            localStorage.setItem('highlightLinks', this.highlightLinks);
                            document.querySelectorAll('a').forEach(el => {
                                el.classList.toggle('highlight-link', this.highlightLinks);
                            });
                            const linkOption = this.options.find(o => o.key === 'links');
                            if (linkOption) linkOption.active = this.highlightLinks;
                        }
                    },
                    ...this.classModes()
                ];

                this.restoreClassModes();

                if (this.highlightLinks) {
                    document.querySelectorAll('a').forEach(el => {
                        el.classList.add('highlight-link');
                    });
                }
            },

            /**
             * Режимы, которые сводятся к классу на #wrapper. Держим их
             * списком: добавить новый — одна строка, а не копия обработчика.
             */
            classModeList() {
                return [
                    { key: 'a11y-contrast', cls: 'contrast',        icon: 'fas fa-adjust',       label: @js(__('frontend.a11y.contrast')),     enabled: this.settings.enable_contrast },
                    { key: 'a11y-mono',     cls: 'a11y-mono',       icon: 'fas fa-low-vision',   label: @js(__('frontend.a11y.monochrome')),   enabled: this.settings.enable_bw_mode },
                    { key: 'a11y-sepia',    cls: 'a11y-sepia',      icon: 'fas fa-tint',         label: @js(__('frontend.a11y.sepia')),        enabled: this.settings.enable_sepia_mode },
                    { key: 'a11y-bg',       cls: 'a11y-soft-bg',   icon: 'fas fa-palette',      label: @js(__('frontend.a11y.background')),   enabled: this.settings.enable_background },
                    { key: 'a11y-cb',       cls: 'a11y-colorblind', icon: 'fas fa-eye',         label: @js(__('frontend.a11y.colorblind')),   enabled: this.settings.enable_colorblind_mode },
                    { key: 'a11y-dys',      cls: 'a11y-dyslexia',  icon: 'fas fa-font',         label: @js(__('frontend.a11y.dyslexia')),     enabled: this.settings.enable_dyslexia_font },
                    { key: 'a11y-read',     cls: 'a11y-read-mode', icon: 'fas fa-book',         label: @js(__('frontend.a11y.read_mode')),    enabled: this.settings.enable_read_mode },
                    { key: 'a11y-spacing',  cls: 'a11y-spacing',   icon: 'fas fa-arrows-alt-v', label: @js(__('frontend.a11y.spacing')),      enabled: this.settings.enable_text_spacing },
                ];
            },

            classModes() {
                return this.classModeList().map(mode => ({
                    key: mode.key,
                    label: mode.label,
                    icon: mode.icon,
                    active: localStorage.getItem(mode.key) === 'true',
                    enableText: @js(__('frontend.a11y.on')),
                    disableText: @js(__('frontend.a11y.off')),
                    enabled: mode.enabled,
                    action: () => this.toggleClassMode(mode),
                }));
            },

            /**
             * Отметить пункт активным по ключу.
             *
             * Раньше это делалось по позиции в массиве (options[5] и т.п.):
             * стоило добавить или убрать пункт — и отметка уезжала на соседний,
             * молча и без ошибки.
             */
            markOption(key, active) {
                const option = this.options.find(o => o.key === key);
                if (option) option.active = active;
            },

            toggleClassMode(mode) {
                const wrapper = document.getElementById('wrapper');
                if (!wrapper) return;

                const on = !wrapper.classList.contains(mode.cls);
                wrapper.classList.toggle(mode.cls, on);
                localStorage.setItem(mode.key, on);

                this.markOption(mode.key, on);
            },

            /** Выбор посетителя должен переживать переход на другую страницу. */
            restoreClassModes() {
                const wrapper = document.getElementById('wrapper');
                if (!wrapper) return;

                this.classModeList().forEach(mode => {
                    if (mode.enabled && localStorage.getItem(mode.key) === 'true') {
                        wrapper.classList.add(mode.cls);
                    }
                });
            },

            /** Сброс: вернуть страницу в обычный вид одним действием. */
            resetAll() {
                const wrapper = document.getElementById('wrapper');

                this.classModeList().forEach(mode => {
                    if (wrapper) wrapper.classList.remove(mode.cls);
                    localStorage.removeItem(mode.key);
                });

                if (wrapper) {
                    wrapper.classList.remove('contrast');
                    wrapper.style.filter = '';
                    wrapper.style.fontSize = '';
                }

                if (this.maskEl) {
                    this.maskEl.remove();
                    this.maskEl = null;
                }
                this.readingMaskActive = false;

                this.highlightLinks = false;
                localStorage.removeItem('highlightLinks');
                localStorage.removeItem('fontSize');
                document.documentElement.style.fontSize = '';
                document.body.style.fontSize = '';
                document.querySelectorAll('a').forEach(el => el.classList.remove('highlight-link'));

                this.fontSize = 16;
                this.applyFontSize();

                if (window.speechSynthesis) window.speechSynthesis.cancel();
                this.speaking = false;

                this.options.forEach(o => { o.active = false; });
            },

            applyFontSize() {
                // Размер ставим на <html>, а не на #wrapper.
                //
                // Шапка и подвал лежат ВНЕ #wrapper, поэтому не менялись
                // вовсе. Да и внутри почти вся вёрстка в rem, а rem считается
                // от корня документа, а не от родителя: увеличение на
                // #wrapper влияло только на редкие места с em и наследуемым
                // размером. Через <html> масштабируется всё сразу.
                document.documentElement.style.fontSize = this.fontSize + 'px';

                // И на body тоже: часть вёрстки наследует размер от него, а
                // не считает от корня.
                document.body.style.fontSize = this.fontSize + 'px';

                // Прежнее значение на #wrapper иначе осталось бы висеть и
                // задавало бы второй, конкурирующий размер.
                const wrapper = document.getElementById('wrapper');
                if (wrapper) {
                    wrapper.style.fontSize = '';
                }
            },

            increaseFontSize() {
                if (this.fontSize < 32) {
                    this.fontSize += 2;
                    localStorage.setItem('fontSize', this.fontSize);
                    this.applyFontSize();
                }
            },

            decreaseFontSize() {
                if (this.fontSize > 12) {
                    this.fontSize -= 2;
                    localStorage.setItem('fontSize', this.fontSize);
                    this.applyFontSize();
                }
            }
        }
    }
</script>

<style>
    /* Кнопка виджета следует за оформлением сайта.
       Раньше цвет был прибит классом bg-blue-700: при смене темы весь сайт
       перекрашивался, а она оставалась синей. Позиционирование тоже здесь —
       литеральным CSS, а не утилитами: так оно не зависит от того, какие
       классы попали в статическую сборку Tailwind. */
    .a11y-fab{ position:fixed; bottom:1.5rem; left:1.5rem; z-index:9999; filter:none !important; isolation:isolate }

    .a11y-fab__btn{ display:flex; align-items:center; justify-content:center; width:3rem; height:3rem;
                    border:0; cursor:pointer; border-radius:9999px;
                    background:var(--color-primary, #6366f1); color:#fff;
                    box-shadow:0 4px 14px rgba(15,23,42,.25);
                    transition:transform .15s ease, box-shadow .15s ease, background .2s ease }
    .a11y-fab__btn:hover{ transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.3) }
    .a11y-fab__btn:focus-visible{ outline:3px solid var(--color-accent, #8b5cf6); outline-offset:3px }
    .a11y-fab__btn i{ font-size:1.4rem }

    /* Пока Alpine не поднялся, панель скрыта этим правилом, а не инлайновым
       display:none — тот ломал первый кадр перехода. */
    .a11y-fab [x-cloak]{ display:none !important }

    /* Анимации открытия здесь намеренно нет.
       И x-transition, и CSS-анимация оставляли панель на opacity:0: смена
       display у x-show перезапускала переход с нулевого кадра снова и снова.
       Панель открывалась, но была полностью прозрачной — со стороны это
       выглядело как «нажимаю на кнопку, ничего не происходит». */
    .a11y-panel{ opacity:1 }
</style>
