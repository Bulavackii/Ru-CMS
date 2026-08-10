@extends('layouts.admin')

@section('content')
    @php
        $base = rtrim((string) config('app.url'), '/');
        $viewUrl = !empty($item->canonical) ? $item->canonical : $base . '/' . ltrim((string) $item->slug, '/');
    @endphp

    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-magnifying-glass-chart"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.seo.page_seo') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono truncate">{{ $item->slug }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ $viewUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-arrow-up-right-from-square"></i> Открыть
            </a>
            <a href="{{ route('seo.pages.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-arrow-left"></i> {{ __('admin.seo.back') }}
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="admin-card border-l-4 border-indigo-500 p-4 mb-5 text-sm text-gray-800 dark:text-gray-200">
            <i class="fas fa-circle-info text-indigo-500 mr-1"></i> {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
            <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- 🔒/🔓 Lock banner --}}
    @if (!empty($item->locked))
        <div role="alert" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="border-l-4 border-amber-500 pl-3">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-600">@themeIcon('lock', 'text-lg')</span>
                        <span class="font-semibold">{{ __('admin.seo.locked_title') }}</span>
                    </div>
                    <p class="text-sm opacity-90 mt-1">
                        Эта запись помечена как <b>locked</b>{{ __('admin.seo.locked_note') }}
                    </p>
                    <form method="POST" action="{{ route('seo.pages.unlock', $item->id) }}" class="mt-3">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md
                               bg-indigo-600 hover:bg-indigo-700 text-white
                               shadow-sm ring-1 ring-inset ring-amber-700/10
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-600">
                            @themeIcon('unlock')
                            {{ __('admin.seo.unlock') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div role="alert" class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-900 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="border-l-4 border-emerald-500 pl-3">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600">@themeIcon('unlock', 'text-lg')</span>
                        <span class="font-semibold">{{ __('admin.seo.unlocked_title') }}</span>
                    </div>
                    <p class="text-sm opacity-90 mt-1">
                        {{ __('admin.seo.unlocked_note') }}
                    </p>
                    <form method="POST" action="{{ route('seo.pages.lock', $item->id) }}" class="mt-3">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md
                               bg-indigo-600 hover:bg-indigo-700 text-white
                               shadow-sm ring-1 ring-inset ring-emerald-700/10
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-600">
                            @themeIcon('lock')
                            Заблокировать
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Левая зона: форма редактирования --}}
        <div class="lg:col-span-2 space-y-5">
            <form method="post" action="{{ route('seo.pages.update', $item->id) }}">
                @csrf
                @method('PUT')

                {{-- Slug --}}
                <div>
                    <label class="seo-label">Slug
                        <span class="seo-hint">{{ __('admin.seo.path_or_url') }}</span>
                    </label>
                    <input name="slug" value="{{ old('slug', $item->slug) }}" class="seo-input"
                        maxlength="1024" placeholder="{{ __('admin.seo.url_ph') }}">
                    <p class="seo-hint">
                        {{ __('admin.seo.slug_note') }}
                    </p>
                    @error('slug')
                        <div class="seo-err">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Title / H1 --}}
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="seo-label">Title</label>
                        <input name="title" value="{{ old('title', $item->title) }}"
                            class="seo-input js-count" data-limit="60" maxlength="255"
                            placeholder="{{ __('admin.seo.title_ph') }}">
                        <div class="seo-hint">
                            Рекомендуем до 60 символов. <span class="js-count-out"></span>
                        </div>
                        @error('title')
                            <div class="seo-err">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="seo-label">H1</label>
                        <input name="h1" value="{{ old('h1', $item->h1) }}" class="seo-input"
                            maxlength="255" placeholder="{{ __('admin.seo.h1_ph') }}">
                        @error('h1')
                            <div class="seo-err">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="seo-label">Description</label>
                    <textarea name="description" rows="2" class="seo-input js-count" data-limit="160"
                        maxlength="255" placeholder="{{ __('admin.seo.desc_ph') }}">{{ old('description', $item->description) }}</textarea>
                    <div class="seo-hint">
                        Лучше до 160 символов. <span class="js-count-out"></span>
                    </div>
                    @error('description')
                        <div class="seo-err">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Keywords --}}
                <div>
                    <label class="seo-label">Keywords
                        <span class="seo-hint">{{ __('admin.seo.comma_separated') }}</span>
                    </label>
                    <input name="keywords" value="{{ old('keywords', $item->keywords) }}"
                        class="seo-input js-count" data-limit="255" maxlength="255"
                        placeholder="{{ __('admin.seo.keywords_ph') }}">
                    <div class="seo-hint">
                        {{ __('admin.seo.optional') }} <span class="js-count-out"></span>
                    </div>
                    @error('keywords')
                        <div class="seo-err">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Canonical --}}
                <div>
                    <label class="seo-label">Canonical
                        <span class="seo-hint">{{ __('admin.seo.can_be_relative') }}</span>
                    </label>
                    <input name="canonical" value="{{ old('canonical', $item->canonical) }}"
                        class="seo-input" maxlength="1024"
                        placeholder="{{ __('admin.seo.url_ph') }}">
                    <div class="seo-hint">
                        {{ __('admin.seo.no_canonical') }}
                    </div>
                    @error('canonical')
                        <div class="seo-err">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Robots --}}
                <div class="grid md:grid-cols-2 gap-4">
                    {{-- Тумблеры вместо галочек: тот же элемент, что в «Меню»,
                         «Слайдшоу» и «Категориях». Имена полей и скрытые нули
                         оставлены как были. Классы js-robots-* добавлены, чтобы
                         строка итоговой директивы менялась сразу, а не только
                         после сохранения — на форме создания так уже было. --}}
                    <div>
                        <input type="hidden" name="robots_index" value="0">
                        <label class="seo-switch">
                            <span class="admin-toggle">
                                <input type="checkbox" name="robots_index" value="1" class="js-robots-index"
                                    {{ old('robots_index', $item->robots_index) ? 'checked' : '' }}>
                                <span class="track"></span><span class="knob"></span>
                            </span>
                            <span class="seo-switch__body">
                                <span class="seo-switch__title">index</span>
                                <span class="seo-hint">{{ __('admin.seo.allow_indexing') }}</span>
                            </span>
                        </label>
                        @error('robots_index')
                            <div class="seo-err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <input type="hidden" name="robots_follow" value="0">
                        <label class="seo-switch">
                            <span class="admin-toggle">
                                <input type="checkbox" name="robots_follow" value="1" class="js-robots-follow"
                                    {{ old('robots_follow', $item->robots_follow) ? 'checked' : '' }}>
                                <span class="track"></span><span class="knob"></span>
                            </span>
                            <span class="seo-switch__body">
                                <span class="seo-switch__title">follow</span>
                                <span class="seo-hint">{{ __('admin.seo.allow_follow') }}</span>
                            </span>
                        </label>
                        @error('robots_follow')
                            <div class="seo-err">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ⚠️ Здесь стоял отрицательный отступ `-mt-2`: строка с итоговой
                     директивой заезжала на подсказку «Разрешить индексирование
                     страницы», и обе читались друг сквозь друга. --}}
                <div class="seo-directive">
                    {{ __('admin.seo.directive') }} <code id="robotsPreview">{{ old('robots_index', $item->robots_index) ? 'index' : 'noindex' }}, {{ old('robots_follow', $item->robots_follow) ? 'follow' : 'nofollow' }}</code>
                </div>

                {{-- OG / Twitter --}}
                <div class="border rounded p-3 space-y-2">
                    <div class="text-sm font-semibold">OG / Twitter</div>
                    <input name="og_title" value="{{ old('og_title', $item->og['og:title'] ?? '') }}"
                        class="seo-input" maxlength="255" placeholder="og:title">
                    <input name="og_description" value="{{ old('og_description', $item->og['og:description'] ?? '') }}"
                        class="seo-input" maxlength="512" placeholder="og:description">
                    <input name="og_image" value="{{ old('og_image', $item->og['og:image'] ?? '') }}"
                        class="seo-input" maxlength="1024" placeholder="og:image (URL)">
                    <input name="twitter_card" value="{{ old('twitter_card', $item->og['twitter:card'] ?? '') }}"
                        class="seo-input" maxlength="50"
                        placeholder="twitter:card (summary / summary_large_image)">
                    <input name="twitter_title" value="{{ old('twitter_title', $item->og['twitter:title'] ?? '') }}"
                        class="seo-input" maxlength="255" placeholder="twitter:title">
                    <input name="twitter_description"
                        value="{{ old('twitter_description', $item->og['twitter:description'] ?? '') }}"
                        class="seo-input" maxlength="512" placeholder="twitter:description">
                    <input name="twitter_image" value="{{ old('twitter_image', $item->og['twitter:image'] ?? '') }}"
                        class="seo-input" maxlength="1024" placeholder="twitter:image (URL)">
                    <p class="seo-hint">{{ __('admin.seo.empty_no_overwrite') }}</p>
                </div>

                {{-- JSON-LD --}}
                <div>
                    <label class="seo-label">JSON-LD</label>
                    <textarea name="jsonld_raw" rows="8" class="mt-1 w-full border p-2 rounded font-mono"
                        placeholder='{"@@context":"https://schema.org","@@type":"Article",...}'>{{ old('jsonld_raw', isset($item->jsonld) ? json_encode($item->jsonld, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
                    <div class="flex items-center justify-between mt-1">
                        <div class="seo-hint">Сохраняем только валидный JSON.</div>
                        <button type="button"
                            class="px-2 py-1 text-xs border rounded js-json-pretty">{{ __('admin.seo.format') }}</button>
                    </div>
                    @error('jsonld_raw')
                        <div class="seo-err">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Кнопка Сохранить --}}
                <div class="flex flex-wrap items-center gap-3">
                    <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">Сохранить</button>
                    <a href="{{ $viewUrl }}" target="_blank" rel="noopener"
                        class="px-4 py-2 border rounded hover:bg-gray-50">Открыть страницу</a>
                    <button type="button" class="px-4 py-2 border rounded hover:bg-gray-50"
                        title="Скопировать URL страницы" data-url="{{ $viewUrl }}"
                        onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='Скопировано'; setTimeout(()=>this.textContent='Копировать URL',1500); });">
                        Копировать URL
                    </button>
                </div>
            </form>

            {{-- ВНЕ основной формы: отдельные действия, чтобы не было вложенных форм --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Пересинхронизация одной записи --}}
                <form method="post" action="{{ route('seo.pages.refresh', $item->id) }}"
                    onsubmit="return confirm('Пересинхронизировать страницу из источника без перезаписи ваших ручных правок?');">
                    @csrf
                    <button class="px-4 py-2 border rounded hover:bg-gray-50">{{ __('admin.seo.resync') }}</button>
                </form>

                {{-- Удаление --}}
                <form action="{{ route('seo.pages.destroy', $item->id) }}" method="post"
                    onsubmit="return confirm('Удалить эту SEO-запись? Если включён автосинк из источников, она может появиться снова.');">
                    @csrf @method('DELETE')
                    <button class="px-4 py-2 bg-red-600 text-white rounded">{{ __('admin.delete') }}</button>
                </form>

                <a href="{{ route('seo.pages.index') }}" class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 text-sm font-semibold transition">{{ __('admin.seo.back') }}</a>
            </div>
        </div>

        {{-- Правая колонка: подсказки и мета-инфо --}}
        <aside class="space-y-3">
            <div class="p-3 rounded border bg-white">
                <div class="font-semibold mb-1">Сведения</div>
                <div class="text-sm space-y-1">
                    <div><span class="text-gray-500">ID:</span> {{ $item->id }}</div>
                    @if ($item->updated_at)
                        <div><span class="text-gray-500">Обновлено:</span> {{ $item->updated_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if (!empty($item->source_type))
                        <div><span class="text-gray-500">Источник:</span> {{ $item->source_type }}@if ($item->source_id)
                                #{{ $item->source_id }}
                            @endif
                        </div>
                    @endif
                    @php $manualCount = is_array($item->manual_fields ?? null) ? count($item->manual_fields) : 0; @endphp
                    <div>
                        <span class="text-gray-500">Ручные поля:</span>
                        {{ $manualCount > 0 ? $manualCount : '—' }}
                    </div>
                    @if (!empty($item->locked))
                        <div><span class="text-gray-500">Статус:</span> locked</div>
                    @endif
                </div>
            </div>

            <div class="p-3 rounded border bg-white">
                <div class="font-semibold mb-1">{{ __('admin.seo.hints') }}</div>
                <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
                    <li><strong>Title</strong> — заголовок сниппета (до ~60).</li>
                    <li><strong>H1</strong> — заголовок на странице.</li>
                    <li><strong>Description</strong> — краткое описание (до ~160).</li>
                    <li><strong>Canonical</strong> — укажите, если есть дубликаты.</li>
                    <li><strong>index/follow</strong> — индексация страницы и ссылок.</li>
                    <li>Кнопка <strong>{{ __('admin.seo.resync') }}</strong> подтянет данные из источника (Новости/Страницы) не
                        перезаписывая ваши ручные поля.</li>
                </ul>
            </div>

            @if (Route::has('seo.sitemaps.rebuild'))
                <form method="post" action="{{ route('seo.sitemaps.rebuild') }}" class="p-3 rounded border bg-white">
                    @csrf
                    <div class="font-semibold mb-2">Sitemap</div>
                    <button
                        class="px-3 py-2 rounded border border-sky-700 text-sky-700 bg-white hover:bg-sky-50 transition w-full">
                        {{ __('admin.seo.rebuild_sitemap') }}
                    </button>
                    <div class="text-xs text-gray-500 mt-2">
                        Если очередь не настроена, пересборка выполнится синхронно (зависит от конфигурации).
                    </div>
                </form>
            @endif
        </aside>
    </div>

    {{-- Мини-скрипты UX --}}
    <script>
        document.querySelectorAll('.js-count').forEach(function(el) {
            const out = el.parentElement.querySelector('.js-count-out');
            const lim = parseInt(el.dataset.limit || '0', 10);
            const apply = () => {
                const len = (el.value || '').length;
                if (!out) return;
                out.textContent = lim ? ` • ${len}/${lim}` : ` • ${len}`;
                out.className = 'js-count-out ' + (lim && len > lim ? 'text-red-600' : 'text-gray-400');
            };
            el.addEventListener('input', apply);
            apply();
        });

        document.querySelectorAll('.js-json-pretty').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const wrap = this.closest('div').previousElementSibling ? this.closest('div')
                    .previousElementSibling : null;
                const ta = wrap && wrap.tagName === 'TEXTAREA' ? wrap : document.querySelector(
                    'textarea[name="jsonld_raw"]');
                if (!ta) return;
                try {
                    const parsed = JSON.parse(ta.value || '{}');
                    ta.value = JSON.stringify(parsed, null, 2);
                } catch (e) {
                    alert(@js(__('admin.seo.bad_json')));
                }
            });
        });
    </script>
