@extends('layouts.admin')

@section('title', __('admin.localization.edit_title'))

@section('content')
{{--
    Страница переписана в общий язык панели: шапка с акцентной полосой,
    подписи полей моноширинным капсом, тумблер вместо голой галочки.
    Прежняя вёрстка была остатком Bootstrap — alert-dismissible, btn-close,
    table-sm и заголовки h5/h6, которых в проекте нет: кнопки закрытия
    ничего не закрывали, потому что Bootstrap JS не подключён.

    Имена полей и адрес формы не менялись — сервер видит ровно то же.
--}}

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-5
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="loc-flag-badge">{{ $country->flag ?? '🏳️' }}</span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ __('admin.localization.edit_heading') }}: {{ $country->name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.localization.code_label') }}: <code class="loc-code">{{ $country->code }}</code>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('admin.localization.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
            <i class="fas fa-arrow-left"></i> {{ __('admin.localization.back') }}
        </a>

        <a href="{{ route('admin.localization.settings', $country->code) }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
            <i class="fas fa-sliders"></i> {{ __('admin.localization.settings_link') }}
        </a>
    </div>
</div>

@includeIf('layouts.partials.flash')

<div class="loc-cols">
    {{-- ── Форма ── --}}
    <form action="{{ route('admin.localization.update', $country->code) }}" method="POST" class="loc-form">
        @csrf
        @method('PUT')

        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-flag text-indigo-500"></i> {{ __('admin.localization.main_params') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name', $country->name) }}"
                           class="loc-input">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="native_name" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_native') }}
                    </label>
                    <input type="text" id="native_name" name="native_name"
                           value="{{ old('native_name', $country->native_name) }}"
                           class="loc-input">
                    @error('native_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="flag" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_flag') }}
                    </label>
                    <input type="text" id="flag" name="flag"
                           value="{{ old('flag', $country->flag) }}"
                           class="loc-input">
                    @error('flag') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.status') }}
                    </span>

                    {{-- Тумблер, а не голая галочка: в этой сборке Tailwind
                         нет варианта peer-checked, поэтому переключатель
                         собран настоящим CSS-селектором (.admin-toggle). --}}
                    <label class="loc-switch">
                        <span class="admin-toggle">
                            <input type="checkbox" id="active" name="active"
                                   {{ old('active', $country->active) ? 'checked' : '' }}>
                            <span class="track"></span><span class="knob"></span>
                        </span>
                        <span class="loc-switch__text">{{ __('admin.localization.active') }}</span>
                    </label>
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
                           value="{{ old('currency_code', $country->currency_code) }}"
                           class="loc-input font-mono">
                    @error('currency_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="currency_symbol" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_currency_symbol') }}
                    </label>
                    <input type="text" id="currency_symbol" name="currency_symbol"
                           value="{{ old('currency_symbol', $country->currency_symbol) }}"
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
                           value="{{ old('locale', $country->locale) }}"
                           class="loc-input font-mono">
                    @error('locale') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="timezone" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.f_timezone') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="timezone" name="timezone" required
                           value="{{ old('timezone', $country->timezone) }}"
                           class="loc-input font-mono">
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
                            <option value="{{ $value }}" @selected(old('date_format', $country->date_format) === $value)>
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
                            <option value="{{ $value }}" @selected(old('time_format', $country->time_format) === $value)>
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
                            <option value="{{ $value }}" @selected(old('decimal_separator', $country->decimal_separator) === $value)>
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
                            <option value="{{ $value }}" @selected(old('thousands_separator', $country->thousands_separator) === $value)>
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
                           value="{{ old('decimal_places', $country->decimal_places) }}"
                           class="loc-input">
                    @error('decimal_places') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-floppy-disk"></i> {{ __('admin.localization.save') }}
            </button>

            <a href="{{ route('admin.localization.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-5 py-2.5 text-sm font-semibold transition">
                {{ __('admin.localization.cancel') }}
            </a>
        </div>
    </form>

    {{-- ── Боковая колонка ── --}}
    <aside class="loc-side">
        {{-- Примеры форматирования: ради них сюда и заходят — видно, как
             настройки скажутся на сайте. --}}
        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-eye text-indigo-500"></i> {{ __('admin.localization.examples') }}
            </h2>

            @php
                $examples = [
                    'currency' => 1234.56,
                    'date' => now(),
                    'time' => now(),
                    'number' => 9876543.21,
                ];
            @endphp

            <dl class="loc-facts">
                <div>
                    <dt>{{ __('admin.localization.ex_currency') }}</dt>
                    <dd>{{ $country->formatCurrency($examples['currency']) }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.localization.ex_date') }}</dt>
                    <dd>{{ $country->formatDate($examples['date']) }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.localization.ex_time') }}</dt>
                    <dd>{{ $country->formatTime($examples['time']) }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.localization.ex_number') }}</dt>
                    <dd>{{ $country->formatNumber($examples['number']) }}</dd>
                </div>
            </dl>
        </section>

        <section class="admin-card p-5">
            <h2 class="loc-h2">
                <i class="fas fa-circle-info text-indigo-500"></i> {{ __('admin.localization.tech_info') }}
            </h2>

            <dl class="loc-facts">
                <div>
                    <dt>ID</dt>
                    <dd>{{ $country->id }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.localization.created_at') }}</dt>
                    <dd>{{ $country->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.localization.updated_at') }}</dt>
                    <dd>{{ $country->updated_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.localization.settings_count') }}</dt>
                    <dd>{{ count($settings) }}</dd>
                </div>
            </dl>
        </section>
    </aside>
</div>
@endsection

@include('Localization::admin.partials.form-styles')
