{{--
    Редактор строк одной группы (файла) переводов.

    Слева — список файлов локали с прогрессом, справа — таблица
    «ключ / эталон / перевод». Фильтрация по подстроке и по незаконченным
    строкам сделана на клиенте: строк сотни, гонять их через сервер незачем.
--}}
@extends('layouts.admin')

@section('title', 'Переводы: ' . $localeName)

@section('content')
<div x-data="translationEditor()">

    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-4
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-language"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 flex-wrap">
                    {{ $localeName }}
                    <code class="tr-code">{{ $locale }}</code>
                    @if ($isReference)
                        <span class="tr-tag is-ref">{{ __('admin.translations.reference') }}</span>
                    @endif
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.translations.file_label') }}
                    <code class="tr-code">resources/lang/{{ $locale }}/{{ $group }}.php</code>
                </p>
            </div>
        </div>

        <a href="{{ route('admin.localization.translations.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.translations.all_languages') }}
        </a>
    </div>

    {{-- ✅ Флеш-сообщения --}}
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    @if ($isReference)
        <div class="tr-note is-warn mb-4">
            <i class="fas fa-triangle-exclamation"></i>
            <span>
                Это <strong>{{ __('admin.translations.reference_lang') }}</strong> {{ __('admin.translations.reference_note') }}
            </span>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-4">

        {{-- 📂 {{ __('admin.translations.files') }} --}}
        <div class="lg:col-span-1">
            <div class="admin-card">
                <div class="tr-panel__head">{{ __('admin.translations.files') }}</div>
                <div class="tr-files-list">
                    @foreach ($groups as $g)
                        @php
                            $gs = $groupStats[$g];
                            $active = $g === $group;
                        @endphp
                        <a href="{{ route('admin.localization.translations.edit', [$locale, $g]) }}"
                           class="tr-file {{ $active ? 'is-active' : '' }}">
                            <span class="tr-file__row">
                                <code>{{ $g }}</code>
                                <span class="tr-file__num">{{ $isReference ? $gs['total'] : $gs['percent'] . '%' }}</span>
                            </span>

                            @unless ($isReference)
                                <span class="tr-bar tr-bar--thin
                                             {{ $gs['percent'] >= 90 ? 'is-ok' : ($gs['percent'] >= 50 ? 'is-warn' : 'is-bad') }}">
                                    <span style="width: {{ $gs['percent'] }}%"></span>
                                </span>
                            @endunless
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ✏️ Строки --}}
        <div class="lg:col-span-3">
            <form method="POST" action="{{ route('admin.localization.translations.update', [$locale, $group]) }}">
                @csrf
                @method('PUT')

                <div class="admin-card">

                    {{-- Панель: поиск, фильтр, сохранение --}}
                    <div class="tr-toolbar">
                        <div class="flex items-center gap-3 flex-1 min-w-[260px]">
                            <input type="search" x-model="query" x-on:input="apply()"
                                   placeholder="{{ __('admin.translations.search_ph') }}" autocomplete="off"
                                   class="loc-input flex-1">
                            @unless ($isReference)
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap cursor-pointer">
                                    <input type="checkbox" x-model="todoOnly" x-on:change="apply()" class="rounded">
                                    {{ __('admin.translations.only_unfinished') }}
                                </label>
                            @endunless
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="tr-counter" x-text="counter"></span>
                            <button type="submit" class="tr-save">
                                <i class="fas fa-floppy-disk"></i> {{ __('admin.translations.save') }}
                            </button>
                        </div>
                    </div>

                    {{-- Таблица --}}
                    <div class="tr-scroll">
                        <table class="tr-table">
                            <thead>
                                <tr>
                                    <th class="w-1/4">{{ __('admin.translations.key') }}</th>
                                    @unless ($isReference)
                                        <th class="w-1/3">{{ __('admin.translations.th_reference', ['locale' => $reference]) }}</th>
                                    @endunless
                                    <th>{{ __('admin.translations.th_translation', ['locale' => $locale]) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="tr-row align-top"
                                        data-todo="{{ ($row['missing'] || $row['same']) && !$isReference ? '1' : '0' }}"
                                        data-haystack="{{ mb_strtolower($row['key'] . ' ' . $row['reference'] . ' ' . $row['value']) }}">
                                        <td class="py-2 px-3">
                                            <code class="tr-key">{{ $row['key'] }}</code>
                                            @if ($row['extra'])
                                                <span class="tr-mark" title="{{ __('admin.translations.not_in_reference') }}">{{ __('admin.translations.extra') }}</span>
                                            @elseif ($row['missing'] && !$isReference)
                                                <span class="tr-mark is-bad">{{ __('admin.translations.none') }}</span>
                                            @elseif ($row['same'] && !$isReference)
                                                <span class="tr-mark is-warn" title="{{ __('admin.translations.same_as_reference') }}">=</span>
                                            @endif
                                        </td>
                                        @unless ($isReference)
                                            <td class="tr-ref">{{ $row['reference'] }}</td>
                                        @endunless
                                        <td class="py-2 px-3">
                                            <textarea name="lines[{{ $row['key'] }}]" rows="1" spellcheck="false"
                                                      x-on:input="fit($event.target)" x-init="fit($el)"
                                                      class="tr-input loc-input">{{ $row['value'] }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Подвал --}}
                    <div class="tr-foot">
                        <span class="tr-foot__note">
                            {{ __('admin.translations.empty_note') }}
                            <code class="tr-code">{{ config('app.fallback_locale') }}</code>.
                            {{ __('admin.translations.bak_note') }}.
                        </span>
                        <button type="submit" class="tr-save">
                            <i class="fas fa-floppy-disk"></i> {{ __('admin.translations.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('Localization::admin.partials.form-styles')

@push('styles')
<style>
    /* ── Редактор переводов ──────────────────────────────────────────
       Литеральный CSS: скругления в панели сняты общим рубильником, а
       произвольных значений (max-h-[65vh]) и прозрачности через дробь в
       сборке Tailwind нет — прежняя вёрстка держалась на них. */

    .tr-note{ display:flex; gap:.5rem; padding:.7rem .9rem; font-size:.78rem; line-height:1.5;
        color:color-mix(in srgb, var(--surface-ink,#111827) 72%, var(--surface,#fff));
        background:#f9fafb; border:1px solid #e5e7eb }
    .tr-note i{ margin-top:.15rem; color:#6366f1 }
    .tr-note.is-warn{ color:#92400e; background:#fffbeb; border-color:#fde68a }
    .tr-note.is-warn i{ color:#b45309 }

    .tr-code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.72rem;
        padding:.05rem .3rem; background:#f3f4f6; color:#4b5563 }
    .dark .tr-code{ background:#1f2937; color:#d1d5db }

    .tr-tag{ padding:.1rem .35rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#4b5563; background:#f3f4f6; border:1px solid #e5e7eb }
    .tr-tag.is-ref{ color:#3730a3; background:#eef2ff; border-color:#c7d2fe }

    .tr-panel__head{ padding:.6rem .9rem; border-bottom:1px solid var(--surface-bd,#eef2f7);
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    .tr-files-list{ max-height:70vh; overflow-y:auto }
    .tr-file{ display:block; padding:.55rem .9rem; border-bottom:1px solid #f1f5f9;
        transition:background .15s }
    .tr-file:hover{ background:#f9fafb }
    .tr-file.is-active{ background:#eef2ff }
    .dark .tr-file{ border-bottom-color:#374151 }
    .dark .tr-file:hover{ background:#1f2937 }
    .dark .tr-file.is-active{ background:#1e1b4b }

    .tr-file__row{ display:flex; align-items:center; justify-content:space-between; gap:.5rem }
    .tr-file code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.75rem;
        color:var(--surface-ink,#111827) }
    .tr-file__num{ font-size:.7rem; font-variant-numeric:tabular-nums;
        color:color-mix(in srgb, var(--surface-ink,#111827) 60%, var(--surface,#fff)) }

    .tr-bar{ display:block; height:4px; margin-top:.35rem; background:#f3f4f6; overflow:hidden }
    .tr-bar span{ display:block; height:100%; background:#9ca3af }
    .tr-bar.is-ok span{ background:#16a34a }
    .tr-bar.is-warn span{ background:#d97706 }
    .tr-bar.is-bad span{ background:#dc2626 }
    .tr-bar--thin{ height:3px }
    .dark .tr-bar{ background:#1f2937 }

    .tr-toolbar{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
        gap:.75rem; padding:.7rem .9rem; border-bottom:1px solid var(--surface-bd,#eef2f7) }
    .tr-counter{ font-size:.72rem; font-variant-numeric:tabular-nums;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    .tr-save{ display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .9rem;
        font-size:.8rem; font-weight:700; cursor:pointer; white-space:nowrap;
        color:var(--on-accent,#fff); background:#4f46e5; border:1px solid #4f46e5;
        transition:filter .15s }
    .tr-save:hover{ filter:brightness(1.08) }

    .tr-scroll{ max-height:65vh; overflow:auto }
    .tr-table{ width:100%; font-size:.85rem; border-collapse:collapse }
    .tr-table thead th{ position:sticky; top:0; z-index:10; text-align:left;
        padding:.5rem .75rem; background:#f9fafb; border-bottom:1px solid #e5e7eb;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .dark .tr-table thead th{ background:#111827; border-bottom-color:#374151 }
    .tr-table td{ padding:.4rem .75rem; vertical-align:top; border-bottom:1px solid #f1f5f9 }
    .dark .tr-table td{ border-bottom-color:#1f2937 }

    .tr-key{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.72rem;
        word-break:break-all; color:var(--surface-ink,#111827) }
    .tr-ref{ font-size:.78rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 65%, var(--surface,#fff)) }

    .tr-mark{ margin-left:.3rem; padding:.05rem .25rem; font-size:.6rem; font-weight:700;
        color:#4b5563; background:#f3f4f6; border:1px solid #e5e7eb }
    .tr-mark.is-bad{ color:#b91c1c; background:#fef2f2; border-color:#fecaca }
    .tr-mark.is-warn{ color:#b45309; background:#fffbeb; border-color:#fde68a }

    .tr-input{ min-height:34px; resize:vertical; padding:.3rem .5rem; font-size:.85rem }

    .tr-foot{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
        gap:.75rem; padding:.7rem .9rem; border-top:1px solid var(--surface-bd,#eef2f7) }
    .tr-foot__note{ font-size:.72rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
</style>
@endpush

@push('scripts')
<script>
    function translationEditor() {
        return {
            query: '',
            todoOnly: false,
            counter: '',

            init() {
                this.apply();
            },

            // Автовысота поля под содержимое
            fit(el) {
                el.style.height = 'auto';
                el.style.height = (el.scrollHeight + 2) + 'px';
            },

            apply() {
                const q = (this.query || '').trim().toLowerCase();
                const rows = document.querySelectorAll('.tr-row');
                let shown = 0;

                rows.forEach((row) => {
                    const matchesText = q === '' || row.dataset.haystack.indexOf(q) !== -1;
                    const matchesTodo = !this.todoOnly || row.dataset.todo === '1';
                    const visible = matchesText && matchesTodo;

                    row.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });

                // Строка счётчика — из словаря: раньше «из … строк» было
                // зашито по-русски и оставалось русским на любой локали.
                this.counter = @js(__('admin.translations.rows_counter'))
                    .replace(':shown', shown)
                    .replace(':total', rows.length);
            },
        };
    }
</script>
@endpush
@endsection