@endsection

@push('styles')
<style>
    /* ── Раздел SEO: поля ─────────────────────────────────────────────
       Литеральный CSS: в сборке проекта нет ни прозрачности через дробь,
       ни произвольных значений; скругления сняты общим рубильником
       `body.admin-sharp`, поэтому классы `rounded` тут ничего не делали. */

    .seo-label{ display:block; margin-bottom:.3rem; font-size:.8rem; font-weight:600; color:#374151 }
    .seo-label span{ font-weight:400; color:#9ca3af }
    .dark .seo-label{ color:#d1d5db }

    .seo-input{ display:block; width:100%; padding:.5rem .75rem; font-size:.875rem;
        color:#111827; background:#fff; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s }
    .seo-input:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .dark .seo-input{ color:#f3f4f6; background:#111827; border-color:#374151 }
    textarea.seo-input{ resize:vertical }

    /* Подсказка всегда с отступом сверху: раньше у части полей его не было,
       и подпись следующего поля прилипала к предыдущей строке. */
    .seo-hint{ display:block; margin-top:.3rem; font-size:.72rem; line-height:1.45; color:#6b7280 }
    .dark .seo-hint{ color:#9ca3af }
    .seo-err{ margin-top:.3rem; font-size:.72rem; color:#dc2626 }

    .seo-directive{ margin-top:.6rem; font-size:.72rem; color:#6b7280 }
    .dark .seo-directive{ color:#9ca3af }

    .seo-switch{ display:inline-flex; align-items:flex-start; gap:.6rem; cursor:pointer }
    .seo-switch__body{ display:flex; flex-direction:column; gap:.1rem; line-height:1.35 }
    .seo-switch__title{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.82rem; font-weight:700; color:#374151 }
    .dark .seo-switch__title{ color:#e5e7eb }
    .seo-switch .seo-hint{ margin-top:0 }
</style>
@endpush

@push('scripts')
<script>
    // Итоговая директива меняется сразу при переключении тумблеров: раньше
    // строка обновлялась только после сохранения, и проверить выбор было
    // нечем. Тот же код, что на форме создания.
    (function () {
        const idx = document.querySelector('.js-robots-index');
        const fol = document.querySelector('.js-robots-follow');
        const out = document.getElementById('robotsPreview');

        if (!idx || !fol || !out) {
            return;
        }

        const upd = () => {
            out.textContent = (idx.checked ? 'index' : 'noindex') + ', ' + (fol.checked ? 'follow' : 'nofollow');
        };

        idx.addEventListener('change', upd);
        fol.addEventListener('change', upd);
        upd();
    })();
</script>
@endpush
