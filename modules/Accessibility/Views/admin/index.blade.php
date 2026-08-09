@extends('layouts.admin')

@section('title', __('admin.accessibility.title'))

@section('content')
@php
    // Возможности разложены по смыслу: плоский список из четырнадцати
    // одинаковых строк не давал понять, что на что влияет.
    $groups = [
        'group_text' => [
            'enable_font_size' => 'fa-text-height',
            'enable_text_spacing' => 'fa-arrows-up-down',
            'enable_dyslexia_font' => 'fa-font',
        ],
        'group_vision' => [
            'enable_contrast' => 'fa-circle-half-stroke',
            'enable_background' => 'fa-palette',
            'enable_bw_mode' => 'fa-low-vision',
            'enable_colorblind_mode' => 'fa-eye',
            'enable_sepia_mode' => 'fa-droplet',
        ],
        'group_reading' => [
            'enable_reading_mask' => 'fa-grip-lines',
            'enable_read_mode' => 'fa-book-open',
            'enable_highlight_links' => 'fa-link',
        ],
        'group_speech' => [
            'enable_speech' => 'fa-volume-high',
            'enable_selected_text_speech' => 'fa-comment-dots',
        ],
    ];

    // Подсказка у каждой возможности: по названию вроде «Сепия-тема» не
    // очевидно, что именно увидит посетитель.
    $hints = [
        'enable_font_size' => 'h_font_size',
        'enable_text_spacing' => 'h_spacing',
        'enable_dyslexia_font' => 'h_dyslexia',
        'enable_contrast' => 'h_contrast',
        'enable_background' => 'h_background',
        'enable_bw_mode' => 'h_bw',
        'enable_colorblind_mode' => 'h_colorblind',
        'enable_sepia_mode' => 'h_sepia',
        'enable_reading_mask' => 'h_reading_mask',
        'enable_read_mode' => 'h_read_mode',
        'enable_highlight_links' => 'h_links',
        'enable_speech' => 'h_speech',
        'enable_selected_text_speech' => 'h_selected_speech',
    ];

    $labels = [
        'enable_font_size' => 'o_font_size',
        'enable_text_spacing' => 'o_spacing',
        'enable_dyslexia_font' => 'o_dyslexia',
        'enable_contrast' => 'o_contrast',
        'enable_background' => 'o_background',
        'enable_bw_mode' => 'o_bw',
        'enable_colorblind_mode' => 'o_colorblind',
        'enable_sepia_mode' => 'o_sepia',
        'enable_reading_mask' => 'o_reading_mask',
        'enable_read_mode' => 'o_read_mode',
        'enable_highlight_links' => 'o_links',
        'enable_speech' => 'o_speech',
        'enable_selected_text_speech' => 'o_selected_speech',
    ];

    $active = collect($options)->filter(fn ($option) => $settings->{$option})->count();
@endphp

