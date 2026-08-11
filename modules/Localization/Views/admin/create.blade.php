@extends('layouts.admin')

@section('title', __('admin.localization.create_title'))

@section('content')
{{--
    Та же вёрстка, что у правки страны: шапка с акцентной полосой,
    подписи полей моноширинным капсом, тумблер вместо голой галочки.
    Прежняя страница была остатком Bootstrap (h5/h6, hr, bg-light,
    text-muted) — классов этих в проекте нет, и выглядела она чужой.

    Имена полей, их id и адрес формы не менялись: на id опирается
    автозаполнение из пресета, а сервер видит ровно то же.
--}}

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-5
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-globe"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ __('admin.localization.create_heading') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.localization.create_sub') }}
            </p>
        </div>
    </div>

    <a href="{{ route('admin.localization.index') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-left"></i> {{ __('admin.localization.back') }}
    </a>
</div>

@includeIf('layouts.partials.flash')

<div class="loc-cols">
    <form action="{{ route('admin.localization.store') }}" method="POST" class="loc-form">
        @csrf

        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-flag text-indigo-500"></i> {{ __('admin.localization.main_params') }}
            </h2>

            @if(!empty($presets))
                {{-- Быстрое заполнение: набор стран лежит в конфиге модуля,
                     скрипт ниже раскладывает выбранный по полям формы. --}}
                <div class="loc-preset mb-4">
                    <label for="presetSelect" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        <i class="fas fa-bolt text-indigo-500"></i> {{ __('admin.localization.preset_fill') }}
                    </label>

                    <select id="presetSelect" class="loc-input">
                        <option value="">{{ __('admin.localization.preset_choose') }}</option>
                        @foreach($presets as $code => $data)
                            <option value="{{ $code }}">{{ $data['flag'] ?? '🏳️' }} {{ $data['name'] }} ({{ $code }})</option>
                        @endforeach
                    </select>

                    <p class="admin-hint mt-1">{{ __('admin.localization.preset_hint') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="code" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_code') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="code" name="code" maxlength="2" required
                           value="{{ old('code') }}" placeholder="RU"
                           class="loc-input font-mono">
                    <p class="admin-hint mt-1">{{ __('admin.localization.f_code_hint') }}</p>
                    @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name') }}" placeholder="{{ __('admin.localization.f_name_ph') }}"
                           class="loc-input">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="native_name" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_native') }}
                    </label>
                    <input type="text" id="native_name" name="native_name"
                           value="{{ old('native_name') }}"
                           class="loc-input">
                    @error('native_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="flag" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_flag') }}
                    </label>
                    <input type="text" id="flag" name="flag"
                           value="{{ old('flag') }}"
                           class="loc-input">
                    @error('flag') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-ruble-sign text-indigo-500"></i> {{ __('admin.localization.g_currency') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="currency_code" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_currency_code') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="currency_code" name="currency_code" maxlength="3" required
                           value="{{ old('currency_code') }}" placeholder="RUB"
                           class="loc-input font-mono">
                    @error('currency_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="currency_symbol" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_currency_symbol') }}
                    </label>
                    <input type="text" id="currency_symbol" name="currency_symbol"
                           value="{{ old('currency_symbol') }}" placeholder="₽"
                           class="loc-input">
                    @error('currency_symbol') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-clock text-indigo-500"></i> {{ __('admin.localization.g_locale_time') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="locale" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_locale') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="locale" name="locale" required
                           value="{{ old('locale') }}" placeholder="ru_RU"
                           class="loc-input font-mono">
                    <p class="admin-hint mt-1">ru_RU, en_US, de_DE</p>
                    @error('locale') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="timezone" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_timezone') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="timezone" name="timezone" required
                           value="{{ old('timezone') }}" placeholder="Europe/Moscow"
                           class="loc-input font-mono">
                    <p class="admin-hint mt-1">Europe/Moscow, America/New_York</p>
                    @error('timezone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-calendar-days text-indigo-500"></i> {{ __('admin.localization.g_formats') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="date_format" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_date_format') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="date_format" name="date_format" required class="loc-input">
                        @foreach($dateFormats as $value => $label)
                            <option value="{{ $value }}" @selected(old('date_format', 'd.m.Y') === $value)>
                                {{ $label }} ({{ $value }})
                            </option>
                        @endforeach
                    </select>
                    @error('date_format') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="time_format" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_time_format') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="time_format" name="time_format" required class="loc-input">
                        @foreach($timeFormats as $value => $label)
                            <option value="{{ $value }}" @selected(old('time_format', 'H:i') === $value)>
                                {{ $label }} ({{ $value }})
                            </option>
                        @endforeach
                    </select>
                    @error('time_format') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-hashtag text-indigo-500"></i> {{ __('admin.localization.g_numbers') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="decimal_separator" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_decimal_sep') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="decimal_separator" name="decimal_separator" required class="loc-input">
                        @foreach($decimalSeparators as $value => $label)
                            <option value="{{ $value }}" @selected(old('decimal_separator', ',') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('decimal_separator') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="thousands_separator" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_thousands_sep') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="thousands_separator" name="thousands_separator" required class="loc-input">
                        @foreach($thousandsSeparators as $value => $label)
                            <option value="{{ $value }}" @selected(old('thousands_separator', ' ') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('thousands_separator') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="decimal_places" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_decimals') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="decimal_places" name="decimal_places" min="0" max="6" required
                           value="{{ old('decimal_places', 2) }}"
                           class="loc-input">
                    @error('decimal_places') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="loc-switch mt-4">
                <span class="admin-toggle">
                    <input type="checkbox" id="active" name="active" {{ old('active', true) ? 'checked' : '' }}>
                    <span class="track"></span><span class="knob"></span>
                </span>
                <span class="loc-switch__text">{{ __('admin.localization.active_full') }}</span>
            </label>
        </section>

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-floppy-disk"></i> {{ __('admin.localization.create_submit') }}
            </button>

            <a href="{{ route('admin.localization.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-5 py-2.5 text-sm font-semibold transition">
                {{ __('admin.localization.cancel') }}
            </a>
        </div>
    </form>

    {{-- ── Подсказки ── --}}
    <aside class="loc-side">
        <section class="admin-card p-5">
            <h2 class="loc-h2">
                <i class="fas fa-circle-info text-indigo-500"></i> {{ __('admin.localization.hints') }}
            </h2>

            <ul class="loc-hints">
                @foreach(['hint_1', 'hint_2', 'hint_3', 'hint_4', 'hint_5'] as $hint)
                    <li><i class="fas fa-check"></i> {{ __('admin.localization.' . $hint) }}</li>
                @endforeach
            </ul>
        </section>
    </aside>
</div>
@endsection

@include('Localization::admin.partials.form-styles')

@push('styles')
<style>
    /* Быстрое заполнение выделено подложкой: это не обычное поле формы,
       а ветка «взять готовое». */
    .loc-preset{ padding:.85rem; background:#f9fafb; border:1px solid #e5e7eb }
    .dark .loc-preset{ background:#0f172a; border-color:#374151 }

    .loc-hints{ display:grid; gap:.55rem; margin:0; padding:0; list-style:none }
    .loc-hints li{ display:flex; align-items:flex-start; gap:.5rem; font-size:.82rem; line-height:1.45;
        color:color-mix(in srgb, var(--surface-ink,#111827) 75%, var(--surface,#fff)) }
    .loc-hints i{ margin-top:.2rem; font-size:.7rem; color:#6366f1; flex:none }
</style>
@endpush

@push('scripts')
<script>
    // Заполнение полей из пресета. Идентификаторы полей менять нельзя —
    // скрипт ищет их по id.
    document.getElementById('presetSelect')?.addEventListener('change', function (e) {
        const presets = @json($presets);
        const code = e.target.value;

        if (!code || !presets[code]) return;

        const preset = presets[code];

        document.getElementById('code').value = code;
        document.getElementById('name').value = preset.name || '';
        document.getElementById('native_name').value = preset.native_name || '';
        document.getElementById('flag').value = preset.flag || '';
        document.getElementById('currency_code').value = preset.currency_code || '';
        document.getElementById('currency_symbol').value = preset.currency_symbol || '';
        document.getElementById('locale').value = preset.locale || '';
        document.getElementById('timezone').value = preset.timezone || '';
        document.getElementById('date_format').value = preset.date_format || 'd.m.Y';
        document.getElementById('time_format').value = preset.time_format || 'H:i';
        document.getElementById('decimal_separator').value = preset.decimal_separator || '.';
        document.getElementById('thousands_separator').value = preset.thousands_separator || ' ';
        document.getElementById('decimal_places').value = preset.decimal_places || 2;
    });
</script>
@endpush
