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

<form method="POST" action="{{ $action }}" id="notificationForm">
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
                        <input type="text" id="title" name="title" value="{{ $val('title') }}" required
                               placeholder="Например: Плановые технические работы"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        @error('title')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="icon" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Иконка</label>
                        <input type="text" id="icon" name="icon" value="{{ $val('icon', '🔔') }}"
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
                    <textarea name="message" id="editor" rows="8"
                              class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                              placeholder="Что нужно сообщить посетителю">{{ $val('message') }}</textarea>
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
                        <select id="type" name="type"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="text" @selected($val('type', 'text') === 'text')>Текст — разметка вырезается</option>
                            <option value="html" @selected($val('type') === 'html')>HTML — ссылки и форматирование</option>
                            <option value="cookie" @selected($val('type') === 'cookie')>Одноразовое — больше не покажем</option>
                        </select>
                        <p class="admin-hint mt-1">«Одноразовое» запоминается в браузере после закрытия.</p>
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Позиция</label>
                        <select id="position" name="position"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="top" @selected($val('position', 'top') === 'top')>Сверху</option>
                            <option value="bottom" @selected($val('position') === 'bottom')>Снизу</option>
                            <option value="fullscreen" @selected($val('position') === 'fullscreen')>По центру экрана</option>
                        </select>
                    </div>

                    <div>
                        <label for="cookie_key" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Ключ cookie</label>
                        <input type="text" id="cookie_key" name="cookie_key" value="{{ $val('cookie_key') }}"
                               placeholder="welcome_notice"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <p class="admin-hint mt-1">Только для типа «одноразовое». Пусто — ключ по id.</p>
                        @error('cookie_key')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="bg_color" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Цвет фона</label>
                        <div class="flex gap-2">
                            <input type="color" id="bg_color_picker" value="{{ $val('bg_color', '#EEF2FF') }}"
                                   class="w-10 h-9 border border-gray-300 dark:border-gray-700 p-0 cursor-pointer">
                            <input type="text" id="bg_color" name="bg_color" value="{{ $val('bg_color', '#EEF2FF') }}"
                                   placeholder="#EEF2FF"
                                   class="flex-1 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        @error('bg_color')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="text_color" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Цвет текста</label>
                        <div class="flex gap-2">
                            <input type="color" id="text_color_picker" value="{{ $val('text_color', '#111827') }}"
                                   class="w-10 h-9 border border-gray-300 dark:border-gray-700 p-0 cursor-pointer">
                            <input type="text" id="text_color" name="text_color" value="{{ $val('text_color', '#111827') }}"
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
        </div>

        {{-- ── Показ ── --}}
        <div class="space-y-5">
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

                <label class="flex items-center gap-3 cursor-pointer mt-4">
                    <span class="admin-toggle">
                        <input type="checkbox" name="enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                        <span class="track"></span>
                        <span class="knob"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Включено</span>
                </label>
            </div>

            <div class="admin-card p-4 flex items-center gap-2">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               px-4 py-2 text-sm font-semibold shadow-sm transition flex-1">
                    <i class="fas fa-save"></i> {{ $submitLabel }}
                </button>
                <a href="{{ route('admin.notifications.index') }}"
                   class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800
                          px-4 py-2 text-sm font-semibold transition">
                    Отмена
                </a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
    <script>
        tinymce.init({
            // Иконка — это пустой тег <i class="fas ...">, а редактор по
            // умолчанию вычищает пустые инлайновые элементы: значок молча
            // исчезал при первом же сохранении, вместе с обёрткой вокруг него.
            // Проверено на живом редакторе — без этой строки не выживает.
            extended_valid_elements: 'i[class|aria-hidden],span[class|style]',
            selector: '#editor',
            language: 'ru',
            language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
            height: 320,
            branding: false,
            license_key: 'gpl',
            convert_urls: false,
            plugins: 'link lists code',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link | code | removeformat',
        });

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