{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-4
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-universal-access"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.accessibility.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.accessibility.subtitle') }}</p>
        </div>
    </div>

    <a href="{{ url('/') }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-up-right-from-square"></i> {{ __('admin.accessibility.preview') }}
    </a>
</div>

<form method="POST" action="{{ route('admin.accessibility.update') }}" class="a11y-form"
      x-data="{ on: @js((bool) $settings->enabled), count: {{ $active }} }">
    @csrf

    {{-- ── Главный выключатель ── --}}
    <section class="admin-card p-5 mb-4">
        <div class="flex items-start gap-3">
            <label class="admin-toggle mt-1">
                <input type="checkbox" name="enabled" value="1" x-model="on" @checked($settings->enabled)>
                <span class="track"></span>
                <span class="knob"></span>
            </label>

            <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white">{{ __('admin.accessibility.master') }}</p>
                <p class="admin-hint">{{ __('admin.accessibility.master_hint') }}</p>

                <p class="a11y-state mt-2" :class="on ? 'is-on' : 'is-off'">
                    <i class="fas" :class="on ? 'fa-circle-check' : 'fa-circle-minus'"></i>
                    <span x-text="on ? @js(__('admin.accessibility.enabled_on')) : @js(__('admin.accessibility.enabled_off'))"></span>
                </p>
            </div>
        </div>
    </section>

    {{-- ── Возможности ── --}}
    <div class="a11y-groups" :class="!on && 'is-dimmed'">
        @foreach($groups as $group => $items)
            <section class="admin-card p-5">
                <h2 class="a11y-group-title">
                    <i class="fas fa-circle-dot"></i> {{ __('admin.accessibility.' . $group) }}
                    <span class="a11y-group-count">{{ count($items) }}</span>
                </h2>

                <div class="a11y-grid">
                @foreach($items as $option => $icon)
                    <div class="a11y-row">
                        {{-- value="1" обязателен: без него браузер шлёт "on",
                             и прежнее правило boolean роняло всю форму. --}}
                        <label class="admin-toggle mt-1">
                            <input type="checkbox" name="{{ $option }}" value="1"
                                   @checked($settings->{$option})
                                   @change="count += $event.target.checked ? 1 : -1">
                            <span class="track"></span>
                            <span class="knob"></span>
                        </label>

                        <div class="min-w-0">
                            <p class="a11y-row__label">
                                <i class="fas {{ $icon }}"></i>
                                {{ __('admin.accessibility.' . $labels[$option]) }}
                            </p>
                            <p class="admin-hint">{{ __('admin.accessibility.' . $hints[$option]) }}</p>
                        </div>
                    </div>
                @endforeach
                </div>
            </section>
        @endforeach
    </div>

    {{-- ── Сохранение ── --}}
    <div class="a11y-bar admin-card">
        <span class="text-sm text-gray-600 dark:text-gray-300"
              x-text="@js(__('admin.accessibility.count')).replace(':count', count).replace(':total', {{ count($options) }})"></span>

        <span class="admin-hint" x-show="on && count === 0" x-cloak>
            {{ __('admin.accessibility.none_hint') }}
        </span>

        <button type="submit"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                       px-5 py-2.5 text-sm font-semibold shadow-sm transition ml-auto">
            <i class="fas fa-floppy-disk"></i> {{ __('admin.accessibility.save') }}
        </button>
    </div>
</form>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    .a11y-form{ max-width:62rem; margin-inline:auto }

    /* Одна колонка групп, внутри группы — две колонки возможностей.
       Прежние три узкие колонки рвали подписи на три-четыре строки. */
    .a11y-groups{ display:grid; gap:1rem; transition:opacity .2s }
    .a11y-groups.is-dimmed{ opacity:.5 }

    /* На широком экране группы идут в ДВА столбца.
       Форма была прибита к 62rem и центрировалась, поэтому на широком мониторе
       по бокам оставалось пусто, а четыре группы тянулись вниз колонкой.
       Внутри столбца возможности идут в один ряд: два ряда в столбце шириной
       около 40rem снова рвали бы подписи на несколько строк — ровно то, от
       чего уходили, когда отказывались от трёх узких колонок.

       Раскладка ровная: слева «Текст» (3) и «Чтение» (3), справа «Зрение»
       (5) и «Речь» (2) — шесть строк против семи. Выравнивание по верху,
       иначе более короткий столбец растянулся бы вслед за длинным. */
    @media (min-width:1280px){
        .a11y-form{ max-width:88rem }
        .a11y-groups{ grid-template-columns:1fr 1fr; align-items:start }
        .a11y-grid{ grid-template-columns:1fr }
    }

    .a11y-group-title{ display:flex; align-items:center; gap:.5rem; margin-bottom:.9rem;
        font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#9ca3af;
        padding-bottom:.6rem; border-bottom:1px solid #eef2f7 }
    .a11y-group-title i{ color:#6366f1; font-size:.55rem }
    .a11y-group-count{ margin-left:auto; font-size:.7rem; font-weight:700; color:#6b7280;
        background:#f1f5f9; padding:.1rem .45rem; letter-spacing:0 }

    .a11y-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(24rem, 1fr)); gap:.35rem 2rem }

    .a11y-row{ display:flex; align-items:flex-start; gap:.85rem; padding:.7rem .1rem }
    .a11y-row + .a11y-row{ border-top:1px solid #f8fafc }
    .a11y-row__label{ margin:0 0 .1rem; font-size:.9rem; font-weight:600; color:#111827;
        display:flex; align-items:center; gap:.45rem }
    .a11y-row__label i{ color:#818cf8; width:1rem; text-align:center; flex:none }

    .a11y-state{ display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:700 }
    .a11y-state.is-on{ color:#166534 }
    .a11y-state.is-off{ color:#92400e }

    .a11y-bar{ display:flex; flex-wrap:wrap; align-items:center; gap:.75rem; padding:.85rem 1rem; margin-top:1rem }

    @media (prefers-color-scheme: dark){
        .a11y-row{ border-color:#1f2937 }
        .a11y-row__label{ color:#f3f4f6 }
    }
</style>
@endpush
