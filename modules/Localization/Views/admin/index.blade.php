@extends('layouts.admin')

@section('title', __('admin.sections.localization'))

@section('content')
@php
    use Modules\Localization\Models\Country;

    $presets = config('localization.preset_countries', []);
    $importedCodes = Country::pluck('code')->all();
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-globe"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.sections.localization') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.localization.subtitle') }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('admin.localization.translations.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
            <i class="fas fa-language"></i> {{ __('admin.localization.translations_link') }}
        </a>
        <a href="{{ route('admin.localization.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i> {{ __('admin.localization.add_country') }}
        </a>
    </div>
</div>

@includeIf('layouts.partials.flash')

{{-- ── Сводка ──
     Тот же приём, что в «Оплате», «Доставке» и «Заказах»: числа раздела
     строкой чипов. Четыре плитки занимали целый экран ради четырёх
     чисел. Устойчивый ключ (data-stat) сохранён — по нему подпись
     берётся из словаря и на него опираются проверки. --}}
<div class="loc-summary mb-4">
    @foreach([
        ['countries', $stats['total_countries'] ?? 0, 'fa-globe'],
        ['active', $stats['active_countries'] ?? 0, 'fa-circle-check'],
        ['settings', $stats['total_settings'] ?? 0, 'fa-sliders'],
        ['system', $stats['system_settings'] ?? 0, 'fa-lock'],
    ] as [$stat, $value, $icon])
        <span class="loc-chip {{ $stat === 'active' ? 'is-on' : '' }}"
              title="{{ __('admin.localization.stat_' . $stat . '_hint') }}">
            <i class="fas {{ $icon }}"></i>
            {{ __('admin.localization.stat_' . $stat) }}
            <b data-stat="{{ $stat }}">{{ $value }}</b>
        </span>
    @endforeach
</div>

