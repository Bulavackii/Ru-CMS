{{--
    Конструктор каптчи.

    26.07.2026. До этого модуль был доступен только из кода — captcha_img() с
    массивом опций, — а его админская вьюха вообще не имела маршрута.

    Здесь: сборка мышью слева, живое превью справа (строится тем же
    CaptchaService::render(), что работает на сайте, — поэтому в превью видно
    ровно то, что получит посетитель), список сохранённых сборок с готовыми
    сниппетами для вставки.

    Примеры кода обёрнуты в директиву, отключающую компиляцию блока: без неё
    Blade ВЫПОЛНЯЕТ примеры, а не показывает их. Её имя, как и имена директив
    внутри примеров, нельзя писать здесь текстом — Blade компилирует и
    содержимое комментариев (см. CLAUDE.md, «Архитектурные грабли»).
--}}
@extends('layouts.admin')

@section('title', 'Каптча')

@section('content')
@php
    $typeMeta = [
        'image'    => ['title' => 'Картинка',  'icon' => 'fa-image',                'desc' => 'Код на картинке с помехами'],
        'slider'   => ['title' => 'Слайдер',   'icon' => 'fa-arrows-left-right',    'desc' => 'Дотащить ползунок до метки'],
        'math'     => ['title' => 'Пример',    'icon' => 'fa-calculator',           'desc' => 'Арифметика в уме'],
        'question' => ['title' => 'Вопрос',    'icon' => 'fa-circle-question',      'desc' => 'Вопрос со свободным ответом'],
    ];
@endphp

