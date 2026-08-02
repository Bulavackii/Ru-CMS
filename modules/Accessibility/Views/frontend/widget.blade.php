<!-- ♿ Виджет доступности -->
<div x-data="accessibilityWidget()" x-init="init()" class="fixed bottom-6 left-6 z-50 flex flex-col items-start">
    <!-- Кнопка -->
    <button @click="open = !open"
        class="w-12 h-12 rounded-full bg-blue-700 text-white shadow-lg flex items-center justify-center hover:bg-blue-800 transition duration-300"
        title="{{ __('frontend.a11y.widget') }}" :aria-expanded="open.toString()">
        <i class="fas fa-universal-access text-2xl"></i>
    </button>

    <!-- Панель -->
    <div x-show="open" x-transition role="dialog" aria-modal="false"
        :aria-label="@js(__('frontend.a11y.title'))"
        class="mt-4 w-80 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl shadow-xl p-4 space-y-3 text-sm text-gray-800 dark:text-gray-100"
        @click.outside="open = false" @keydown.escape.window="open = false" style="display: none;">

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
                                    this.options[0].active = true;
                                    msg.onend = () => {
                                        this.speaking = false;
                                        this.options[0].active = false;
                                    };
                                }
                            } else {
                                speechSynthesis.cancel();
                                this.speaking = false;
                                this.options[0].active = false;
                            }
                        }
                    },
                    {
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
                                this.options[1].active = true;
                                msg.onend = () => {
                                    this.speaking = false;
                                    this.options[1].active = false;
                                };
                            } else {
                                speechSynthesis.cancel();
                                this.speaking = false;
                                this.options[1].active = false;
                            }
                        }
                    },
                    {
                        label: @js(__('frontend.a11y.contrast')),
                        icon: 'fas fa-adjust',
                        active: false,
                        enableText: @js(__('frontend.a11y.on')),
                        disableText: @js(__('frontend.a11y.off')),
                        enabled: this.settings.enable_contrast,
                        action: () => {
                            const wrapper = document.getElementById('wrapper');
                            document.getElementById('wrapper').classList.toggle('contrast');
                            this.options[2].active = !this.options[2].active;
                        }
                    },
                    {
                        label: @js(__('frontend.a11y.monochrome')),
                        icon: 'fas fa-low-vision',
                        active: false,
                        enableText: @js(__('frontend.a11y.on')),
                        disableText: @js(__('frontend.a11y.off')),
                        enabled: this.settings.enable_bw_mode,
                        action: () => {
                            const wrapper = document.getElementById('wrapper');
                            wrapper.style.filter = this.options[3].active ? '' : 'grayscale(1)';
                            this.options[3].active = !this.options[3].active;
                        }
                    },
                    {
                        label: @js(__('frontend.a11y.sepia')),
                        icon: 'fas fa-tint',
                        active: false,
                        enableText: @js(__('frontend.a11y.on')),
                        disableText: @js(__('frontend.a11y.off')),
                        enabled: this.settings.enable_sepia_mode,
                        action: () => {
                            const wrapper = document.getElementById('wrapper');
                            wrapper.style.filter = this.options[4].active ? '' : 'sepia(1)';
                            this.options[4].active = !this.options[4].active;
                        }
                    },
                    {
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
                                this.options[5].active = true;
                            } else {
                                if (this.maskEl) this.maskEl.remove();
                                this.readingMaskActive = false;
                                this.options[5].active = false;
                            }
                        }
                    },
                    {
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
                            this.options[6].active = this.highlightLinks;
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
                    { key: 'a11y-bg',       cls: 'a11y-soft-bg',   icon: 'fas fa-palette',      label: @js(__('frontend.a11y.background')),   enabled: this.settings.enable_background },
                    { key: 'a11y-cb',       cls: 'a11y-colorblind', icon: 'fas fa-eye',         label: @js(__('frontend.a11y.colorblind')),   enabled: this.settings.enable_colorblind_mode },
                    { key: 'a11y-dys',      cls: 'a11y-dyslexia',  icon: 'fas fa-font',         label: @js(__('frontend.a11y.dyslexia')),     enabled: this.settings.enable_dyslexia_font },
                    { key: 'a11y-read',     cls: 'a11y-read-mode', icon: 'fas fa-book',         label: @js(__('frontend.a11y.read_mode')),    enabled: this.settings.enable_read_mode },
                    { key: 'a11y-spacing',  cls: 'a11y-spacing',   icon: 'fas fa-arrows-alt-v', label: @js(__('frontend.a11y.spacing')),      enabled: this.settings.enable_text_spacing },
                ];
            },

            classModes() {
                return this.classModeList().map(mode => ({
                    label: mode.label,
                    icon: mode.icon,
                    active: localStorage.getItem(mode.key) === 'true',
                    enableText: @js(__('frontend.a11y.on')),
                    disableText: @js(__('frontend.a11y.off')),
                    enabled: mode.enabled,
                    action: () => this.toggleClassMode(mode),
                }));
            },

            toggleClassMode(mode) {
                const wrapper = document.getElementById('wrapper');
                if (!wrapper) return;

                const on = !wrapper.classList.contains(mode.cls);
                wrapper.classList.toggle(mode.cls, on);
                localStorage.setItem(mode.key, on);

                const option = this.options.find(o => o.label === mode.label);
                if (option) option.active = on;
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
                document.querySelectorAll('a').forEach(el => el.classList.remove('highlight-link'));

                this.fontSize = 16;
                this.applyFontSize();

                if (window.speechSynthesis) window.speechSynthesis.cancel();
                this.speaking = false;

                this.options.forEach(o => { o.active = false; });
            },

            applyFontSize() {
                const wrapper = document.getElementById('wrapper');
                if (wrapper) {
                    wrapper.style.fontSize = this.fontSize + 'px';
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
