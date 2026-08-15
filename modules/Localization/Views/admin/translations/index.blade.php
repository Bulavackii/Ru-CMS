{{--
    Список языков с прогрессом перевода.

    Вёрстка — Tailwind, как во всей админке (layouts.admin подключает
    tailwind.min.css + Font Awesome). Bootstrap в проекте нет, поэтому
    диалоги сделаны на Alpine, а не на data-bs-* модалках.
--}}
@extends('layouts.admin')

@section('title', __('admin.translations.title'))

@section('content')
<div x-data="{ addOpen: false, deleteCode: null, deleteName: '' }">

    {{-- ── Шапка раздела ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-5
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-language"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.translations.title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.translations.index_hint') }} <code class="loc-code">resources/lang</code>
                    {{ __('admin.translations.from_admin') }} <strong>{{ $reference }}</strong>.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.localization.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-globe"></i> {{ __('admin.translations.countries') }}
            </a>

            <button type="button" x-on:click="addOpen = true"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-plus"></i> {{ __('admin.translations.add_language') }}
            </button>
        </div>
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
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ℹ️ Как считается прогресс --}}
    <div class="tr-note mb-4">
        <i class="fas fa-circle-info"></i>
        <span>
            {{ __('admin.translations.progress_note') }} <strong>{{ __('admin.translations.not_translated') }}</strong> {{ __('admin.translations.not_translated_hint') }}
        </span>
    </div>

    {{-- ── Языки карточками ── --}}
    <div class="tr-grid">
        @foreach ($locales as $locale)
            @php
                $s = $locale['stats'];
                $percent = $s['reference'] ? 100 : $s['percent'];
                $tone = $s['reference'] ? 'is-ref' : ($percent >= 90 ? 'is-ok' : ($percent >= 50 ? 'is-warn' : 'is-bad'));
            @endphp

            <article class="tr-card {{ $tone }}">
                <div class="tr-card__head">
                    <div class="tr-card__name">
                        <b class="tr-title">{{ $locale['name'] }}</b>
                        <span class="tr-meta">
                            <code class="tr-code">{{ $locale['code'] }}</code>

                            @if ($s['reference'])
                                <span class="tr-tag is-ref">{{ __('admin.translations.reference') }}</span>
                            @elseif ($locale['protected'])
                                <span class="tr-tag">{{ __('admin.translations.fallback') }}</span>
                            @endif
                        </span>
                    </div>

                    <span class="tr-percent">{{ $percent }}%</span>
                </div>

                <div class="tr-bar"><span style="width: {{ $percent }}%"></span></div>

                <p class="tr-stats">
                    @if ($s['reference'])
                        {{ __('admin.translations.source_lang', ['count' => $s['total']]) }}
                    @else
                        {{ __('admin.translations.translated_of', ['done' => $s['translated'], 'total' => $s['total']]) }}

                        {{-- ⚠️ Число подставляется ПАРАМЕТРОМ. Раньше строка
                             выводилась как есть, а число дописывалось рядом —
                             на странице печаталось «Совпадает с эталоном:
                             :count 17». --}}
                        @if ($s['missing'] > 0)
                            <span class="tr-bad">· {{ __('admin.translations.missing_n', ['count' => $s['missing']]) }}</span>
                        @endif

                        @if ($s['same'] > 0)
                            <span class="tr-warn">· {{ __('admin.translations.same_n', ['count' => $s['same']]) }}</span>
                        @endif
                    @endif
                </p>

                <div class="tr-card__foot">
                    <a href="{{ route('admin.localization.translations.edit', $locale['code']) }}" class="tr-btn">
                        <i class="fas fa-pen"></i> {{ __('admin.edit') }}
                    </a>

                    @unless ($locale['protected'])
                        <button type="button"
                                x-on:click="deleteCode = @js($locale['code']); deleteName = @js($locale['name'])"
                                title="{{ __('admin.translations.delete_language') }}"
                                class="tr-btn tr-btn--danger">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    @endunless
                </div>
            </article>
        @endforeach
    </div>

    <div class="tr-files mt-4">
        <span class="tr-files__label">
            <i class="fas fa-folder-open"></i> {{ __('admin.translations.files') }} ({{ count($groups) }})
        </span>
        @foreach ($groups as $g)<code class="tr-code">{{ $g }}</code>@endforeach
    </div>

    {{-- ➕ Диалог: новый язык --}}
    <div x-show="addOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-on:keydown.escape.window="addOpen = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md" x-on:click.outside="addOpen = false">
            <form method="POST" action="{{ route('admin.localization.translations.store') }}">
                @csrf
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="font-semibold text-gray-900 dark:text-white">➕ {{ __('admin.translations.new_language') }}</h2>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.translations.lang_code') }}</label>
                        <input type="text" name="code" id="code" required
                               placeholder="{{ __('admin.translations.lang_code_ph') }}"
                               pattern="[A-Za-z]{2,3}([_-][A-Za-z]{2,8})?"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded px-3 py-2 text-sm">
                        <p class="text-xs text-gray-500 mt-1">
                            {{ __('admin.translations.lang_code_hint') }} <code class="font-mono">resources/lang/&lt;код&gt;</code> {{ __('admin.translations.created_auto') }}
                        </p>
                    </div>
                    <div>
                        <label for="copy_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.translations.copy_from') }}</label>
                        <select name="copy_from" id="copy_from"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded px-3 py-2 text-sm">
                            @foreach ($locales as $locale)
                                <option value="{{ $locale['code'] }}" @selected($locale['code'] === $reference)>
                                    {{ $locale['name'] }} ({{ $locale['code'] }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ __('admin.translations.copy_hint') }}
                        </p>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button type="button" x-on:click="addOpen = false"
                            class="px-4 py-2 rounded text-sm border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        {{ __('admin.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded text-sm bg-black text-white hover:bg-gray-800">
                        {{ __('admin.translations.create_and_open') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 🗑️ Диалог: удаление языка --}}
    <div x-show="deleteCode !== null" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-on:keydown.escape.window="deleteCode = null">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md" x-on:click.outside="deleteCode = null">
            <form method="POST" x-bind:action="@js(url('admin/localization/translations')) + '/' + deleteCode">
                @csrf
                @method('DELETE')
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="font-semibold text-red-600">🗑️ Удалить язык <span x-text="deleteName"></span></h2>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                    <p class="mb-1">
                        Каталог <code class="font-mono">resources/lang/<span x-text="deleteCode"></span></code>
                        {{ __('admin.translations.will_be_deleted') }}
                    </p>
                    <p class="text-xs text-gray-500">{{ __('admin.translations.irreversible') }}</p>
                </div>
                <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button type="button" x-on:click="deleteCode = null"
                            class="px-4 py-2 rounded text-sm border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        {{ __('admin.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded text-sm bg-red-600 text-white hover:bg-red-700">
                        {{ __('admin.delete') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('Localization::admin.partials.form-styles')

@push('styles')
<style>
    /* ── Переводы интерфейса ─────────────────────────────────────────
       Литеральный CSS: скруглений в панели нет (сняты общим
       рубильником), а прозрачности через дробь нет в сборке Tailwind —
       прежние rounded-xl и bg-black/40 держались на них. */

    .tr-note{ display:flex; gap:.5rem; padding:.7rem .9rem; font-size:.78rem; line-height:1.5;
        color:color-mix(in srgb, var(--surface-ink,#111827) 72%, var(--surface,#fff));
        background:#f9fafb; border:1px solid #e5e7eb }
    .tr-note i{ margin-top:.15rem; color:#6366f1 }
    .dark .tr-note{ background:#0f172a; border-color:#374151 }

    .tr-grid{ display:grid; gap:1rem;
        grid-template-columns:repeat(auto-fill, minmax(min(100%, 18rem), 1fr)) }

    .tr-card{ display:flex; flex-direction:column; gap:.6rem; padding:1rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#e5e7eb);
        transition:border-color .15s, box-shadow .15s }
    .tr-card:hover{ border-color:#a5b4fc; box-shadow:0 6px 18px rgba(15,23,42,.07) }

    .tr-card__head{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem }
    .tr-card__name{ display:flex; flex-direction:column; gap:.25rem; min-width:0 }
    .tr-title{ font-size:.95rem; font-weight:700; color:var(--surface-ink,#111827) }
    .tr-meta{ display:inline-flex; align-items:center; gap:.35rem }

    .tr-code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.7rem;
        padding:.05rem .3rem; background:#f3f4f6; color:#4b5563 }
    .dark .tr-code{ background:#1f2937; color:#d1d5db }

    .tr-tag{ padding:.1rem .35rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#4b5563; background:#f3f4f6; border:1px solid #e5e7eb }
    .tr-tag.is-ref{ color:#3730a3; background:#eef2ff; border-color:#c7d2fe }
    .dark .tr-tag{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .tr-percent{ font-size:1.35rem; font-weight:800; letter-spacing:-.02em; white-space:nowrap;
        font-variant-numeric:tabular-nums; color:#6b7280 }
    .tr-card.is-ok .tr-percent{ color:#15803d }
    .tr-card.is-warn .tr-percent{ color:#b45309 }
    .tr-card.is-bad .tr-percent{ color:#b91c1c }
    .tr-card.is-ref .tr-percent{ color:#4338ca }

    .tr-bar{ height:4px; background:#f3f4f6; overflow:hidden }
    .tr-bar span{ display:block; height:100%; background:#9ca3af }
    .tr-card.is-ok .tr-bar span{ background:#16a34a }
    .tr-card.is-warn .tr-bar span{ background:#d97706 }
    .tr-card.is-bad .tr-bar span{ background:#dc2626 }
    .tr-card.is-ref .tr-bar span{ background:#6366f1 }
    .dark .tr-bar{ background:#1f2937 }

    .tr-stats{ margin:0; flex:1; font-size:.76rem; line-height:1.5;
        color:color-mix(in srgb, var(--surface-ink,#111827) 68%, var(--surface,#fff)) }
    .tr-bad{ color:#b91c1c }
    .tr-warn{ color:#b45309 }

    .tr-card__foot{ display:flex; gap:.35rem; margin-top:auto }
    .tr-btn{ display:inline-flex; align-items:center; justify-content:center; gap:.4rem; flex:1;
        padding:.5rem .75rem; font-size:.8rem; font-weight:600; cursor:pointer;
        color:var(--on-accent,#fff); background:#4f46e5; border:1px solid #4f46e5;
        transition:filter .15s }
    .tr-btn:hover{ filter:brightness(1.08); color:#fff }
    .tr-btn--danger{ flex:none; color:#b91c1c; background:var(--surface,#fff); border-color:#fecaca }
    .tr-btn--danger:hover{ color:#991b1b; border-color:#dc2626; filter:none }

    .tr-files{ display:flex; flex-wrap:wrap; align-items:center; gap:.35rem; font-size:.76rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 68%, var(--surface,#fff)) }
    .tr-files__label{ display:inline-flex; align-items:center; gap:.35rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase }
</style>
@endpush