<div class="max-w-screen-2xl mx-auto"
     x-data="captchaBuilder(@js($defaults), @js($questions), @js($presets))">

    {{-- Шапка раздела --}}
    <div class="admin-card mb-5">
        <div class="admin-accent-bar" aria-hidden="true"></div>
        <div class="p-5 flex flex-wrap items-center gap-4">
            <span class="admin-icon-badge" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Каптча</h1>
                <p class="text-sm text-gray-500">
                    Соберите защиту формы, сохраните её и вставляйте в страницы, новости или свои шаблоны.
                </p>
            </div>
            <span class="cap-state {{ $enabled ? 'is-on' : 'is-off' }}">
                <span class="cap-dot" aria-hidden="true"></span>
                {{ $enabled ? 'Включена' : 'Выключена' }}
            </span>
        </div>
    </div>

    @unless($enabled)
        <div class="admin-hint p-4 mb-5 text-sm">
            Модуль выключен: <span class="font-mono">CAPTCHA_ENABLED=false</span> в <span class="font-mono">.env</span>.
            Собрать и сохранить сборку можно, но на формах проверка пропустит любое значение.
        </div>
    @endunless

    {{-- ═══ Конструктор ═══ --}}
    <form method="POST" :action="editing ? presetUrl(editing) : @js(route('admin.captcha.presets.store'))"
          class="grid gap-5 lg:grid-cols-2 items-start">
        @csrf
        <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

        {{-- Настройки --}}
        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400"
                    x-text="editing ? 'Редактирование сборки' : 'Новая сборка'"></h2>
                <button type="button" x-cloak x-show="editing" @click="resetForm()" class="cap-link">
                    <i class="fas fa-xmark"></i> Отменить
                </button>
            </div>

            <label class="cap-label">Название</label>
            <input type="text" name="name" x-model="name" required maxlength="255"
                   class="cap-input mb-4" placeholder="Например: Форма обратной связи">

            <label class="cap-label">Тип</label>
            <div class="grid grid-cols-2 gap-2 mb-4">
                @foreach($typeMeta as $key => $meta)
                    <label class="cap-pick" :class="type === @js($key) ? 'is-picked' : ''">
                        <input type="radio" name="type" value="{{ $key }}" x-model="type" class="sr-only">
                        <i class="fas {{ $meta['icon'] }} cap-pick-ico"></i>
                        <span class="min-w-0">
                            <span class="block font-semibold text-sm">{{ $meta['title'] }}</span>
                            <span class="block text-xs text-gray-500">{{ $meta['desc'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            {{-- Параметры. Показываются только те, которые сервис реально
                 читает для выбранного типа. --}}
            <div x-show="type === 'image'" x-cloak class="space-y-3">
                <div>
                    <label class="cap-label">Длина кода: <b x-text="options.image.length"></b></label>
                    <input type="range" name="length" min="3" max="10" x-model.number="options.image.length" class="cap-range">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="cap-label">Ширина: <b x-text="options.image.width"></b> px</label>
                        <input type="range" name="width" min="80" max="600" step="10" x-model.number="options.image.width" class="cap-range">
                    </div>
                    <div>
                        <label class="cap-label">Высота: <b x-text="options.image.height"></b> px</label>
                        <input type="range" name="height" min="30" max="200" step="5" x-model.number="options.image.height" class="cap-range">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="cap-label">Шум: <b x-text="options.image.noise"></b></label>
                        <input type="range" name="noise" min="0" max="3" x-model.number="options.image.noise" class="cap-range">
                    </div>
                    <div>
                        <label class="cap-label">Линии: <b x-text="options.image.lines"></b></label>
                        <input type="range" name="lines" min="0" max="10" x-model.number="options.image.lines" class="cap-range">
                    </div>
                </div>
            </div>

            <div x-show="type === 'slider'" x-cloak class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="cap-label">Ширина: <b x-text="options.slider.width"></b> px</label>
                        <input type="range" name="width" min="160" max="600" step="10" x-model.number="options.slider.width" class="cap-range">
                    </div>
                    <div>
                        <label class="cap-label">Высота: <b x-text="options.slider.height"></b> px</label>
                        <input type="range" name="height" min="32" max="80" step="2" x-model.number="options.slider.height" class="cap-range">
                    </div>
                </div>
                <div>
                    <label class="cap-label">Допуск попадания: <b x-text="options.slider.tolerance"></b> px</label>
                    <input type="range" name="tolerance" min="4" max="40" x-model.number="options.slider.tolerance" class="cap-range">
                    <p class="cap-note">Меньше допуск — строже проверка, но труднее живому человеку.</p>
                </div>
            </div>

            <div x-show="type === 'math'" x-cloak class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="cap-label">Числа от</label>
                        <input type="number" name="min" min="0" max="999" x-model.number="options.math.min" class="cap-input">
                    </div>
                    <div>
                        <label class="cap-label">до</label>
                        <input type="number" name="max" min="1" max="1000" x-model.number="options.math.max" class="cap-input">
                    </div>
                </div>
                <div>
                    <label class="cap-label">Действия</label>
                    <div class="flex gap-2">
                        <template x-for="op in ['+', '-', '*']" :key="op">
                            <label class="cap-op" :class="options.math.operations.includes(op) ? 'is-picked' : ''">
                                <input type="checkbox" name="operations[]" :value="op"
                                       x-model="options.math.operations" class="sr-only">
                                <span x-text="op"></span>
                            </label>
                        </template>
                    </div>
                    <p class="cap-note">Умножение сервис ограничивает числами до 12 — иначе пример не решить в уме.</p>
                </div>
            </div>

            <div x-show="type === 'question'" x-cloak class="space-y-3">
                <label class="cap-label">Свои вопросы</label>
                <template x-for="(pair, index) in options.question.questions" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="text" :name="'questions[' + index + '][q]'" x-model="pair.q"
                               class="cap-input flex-1" placeholder="Вопрос">
                        <input type="text" :name="'questions[' + index + '][a]'" x-model="pair.a"
                               class="cap-input" style="max-width:9rem" placeholder="Ответ">
                        <button type="button" @click="options.question.questions.splice(index, 1)"
                                class="cap-icon-btn" aria-label="Удалить"><i class="fas fa-trash"></i></button>
                    </div>
                </template>
                <button type="button" @click="options.question.questions.push({q: '', a: ''})" class="cap-link">
                    <i class="fas fa-plus"></i> Добавить вопрос
                </button>
                <p class="cap-note">
                    Если не добавить ни одного, будет использован набор по умолчанию
                    (<span x-text="defaultQuestions.length"></span> шт.). Ответ сверяется без учёта регистра.
                </p>
            </div>

            <label class="flex items-center gap-2 mt-4 text-sm">
                <span class="admin-toggle">
                    <input type="checkbox" name="is_active" value="1" x-model="isActive">
                    <span class="track"></span><span class="knob"></span>
                </span>
                <span>Активна — доступна для вставки в материалы</span>
            </label>

            <div class="flex flex-wrap gap-2 mt-5">
                <button type="submit" class="cap-btn">
                    <i class="fas fa-floppy-disk"></i>
                    <span x-text="editing ? 'Сохранить изменения' : 'Сохранить сборку'"></span>
                </button>
                <button type="button" @click="refresh()" class="cap-btn cap-btn--ghost">
                    <i class="fas fa-rotate"></i> Обновить превью
                </button>
            </div>
        </section>

        {{-- Превью --}}
        <section class="admin-card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Как увидит посетитель</h2>

            <div class="cap-preview">
                <div x-show="loading" class="text-xs text-gray-400">собираем…</div>
                <div x-show="!loading" x-html="preview"></div>
            </div>

            <p class="cap-note mt-3">
                Это не картинка-макет, а настоящая каптча: она собрана тем же кодом, что работает на сайте.
                Ответ проверяется на сервере — в разметку он не попадает.
            </p>

            <div x-cloak x-show="error" class="admin-hint p-3 mt-3 text-xs" x-text="error"></div>
        </section>
    </form>

    {{-- ═══ Сохранённые сборки ═══ --}}
    <section class="admin-card p-5 mt-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Сохранённые сборки</h2>

        @forelse($presets as $preset)
            @php $meta = $typeMeta[$preset->type] ?? ['title' => $preset->type, 'icon' => 'fa-shield-halved']; @endphp
            <div class="cap-preset">
                <span class="cap-type-ico"><i class="fas {{ $meta['icon'] }}"></i></span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <strong class="text-gray-900 dark:text-white">{{ $preset->name }}</strong>
                        <code class="cap-code">{{ $meta['title'] }}</code>
                        @if(! $preset->is_active)
                            <span class="cap-off">выключена</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        {{-- Копирование через @js(): @json внутри onclick рвёт
                             атрибут на первой же двойной кавычке --}}
                        <button type="button" class="cap-copy"
                                onclick="navigator.clipboard.writeText(@js($preset->shortcode())).then(() => window.toast && toast('Шорткод скопирован'))">
                            <i class="fa-regular fa-copy"></i>
                            <code>{{ $preset->shortcode() }}</code>
                        </button>

                        <button type="button" class="cap-copy"
                                onclick="navigator.clipboard.writeText(@js($preset->bladeSnippet())).then(() => window.toast && toast('Вызов для шаблона скопирован'))">
                            <i class="fa-regular fa-copy"></i> для шаблона
                        </button>

                        <button type="button" class="cap-copy"
                                onclick="navigator.clipboard.writeText(@js($preset->verifySnippet())).then(() => window.toast && toast('Проверка скопирована'))">
                            <i class="fa-regular fa-copy"></i> проверка в контроллере
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 flex-none">
                    <button type="button" class="cap-icon-btn" title="Редактировать"
                            @click="edit(@js([
                                'id' => $preset->id,
                                'name' => $preset->name,
                                'type' => $preset->type,
                                'options' => (array) $preset->options,
                                'is_active' => (bool) $preset->is_active,
                            ]))">
                        <i class="fas fa-pen"></i>
                    </button>

                    <form method="POST" action="{{ route('admin.captcha.presets.duplicate', $preset) }}">
                        @csrf
                        <button type="submit" class="cap-icon-btn" title="Дублировать"><i class="fa-regular fa-clone"></i></button>
                    </form>

                    <form method="POST" action="{{ route('admin.captcha.presets.destroy', $preset) }}"
                          onsubmit="return confirm('Удалить сборку «{{ $preset->name }}»? Вставленные шорткоды перестанут что-либо показывать.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="cap-icon-btn cap-icon-btn--danger" title="Удалить"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                Пока ничего не сохранено. Соберите каптчу выше и нажмите «Сохранить сборку» —
                после этого её можно будет вставить в страницу или новость одним выбором из списка.
            </p>
        @endforelse
    </section>

    {{-- ═══ Памятка ═══ --}}
    <section class="admin-card p-5 mt-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Как встроить</h2>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <h3 class="cap-h3">1. В текст страницы или новости</h3>
                <p class="cap-note mb-2">Вставьте шорткод — или выберите сборку из списка прямо в редакторе.</p>
                <pre class="cap-pre">@verbatim
