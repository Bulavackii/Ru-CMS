<!-- ♿ Виджет доступности -->
<div x-data="accessibilityWidget()" x-init="init()" class="fixed bottom-6 left-6 z-50 flex flex-col items-start">
    <!-- Кнопка -->
    <button @click="open = !open"
        class="w-12 h-12 rounded-full bg-blue-700 text-white shadow-lg flex items-center justify-center hover:bg-blue-800 transition duration-300"
        title="Спецвозможности" :aria-expanded="open.toString()">
        <i class="fas fa-universal-access text-2xl"></i>
    </button>

    <!-- Панель -->
    <div x-show="open" x-transition
        class="mt-4 w-80 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl shadow-xl p-4 space-y-3 text-sm text-gray-800 dark:text-gray-100"
        @click.outside="open = false" style="display: none;">

        <h3 class="font-semibold text-base text-blue-700 flex items-center gap-2 mb-1">
            <i class="fas fa-eye"></i> Настройки доступности
        </h3>

        <!-- Размер текста (x-show) -->
        <div class="flex items-center justify-between" x-show="settings.enable_font_size">
            <span class="flex items-center gap-2">
                <i class="fas fa-text-height mr-1"></i> Размер текста
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
    </div>
</div>

<style>
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
                        label: 'Озвучить выделенный текст',
                        icon: 'fas fa-comment-dots',
                        active: false,
                        enableText: 'Озвучить',
                        disableText: 'Стоп',
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
                        label: 'Озвучить всю страницу',
                        icon: 'fas fa-volume-up',
                        active: false,
                        enableText: 'Озвучить',
                        disableText: 'Стоп',
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
                        label: 'Контрастный режим',
                        icon: 'fas fa-adjust',
                        active: false,
                        enableText: 'Включить',
                        disableText: 'Отключить',
                        enabled: this.settings.enable_contrast,
                        action: () => {
                            const wrapper = document.getElementById('wrapper');
                            document.getElementById('wrapper').classList.toggle('contrast');
                            this.options[2].active = !this.options[2].active;
                        }
                    },
                    {
                        label: 'Монохром',
                        icon: 'fas fa-low-vision',
                        active: false,
                        enableText: 'Включить',
                        disableText: 'Отключить',
                        enabled: this.settings.enable_bw_mode,
                        action: () => {
                            const wrapper = document.getElementById('wrapper');
                            wrapper.style.filter = this.options[3].active ? '' : 'grayscale(1)';
                            this.options[3].active = !this.options[3].active;
                        }
                    },
                    {
                        label: 'Сепия',
                        icon: 'fas fa-tint',
                        active: false,
                        enableText: 'Включить',
                        disableText: 'Отключить',
                        enabled: this.settings.enable_sepia_mode,
                        action: () => {
                            const wrapper = document.getElementById('wrapper');
                            wrapper.style.filter = this.options[4].active ? '' : 'sepia(1)';
                            this.options[4].active = !this.options[4].active;
                        }
                    },
                    {
                        label: 'Маска для чтения',
                        icon: 'fas fa-minus',
                        active: false,
                        enableText: 'Показать',
                        disableText: 'Скрыть',
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
                        label: 'Подсветка ссылок',
                        icon: 'fas fa-link',
                        active: this.highlightLinks,
                        enableText: 'Включить',
                        disableText: 'Отключить',
                        enabled: this.settings.enable_highlight_links,
                        action: () => {
                            this.highlightLinks = !this.highlightLinks;
                            localStorage.setItem('highlightLinks', this.highlightLinks);
                            document.querySelectorAll('a').forEach(el => {
                                el.classList.toggle('highlight-link', this.highlightLinks);
                            });
                            this.options[6].active = this.highlightLinks;
                        }
                    }
                ];

                if (this.highlightLinks) {
                    document.querySelectorAll('a').forEach(el => {
                        el.classList.add('highlight-link');
                    });
                }
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
