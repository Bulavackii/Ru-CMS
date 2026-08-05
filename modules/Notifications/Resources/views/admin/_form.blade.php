{{--
    Общая форма создания и редактирования уведомления.
    Ожидает: $notification (модель или null), $action, $method, $submitLabel.
--}}
@php
    $n = $notification ?? null;
    $val = fn (string $field, $default = null) => old($field, $n->{$field} ?? $default);
    $dt = function (string $field) use ($n) {
        $value = old($field);
        if ($value) return $value;
        return optional($n?->{$field})->format('Y-m-d\TH:i');
    };
    // Чекбокс: при ошибке валидации берём отправленное значение, иначе — из модели
    $enabled = old('_submitted') ? (bool) old('enabled') : (bool) ($n->enabled ?? true);
@endphp

@if ($errors->any())
    <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
        <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">
            <i class="fas fa-triangle-exclamation mr-1"></i> Проверьте заполнение формы
        </p>
        <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Состояние держим на форме целиком: предпросмотр должен видеть и
     заголовок из «Содержимого», и цвета из «Оформления». --}}
<form method="POST" action="{{ $action }}" id="notificationForm"
      x-data="notifForm({
          title: @js($val('title')),
          icon: @js($val('icon', '🔔')),
          type: @js($val('type', 'text')),
          position: @js($val('position', 'top')),
          bg: @js($val('bg_color', '#EEF2FF')),
          fg: @js($val('text_color', '#111827')),
      })">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="_submitted" value="1">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

        {{-- ── Содержимое ── --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-pen-to-square text-indigo-500"></i> Содержимое
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Заголовок <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ $val('title') }}" required x-model="title"
                               placeholder="Например: Плановые технические работы"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        @error('title')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="icon" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Иконка</label>
                        <input type="text" id="icon" name="icon" value="{{ $val('icon', '🔔') }}" x-model="icon"
                               placeholder="🔔 или fas fa-bolt"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <p class="admin-hint mt-1">Эмодзи или класс Font Awesome.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="editor" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Текст уведомления <span class="text-red-500">*</span>
                    </label>
                    <x-ru-editor name="message" id="editor" preset="mail" :height="280"
                                  :value="$val('message')"
                                  placeholder="Что нужно сообщить посетителю" />
                    @error('message')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ── Оформление ── --}}
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-palette text-indigo-500"></i> Оформление
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Тип</label>
                        <select id="type" name="type" x-model="type"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="text">Текст — разметка вырезается</option>
                            <option value="html">HTML — ссылки и форматирование</option>
                            <option value="cookie">Согласие — с кнопками «Принять» и «Только необходимые»</option>
                        </select>

                        {{-- Подсказка меняется вместе с типом: у согласия
                             поведение другое во всём, и общая фраза про
                             «запоминается в браузере» вводила в заблуждение. --}}
                        <p class="admin-hint mt-1" x-show="type !== 'cookie'" x-cloak>
                            Закрывается крестиком и больше не показывается в этом браузере.
                        </p>
                        <p class="admin-hint mt-1" x-show="type === 'cookie'" x-cloak>
                            Кнопки добавляются сами, крестика нет — ответ должен быть осознанным.
                            Выбор держится <b>до закрытия браузера</b>, потом спросим снова.
                            Пока не нажали «Принять», Яндекс.Метрика не запускается.
                        </p>
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Позиция</label>
                        <select id="position" name="position" x-model="position"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="top" @selected($val('position', 'top') === 'top')>Сверху</option>
                            <option value="bottom" @selected($val('position') === 'bottom')>Снизу</option>
                            <option value="fullscreen" @selected($val('position') === 'fullscreen')>По центру экрана</option>
                        </select>
                    </div>

                    {{-- Поле показывается только там, где работает: у обычных
                         баннеров оно не значило ничего и просто занимало место
                         в строке. --}}
                    <div x-show="type === 'cookie'" x-cloak>
                        <label for="cookie_key" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Ключ cookie</label>
                        <input type="text" id="cookie_key" name="cookie_key" value="{{ $val('cookie_key') }}"
                               placeholder="ru_consent"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <p class="admin-hint mt-1">
                            По этому ключу сайт понимает, что ответил посетитель.
                            Для согласия на cookie он должен быть <code>ru_consent</code> — именно его
                            проверяют счётчики. Пусто — ключ соберётся по номеру уведомления.
                        </p>
                        @error('cookie_key')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="bg_color" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Цвет фона</label>
                        <div class="flex gap-2">
                            <input type="color" id="bg_color_picker" value="{{ $val('bg_color', '#EEF2FF') }}" x-model="bg"
                                   class="w-10 h-9 border border-gray-300 dark:border-gray-700 p-0 cursor-pointer">
                            <input type="text" id="bg_color" name="bg_color" value="{{ $val('bg_color', '#EEF2FF') }}" x-model="bg"
                                   placeholder="#EEF2FF"
                                   class="flex-1 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        @error('bg_color')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="text_color" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Цвет текста</label>
                        <div class="flex gap-2">
                            <input type="color" id="text_color_picker" value="{{ $val('text_color', '#111827') }}" x-model="fg"
                                   class="w-10 h-9 border border-gray-300 dark:border-gray-700 p-0 cursor-pointer">
                            <input type="text" id="text_color" name="text_color" value="{{ $val('text_color', '#111827') }}" x-model="fg"
                                   placeholder="#111827"
                                   class="flex-1 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        @error('text_color')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Приоритет</label>
                        <input type="number" id="priority" name="priority" value="{{ $val('priority', 0) }}" min="0" max="100"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <p class="admin-hint mt-1">Чем больше, тем выше в стопке (0–100).</p>
                        @error('priority')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Предпросмотр ──────────────────────────────────────────
                 Уведомление живёт на сайте, а собирают его здесь: без
                 предпросмотра приходилось сохранять, открывать сайт, смотреть,
                 возвращаться — и так на каждый подобранный цвет. --}}
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-eye text-indigo-500"></i> Как увидит посетитель
                    <span class="ml-auto normal-case font-normal text-[11px] text-gray-400"
                          x-text="position === 'top' ? 'сверху страницы' : (position === 'bottom' ? 'снизу страницы' : 'по центру экрана')"></span>
                </h2>

                <div class="p-6" style="background:repeating-linear-gradient(45deg,#f8fafc,#f8fafc 10px,#f1f5f9 10px,#f1f5f9 20px)">
                    <div class="mx-auto" style="max-width:560px; padding:18px 20px; border:1px solid rgba(17,24,39,.1);
                                                box-shadow:0 18px 40px -18px rgba(17,24,39,.45);"
                         :style="`background:${bg}; color:${fg}`">
                        <div class="flex items-center gap-2 mb-1.5" x-show="icon || title">
                            <span x-show="icon" x-text="icon" style="font-size:1.15rem; line-height:1"></span>
                            <strong x-show="title" x-text="title" style="font-size:.95rem"></strong>
                        </div>

                        <div class="notif-preview-text" style="font-size:.88rem; line-height:1.55" x-html="body"></div>

                        <div class="flex flex-wrap gap-2 mt-3.5" x-show="type === 'cookie'" x-cloak>
                            <span style="flex:1 1 auto; min-width:150px; padding:9px 16px; font-size:.85rem; font-weight:600;
                                         text-align:center; color:#fff; background:#4f46e5;">{{ __('frontend.consent.accept') }}</span>
                            <span style="flex:1 1 auto; min-width:150px; padding:9px 16px; font-size:.85rem; font-weight:600;
                                         text-align:center; color:#374151; background:#fff; border:1px solid #d1d5db;">{{ __('frontend.consent.essential') }}</span>
                        </div>
                    </div>
                </div>

                <p class="admin-hint mt-3" x-show="type === 'cookie'" x-cloak>
                    Кнопки показаны как есть — их рисует сам сайт, из панели они не правятся.
                </p>
            </div>
        </div>

        {{-- ── Показ ── --}}
        <div class="space-y-5">

            {{-- Публикация и сохранение наверху: за кнопкой не надо мотать
                 вниз через редактор и предпросмотр. Тумблер вместо галочки —
                 состояние читается сразу, подпись говорит, что оно значит. --}}
            <div class="admin-card p-5 space-y-4" x-data="{ live: {{ $enabled ? 'true' : 'false' }} }">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <i class="fas fa-circle-check text-indigo-500"></i> Публикация
                </h2>

                <div class="flex items-start gap-3">
                    <label class="admin-toggle mt-0.5">
                        <input type="checkbox" name="enabled" value="1" x-model="live" {{ $enabled ? 'checked' : '' }}>
                        <span class="track"></span>
                        <span class="knob"></span>
                    </label>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white"
                           x-text="live ? 'Показывается' : 'Выключено'"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"
                           x-text="live ? 'Посетители видят его на сайте' : 'Черновик — на сайте не появится'"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 flex-1 mt-3 bg-indigo-600 hover:bg-indigo-700 text-white
                                   px-4 py-2 text-sm font-semibold shadow-sm transition">
                        <i class="fas fa-save"></i> {{ $submitLabel }}
                    </button>
                    <a href="{{ route('admin.notifications.index') }}"
                       class="inline-flex items-center mt-3 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                              text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Отмена
                    </a>
                </div>
            </div>

            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-users text-indigo-500"></i> Кому и где
                </h2>

                <label for="target" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Аудитория</label>
                <select id="target" name="target"
                        class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="all" @selected($val('target', 'all') === 'all')>Все посетители</option>
                    <option value="admin" @selected($val('target') === 'admin')>Только администраторы</option>
                    <option value="user" @selected($val('target') === 'user')>Только авторизованные</option>
                </select>

                <label for="route_filter" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 mt-4">Страницы</label>
                <input type="text" id="route_filter" name="route_filter" value="{{ $val('route_filter') }}"
                       placeholder="пусто = на всех страницах"
                       class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <p class="admin-hint mt-1">
                    Оставьте пустым — покажем везде. Иначе путь: <span class="font-mono">/about</span>,
                    маска <span class="font-mono">/news/*</span> или несколько через запятую.
                </p>
                @error('route_filter')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-indigo-500"></i> Когда
                </h2>

                <label for="duration" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Скрыть через (сек)</label>
                <input type="number" id="duration" name="duration" value="{{ $val('duration', 0) }}" min="0"
                       class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <p class="admin-hint mt-1">0 — висит, пока посетитель не закроет.</p>
                @error('duration')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror

                <label for="starts_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 mt-4">Начало показа</label>
                <input type="datetime-local" id="starts_at" name="starts_at" value="{{ $dt('starts_at') }}"
                       class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

                <label for="ends_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 mt-4">Конец показа</label>
                <input type="datetime-local" id="ends_at" name="ends_at" value="{{ $dt('ends_at') }}"
                       class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                @error('ends_at')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                <p class="admin-hint mt-1">Пустые поля — без ограничения по датам.</p>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        // Состояние формы для предпросмотра. Текст берём у редактора: он живёт
        // в отдельном документе, и обычная привязка к полю его не видит.
        function notifForm(initial) {
            return Object.assign({}, initial, {
                body: '',

                init() {
                    const pull = () => {
                        const editor = window.RuEditor && window.RuEditor.get('editor');
                        if (!editor) { return false; }
                        this.body = editor.getContent();
                        editor.on('change', () => { this.body = editor.getContent(); });
                        return true;
                    };

                    // Редактор поднимается своим скриптом на DOMContentLoaded,
                    // и к этому моменту его может ещё не быть.
                    if (!pull()) {
                        const timer = setInterval(() => { if (pull()) { clearInterval(timer); } }, 200);
                        setTimeout(() => clearInterval(timer), 5000);
                    }
                },
            });
        }


        // Пипетка и текстовое поле цвета держатся друг за друга
        [['bg_color_picker', 'bg_color'], ['text_color_picker', 'text_color']].forEach(([pickerId, inputId]) => {
            const picker = document.getElementById(pickerId);
            const input = document.getElementById(inputId);
            if (!picker || !input) return;
            picker.addEventListener('input', () => { input.value = picker.value.toUpperCase(); });
            input.addEventListener('input', () => {
                if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) picker.value = input.value;
            });
        });
    </script>
@endpush