{{-- ── Инструменты ── --}}
<div class="admin-card p-4 mb-5" x-data="{ importOpen: false }">
    <h2 class="loc-h2">
        <i class="fas fa-screwdriver-wrench text-indigo-500"></i> {{ __('admin.localization.tools') }}
    </h2>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Модал импорта на Alpine: прежний был на data-bs-toggle, а Bootstrap
             JS в проекте не подключён — кнопка просто ничего не делала --}}
        <button type="button" @click="importOpen = true"
                class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                       hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
            <i class="fas fa-download"></i> {{ __('admin.localization.import') }}
        </button>

        <form action="{{ route('admin.localization.clear.cache') }}" method="POST" class="inline">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                           hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
                <i class="fas fa-arrows-rotate"></i> {{ __('admin.localization.clear_cache') }}
            </button>
        </form>

        <span class="text-sm text-gray-400 dark:text-gray-500">
            {{ __('admin.localization.available_presets') }} {{ trans_choice('admin.localization.countries_plural', count($presets)) }}
        </span>
    </div>

    {{-- Модал импорта --}}
    <div x-cloak x-show="importOpen" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(17,24,39,.55);"
         @keydown.escape.window="importOpen = false">
        <div class="admin-card w-full max-w-lg p-5" @click.outside="importOpen = false">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.localization.import') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.localization.import_hint') }}
                    </p>
                </div>
                <button type="button" @click="importOpen = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.localization.import.presets') }}" method="POST">
                @csrf
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse($presets as $code => $data)
                        @php $already = in_array($code, $importedCodes, true); @endphp
                        <label class="flex items-center gap-3 border border-gray-200 dark:border-gray-700 p-3
                                      {{ $already ? 'opacity-60' : 'cursor-pointer hover:border-indigo-400' }} transition">
                            <input type="checkbox" name="countries[]" value="{{ $code }}"
                                   class="border-gray-400" {{ $already ? 'disabled' : '' }}>
                            <span class="text-lg">{{ $data['flag'] ?? '🏳️' }}</span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $data['name'] }}</span>
                                <span class="block text-xs font-mono text-gray-400">{{ $code }}</span>
                            </span>
                            @if($already)
                                <span class="ml-auto text-xs text-green-700"><i class="fas fa-check"></i> {{ __('admin.localization.already_added') }}</span>
                            @endif
                        </label>
                    @empty
                        <p class="admin-hint">{{ __('admin.localization.no_presets') }}</p>
                    @endforelse
                </div>

                <div class="flex items-center gap-2 mt-5">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                                   px-4 py-2 text-sm font-semibold shadow-sm transition flex-1">
                        <i class="fas fa-download"></i> {{ __('admin.localization.import_submit') }}
                    </button>
                    <button type="button" @click="importOpen = false"
                            class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800
                                   px-4 py-2 text-sm font-semibold transition">
                        {{ __('admin.localization.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($countries->isEmpty())
    {{-- ── Пустое состояние ── --}}
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-globe"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.localization.empty_title') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-5">
            {{ __('admin.localization.empty_hint_1') }}
            {{ __('admin.localization.empty_hint_2') }}
        </p>
        <a href="{{ route('admin.localization.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i> {{ __('admin.localization.add_country') }}
        </a>
    </div>
@else
    {{-- ── Страны карточками ──
         Таблица на шесть колонок при одной стране давала полторы тысячи
         пикселей пустоты и горизонтальную прокрутку на ноутбуке. --}}
    <div class="loc-grid">
        @foreach($countries as $country)
            <article class="loc-card {{ $country->active ? '' : 'is-off' }}">
                <div class="loc-card__head">
                    <span class="loc-flag">{{ $country->flag ?? '🏳️' }}</span>

                    <div class="loc-card__name">
                        <b class="loc-title">{{ $country->name }}</b>
                        @if($country->native_name && $country->native_name !== $country->name)
                            <span class="loc-native">{{ $country->native_name }}</span>
                        @endif
                    </div>

                    <span class="loc-state {{ $country->active ? 'is-on' : '' }}">
                        <i class="fas {{ $country->active ? 'fa-circle-check' : 'fa-ban' }}"></i>
                        {{ $country->active ? __('admin.localization.status_on') : __('admin.localization.status_off') }}
                    </span>
                </div>

                <div class="loc-figures">
                    <span class="loc-fig">
                        <span class="loc-fig__label">{{ __('admin.localization.code_label') }}</span>
                        <b>{{ $country->code }}</b>
                    </span>
                    <span class="loc-fig">
                        <span class="loc-fig__label">{{ __('admin.localization.th_currency') }}</span>
                        <b>{{ $country->currency_code }}{{ $country->currency_symbol ? ' ' . $country->currency_symbol : '' }}</b>
                    </span>
                    <span class="loc-fig">
                        <span class="loc-fig__label">{{ __('admin.localization.th_locale') }}</span>
                        <b>{{ $country->locale }}</b>
                    </span>
                    <span class="loc-fig">
                        <span class="loc-fig__label">{{ __('admin.localization.th_settings') }}</span>
                        <b>{{ $country->settings_count }}</b>
                    </span>
                </div>

                <div class="loc-card__foot">
                    <a href="{{ route('admin.localization.edit', $country->code) }}" class="loc-btn">
                        <i class="fas fa-pen"></i> {{ __('admin.localization.act_edit') }}
                    </a>

                    <a href="{{ route('admin.localization.settings', $country->code) }}" class="loc-btn">
                        <i class="fas fa-sliders"></i> {{ __('admin.localization.act_formats') }}
                    </a>

                    <button type="submit" form="delete-country-{{ $country->code }}"
                            class="loc-btn loc-btn--danger" title="{{ __('admin.localization.act_delete') }}">
                        <i class="fas fa-trash-can"></i>
                    </button>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Формы удаления — вне таблицы --}}
    @foreach($countries as $country)
        <form id="delete-country-{{ $country->code }}" method="POST"
              action="{{ route('admin.localization.destroy', $country->code) }}" class="hidden"
              onsubmit="return confirm(@js(__('admin.localization.confirm_delete', ['name' => $country->name])))">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
@endsection

@push('styles')
<style>
    /* ── Локализация ──────────────────────────────────────────────────
       Литеральный CSS: в сборке проекта нет ни прозрачности через дробь,
       ни произвольных значений. Типографика — как в «Оплате» и
       «Доставке»: подписи моноширинным капсом. */

    .loc-summary{ display:flex; flex-wrap:wrap; gap:.5rem }
    .loc-chip{ display:inline-flex; align-items:center; gap:.45rem;
        padding:.4rem .7rem; font-size:.8rem; color:#4b5563;
        background:#f9fafb; border:1px solid #e5e7eb }
    .loc-chip i{ color:#9ca3af }
    .loc-chip b{ color:#111827; font-variant-numeric:tabular-nums }
    .loc-chip.is-on i{ color:#16a34a }
    .dark .loc-chip{ color:#d1d5db; background:#111827; border-color:#374151 }
    .dark .loc-chip b{ color:#f3f4f6 }

    .loc-h2{ display:flex; align-items:center; gap:.4rem; margin-bottom:.75rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.7rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    .loc-grid{ display:grid; gap:1rem;
        grid-template-columns:repeat(auto-fill, minmax(min(100%, 20rem), 1fr)) }

    .loc-card{ display:flex; flex-direction:column; gap:.7rem; padding:0 0 .8rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#e5e7eb);
        transition:border-color .15s, box-shadow .15s }
    .loc-card:hover{ border-color:#a5b4fc; box-shadow:0 6px 18px rgba(15,23,42,.07) }
    .loc-card.is-off .loc-flag{ filter:grayscale(1); opacity:.6 }

    .loc-card__head{ display:flex; align-items:center; gap:.7rem; padding:.9rem 1rem 0 }
    .loc-flag{ font-size:1.6rem; line-height:1; flex:none }
    .loc-card__name{ display:flex; flex-direction:column; gap:.1rem; min-width:0; flex:1 }
    .loc-title{ font-size:.95rem; font-weight:700; color:var(--surface-ink,#111827);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .loc-native{ font-size:.72rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 60%, var(--surface,#fff)) }

    .loc-state{ display:inline-flex; align-items:center; gap:.3rem; flex:none;
        padding:.15rem .45rem; font-size:.68rem; font-weight:700; white-space:nowrap;
        color:#6b7280; background:#f3f4f6; border:1px solid #e5e7eb }
    .loc-state.is-on{ color:#15803d; background:#dcfce7; border-color:#86efac }
    .dark .loc-state{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    /* Четыре факта в ряд: код, валюта, локаль, число настроек. */
    .loc-figures{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr));
        gap:.5rem; margin:0 1rem }
    .loc-fig{ display:flex; align-items:baseline; gap:.4rem; padding:.3rem .55rem;
        background:#f9fafb; border:1px solid #eef2f7; min-width:0 }
    .loc-fig__label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#6b7280; flex:none }
    .loc-fig b{ font-size:.82rem; margin-left:auto; color:var(--surface-ink,#111827);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        font-variant-numeric:tabular-nums }
    .dark .loc-fig{ background:#0f172a; border-color:#374151 }

    .loc-card__foot{ display:flex; align-items:center; gap:.35rem;
        margin-top:auto; padding:.7rem 1rem 0; border-top:1px solid #f1f5f9 }
    .dark .loc-card__foot{ border-top-color:#374151 }

    .loc-btn{ display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .6rem;
        font-size:.75rem; font-weight:600; cursor:pointer;
        color:#4b5563; background:var(--surface,#fff); border:1px solid #e5e7eb;
        transition:border-color .15s, color .15s }
    .loc-btn:hover{ border-color:#6366f1; color:#4338ca }
    .loc-btn--danger{ margin-left:auto }
    .loc-btn--danger:hover{ border-color:#dc2626; color:#b91c1c }
    .dark .loc-btn{ color:#d1d5db; background:#111827; border-color:#374151 }
</style>
@endpush