[captcha preset="obratnaya-svyaz"]
@endverbatim</pre>
            </div>

            <div>
                <h3 class="cap-h3">2. В свой Blade-шаблон</h3>
                <pre class="cap-pre">@verbatim
<form method="POST" action="/feedback">
    @csrf
    {!! captcha_preset('obratnaya-svyaz') !!}
    <button type="submit">Отправить</button>
</form>
@endverbatim</pre>
            </div>

            <div>
                <h3 class="cap-h3">3. Проверка в контроллере</h3>
                <p class="cap-note mb-2">
                    Без параметра: тип берётся из самой сборки. Так две каптчи на одной странице
                    не мешают друг другу.
                </p>
                <pre class="cap-pre">@verbatim
$request->validate([
    'captcha' => 'required|captcha',
]);
@endverbatim</pre>
            </div>

            <div>
                <h3 class="cap-h3">4. Без сохранённой сборки</h3>
                <pre class="cap-pre">@verbatim
{!! captcha_field('math', ['min' => 1, 'max' => 20]) !!}
@endverbatim</pre>
                <p class="cap-note mt-2">
                    Старый <code>captcha_img()</code> тоже работает, но выводит только картинку,
                    без поля ответа и идентификатора.
                </p>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    /* Литеральный CSS: в собранном tailwind.min.css нет ни произвольных
       значений, ни opacity-модификаторов, ни dark:-вариантов (см. CLAUDE.md). */
    .cap-state{display:inline-flex;align-items:center;gap:.45rem;padding:.3rem .7rem;flex:none;
        font-size:.75rem;font-weight:600;border:1px solid}
    .cap-state.is-on{color:#166534;background:#dcfce7;border-color:#86efac}
    .cap-state.is-off{color:#991b1b;background:#fee2e2;border-color:#fca5a5}
    .cap-dot{width:.45rem;height:.45rem;border-radius:999px;background:currentColor}
    .dark .cap-state.is-on{color:#86efac;background:rgba(22,101,52,.25);border-color:#166534}
    .dark .cap-state.is-off{color:#fca5a5;background:rgba(153,27,27,.25);border-color:#991b1b}

    .cap-label{display:block;font-size:.72rem;font-weight:600;color:#6b7280;margin-bottom:.25rem}
    .dark .cap-label{color:#9ca3af}
    .cap-input{width:100%;padding:.4rem .6rem;font-size:.85rem;color:#111827;background:#fff;
        border:1px solid #d1d5db;outline:none}
    .dark .cap-input{color:#e5e7eb;background:#111827;border-color:#374151}
    .cap-input:focus{border-color:var(--admin-primary,#6366f1);
        box-shadow:0 0 0 3px var(--admin-primary-soft,rgba(99,102,241,.2))}
    .cap-range{width:100%;accent-color:var(--admin-primary,#6366f1)}
    .cap-note{font-size:.7rem;color:#9ca3af;line-height:1.45;margin-top:.3rem}

    /* Выбор типа */
    .cap-pick{display:flex;align-items:center;gap:.6rem;padding:.6rem;cursor:pointer;
        border:1px solid #e5e7eb;transition:border-color .15s ease,background .15s ease}
    .dark .cap-pick{border-color:#374151}
    .cap-pick:hover{border-color:var(--admin-primary,#6366f1)}
    .cap-pick.is-picked{border-color:var(--admin-primary,#6366f1);
        background:var(--admin-primary-soft,rgba(99,102,241,.1))}
    .cap-pick-ico{width:1.5rem;text-align:center;color:var(--admin-primary,#6366f1)}

    .cap-op{display:grid;place-items:center;width:2.5rem;height:2.25rem;cursor:pointer;
        font-size:1rem;font-weight:700;border:1px solid #e5e7eb;color:#6b7280}
    .dark .cap-op{border-color:#374151}
    .cap-op.is-picked{color:var(--admin-on-primary,#fff);background:var(--admin-primary,#6366f1);
        border-color:var(--admin-primary,#6366f1)}

    .cap-preview{display:flex;align-items:center;justify-content:center;min-height:11rem;padding:1.25rem;
        border:1px dashed #d1d5db;background:#fff}
    .dark .cap-preview{border-color:#4b5563;background:#0f172a}

    .cap-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;
        font-size:.8rem;font-weight:600;color:var(--admin-on-primary,#fff);border:1px solid transparent;
        cursor:pointer;background:var(--admin-primary,#6366f1);transition:filter .15s ease}
    .cap-btn:hover{filter:brightness(1.1)}
    .cap-btn--ghost{color:#4b5563;background:transparent;border-color:#d1d5db}
    .dark .cap-btn--ghost{color:#d1d5db;border-color:#374151}
    .cap-btn--ghost:hover{background:rgba(17,24,39,.05);filter:none}

    .cap-link{display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;font-weight:600;
        color:var(--admin-primary,#4f46e5);background:none;border:0;cursor:pointer}
    .cap-link:hover{text-decoration:underline}

    /* Сохранённые сборки */
    .cap-preset{display:flex;align-items:flex-start;gap:.8rem;padding:.75rem;margin-bottom:.5rem;
        border:1px solid #e5e7eb}
    .dark .cap-preset{border-color:#374151}
    .cap-preset:hover{border-color:var(--admin-primary,#6366f1)}
    .cap-type-ico{display:grid;place-items:center;width:2.25rem;height:2.25rem;flex:none;
        color:var(--admin-on-primary,#fff);
        background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7))}
    .cap-code{padding:.05rem .35rem;font-size:.7rem;color:#4b5563;background:rgba(17,24,39,.06);
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
    .dark .cap-code{color:#d1d5db;background:rgba(255,255,255,.08)}
    .cap-off{padding:.05rem .35rem;font-size:.62rem;font-weight:700;color:#92400e;background:#fef3c7}
    .cap-copy{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .45rem;font-size:.7rem;
        color:#6b7280;background:none;border:1px solid #e5e7eb;cursor:pointer}
    .dark .cap-copy{color:#9ca3af;border-color:#374151}
    .cap-copy:hover{color:var(--admin-primary,#4f46e5);border-color:var(--admin-primary,#6366f1)}
    .cap-copy code{font-size:.7rem}
    .cap-icon-btn{display:grid;place-items:center;width:2rem;height:2rem;flex:none;color:#6b7280;
        background:none;border:1px solid #e5e7eb;cursor:pointer;transition:color .15s ease,border-color .15s ease}
    .dark .cap-icon-btn{border-color:#374151;color:#9ca3af}
    .cap-icon-btn:hover{color:var(--admin-primary,#4f46e5);border-color:var(--admin-primary,#6366f1)}
    .cap-icon-btn--danger:hover{color:#dc2626;border-color:#dc2626}

    .cap-h3{font-size:.78rem;font-weight:700;color:#374151;margin-bottom:.4rem}
    .dark .cap-h3{color:#d1d5db}
    .cap-pre{margin:0;padding:.75rem;font-size:.72rem;line-height:1.5;overflow-x:auto;
        color:#e5e7eb;background:#111827;border:1px solid #374151;
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
</style>
@endpush

@push('scripts')
<script>
function captchaBuilder(defaults, defaultQuestions, presets) {
    return {
        defaults,
        defaultQuestions,
        presets,
        editing: null,
        name: '',
        type: 'image',
        isActive: true,
        preview: '',
        loading: false,
        error: '',
        timer: null,

        // Параметры всех типов держим рядом: переключение типа не должно
        // терять то, что уже настроено в соседнем
        options: {
            image: {},
            slider: {},
            math: {},
            question: { questions: [] },
        },

        init() {
            this.resetForm();

            // Любое изменение — перерисовать превью. Debounce, чтобы
            // перетаскивание ползунка не слало запрос на каждый пиксель.
            this.$watch('type', () => this.schedule());
            this.$watch('options', () => this.schedule(), { deep: true });
        },

        presetUrl(id) {
            return @js(route('admin.captcha.presets.update', ['preset' => '__ID__'])).replace('__ID__', id);
        },

        resetForm() {
            this.editing = null;
            this.name = '';
            this.type = 'image';
            this.isActive = true;
            this.options = {
                image: { ...this.defaults.image },
                slider: { ...this.defaults.slider },
                math: { ...this.defaults.math, operations: [...this.defaults.math.operations] },
                question: { questions: [] },
            };
            this.schedule();
        },

        edit(preset) {
            this.resetForm();
            this.editing = preset.id;
            this.name = preset.name;
            this.type = preset.type;
            this.isActive = preset.is_active;

            // Пресет хранит только параметры своего типа — остальные остаются
            // значениями по умолчанию
            Object.assign(this.options[preset.type], preset.options || {});
            if (preset.type === 'question' && !Array.isArray(this.options.question.questions)) {
                this.options.question.questions = [];
            }

            this.schedule();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        schedule() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.refresh(), 250);
        },

        async refresh() {
            this.loading = true;
            this.error = '';

            const body = new FormData();
            body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            body.append('type', this.type);

            const current = this.options[this.type] || {};
            for (const [key, value] of Object.entries(current)) {
                if (Array.isArray(value)) {
                    value.forEach((item, index) => {
                        if (key === 'questions') {
                            body.append(`questions[${index}][q]`, item.q ?? '');
                            body.append(`questions[${index}][a]`, item.a ?? '');
                        } else {
                            body.append(`${key}[]`, item);
                        }
                    });
                } else if (value !== null && value !== undefined && value !== '') {
                    body.append(key, value);
                }
            }

            try {
                const response = await fetch(@js(route('admin.captcha.preview')), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });

                if (!response.ok) throw new Error('сервер ответил ' + response.status);

                const data = await response.json();
                this.preview = data.html || '';

                // Слайдер оживает только после привязки обработчиков —
                // разметка сама по себе не перетаскивается
                this.$nextTick(() => window.captchaBindSliders && window.captchaBindSliders());
            } catch (e) {
                this.preview = '';
                this.error = 'Не удалось собрать превью: ' + (e.message || e);
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
