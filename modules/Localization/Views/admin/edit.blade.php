@extends('layouts.admin')

@section('title', __('admin.localization.edit_title'))

@section('content')
<div class="">
    <!-- Заголовок -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">🌍 {{ __('admin.localization.edit_heading') }}: {{ $country->flag ?? '🏳️' }} {{ $country->name }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-0">{{ __('admin.localization.code_label') }}: <code>{{ $country->code }}</code></p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.localization.index') }}" class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-arrow-left"></i> {{ __('admin.localization.back') }}
            </a>
            <a href="{{ route('admin.localization.settings', $country->code) }}" class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-cog"></i> {{ __('admin.localization.settings_link') }}
            </a>
        </div>
    </div>

    <!-- Уведомления -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="admin-card border-l-4 border-red-500 p-4 alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Форма -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2">
            <div class="admin-card">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h5 class="mb-0">{{ __('admin.localization.main_params') }}</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('admin.localization.update', $country->code) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <!-- Название -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_name') }} *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $country->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Название на родном языке -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="native_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_native') }}</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('native_name') is-invalid @enderror"
                                           id="native_name" name="native_name" value="{{ old('native_name', $country->native_name) }}">
                                    @error('native_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <!-- Флаг -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="flag" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_flag') }}</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('flag') is-invalid @enderror"
                                           id="flag" name="flag" value="{{ old('flag', $country->flag) }}">
                                    @error('flag')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Статус -->
                            <div class="">
                                <div class="mb-3">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 d-block">{{ __('admin.localization.status') }}</label>
                                    <div class="flex items-center gap-2 form-switch">
                                        <input class="border-gray-400" type="checkbox" id="active" name="active"
                                               {{ old('active', $country->active) ? 'checked' : '' }}>
                                        <label class="text-sm text-gray-700 dark:text-gray-300" for="active">
                                            {{ __('admin.localization.active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">💰 {{ __('admin.localization.g_currency') }}</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="currency_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_currency_code') }} *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('currency_code') is-invalid @enderror"
                                           id="currency_code" name="currency_code" value="{{ old('currency_code', $country->currency_code) }}"
                                           maxlength="3" required>
                                    @error('currency_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="currency_symbol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_currency_symbol') }}</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('currency_symbol') is-invalid @enderror"
                                           id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $country->currency_symbol) }}">
                                    @error('currency_symbol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">🌐 {{ __('admin.localization.g_locale_time') }}</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="locale" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_locale') }} *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('locale') is-invalid @enderror"
                                           id="locale" name="locale" value="{{ old('locale', $country->locale) }}" required>
                                    @error('locale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="timezone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_timezone') }} *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('timezone') is-invalid @enderror"
                                           id="timezone" name="timezone" value="{{ old('timezone', $country->timezone) }}" required>
                                    @error('timezone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">📅 {{ __('admin.localization.g_formats') }}</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="date_format" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_date_format') }} *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('date_format') is-invalid @enderror"
                                            id="date_format" name="date_format" required>
                                        @foreach($dateFormats as $value => $label)
                                        <option value="{{ $value }}" {{ old('date_format', $country->date_format) === $value ? 'selected' : '' }}>
                                            {{ $label }} ({{ $value }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('date_format')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="time_format" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_time_format') }} *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('time_format') is-invalid @enderror"
                                            id="time_format" name="time_format" required>
                                        @foreach($timeFormats as $value => $label)
                                        <option value="{{ $value }}" {{ old('time_format', $country->time_format) === $value ? 'selected' : '' }}>
                                            {{ $label }} ({{ $value }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('time_format')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">🔢 {{ __('admin.localization.g_numbers') }}</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="decimal_separator" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_decimal_sep') }} *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('decimal_separator') is-invalid @enderror"
                                            id="decimal_separator" name="decimal_separator" required>
                                        @foreach($decimalSeparators as $value => $label)
                                        <option value="{{ $value }}" {{ old('decimal_separator', $country->decimal_separator) === $value ? 'selected' : '' }}>
                                            {{ $label }} ({{ $value }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('decimal_separator')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="thousands_separator" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_thousands_sep') }} *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('thousands_separator') is-invalid @enderror"
                                            id="thousands_separator" name="thousands_separator" required>
                                        @foreach($thousandsSeparators as $value => $label)
                                        <option value="{{ $value }}" {{ old('thousands_separator', $country->thousands_separator) === $value ? 'selected' : '' }}>
                                            {{ $label }} ({{ $value }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('thousands_separator')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="decimal_places" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.f_decimals') }} *</label>
                                    <input type="number" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('decimal_places') is-invalid @enderror"
                                           id="decimal_places" name="decimal_places" value="{{ old('decimal_places', $country->decimal_places) }}"
                                           min="0" max="6" required>
                                    @error('decimal_places')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 mt-4">
                            <a href="{{ route('admin.localization.index') }}" class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">{{ __('admin.localization.cancel') }}</a>
                            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                                <i class="fas fa-save"></i> {{ __('admin.localization.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="">
            <!-- Примеры форматирования -->
            <div class="admin-card mb-3">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h6 class="mb-0">📊 {{ __('admin.localization.examples') }}</h6>
                </div>
                <div class="p-5">
                    @php
                        $examples = [
                            'currency' => 1234.56,
                            'date' => now(),
                            'time' => now(),
                            'number' => 9876543.21,
                        ];
                    @endphp
                    <table class="table table-sm small mb-0">
                        <tbody>
                            <tr>
                                <td>{{ __('admin.localization.ex_currency') }}:</td>
                                <td class="text-right"><strong>{{ $country->formatCurrency($examples['currency']) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('admin.localization.ex_date') }}:</td>
                                <td class="text-right"><strong>{{ $country->formatDate($examples['date']) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('admin.localization.ex_time') }}:</td>
                                <td class="text-right"><strong>{{ $country->formatTime($examples['time']) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('admin.localization.ex_number') }}:</td>
                                <td class="text-right"><strong>{{ $country->formatNumber($examples['number']) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Информация -->
            <div class="admin-card">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h6 class="mb-0">ℹ️ {{ __('admin.localization.tech_info') }}</h6>
                </div>
                <div class="p-5 small">
                    <p class="mb-2"><strong>ID:</strong> {{ $country->id }}</p>
                    <p class="mb-2"><strong>{{ __('admin.localization.created_at') }}:</strong> {{ $country->created_at->format('d.m.Y H:i') }}</p>
                    <p class="mb-2"><strong>{{ __('admin.localization.updated_at') }}:</strong> {{ $country->updated_at->format('d.m.Y H:i') }}</p>
                    <p class="mb-0"><strong>{{ __('admin.localization.settings_count') }}:</strong> {{ count($settings) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Автоподстановка примеров при изменении полей
    const form = document.querySelector('form');
    const updateExamples = () => {
        // Можно добавить AJAX для обновления примеров в реальном времени
        console.log('Поля изменены, примеры могут обновиться');
    };

    ['date_format', 'time_format', 'decimal_separator', 'thousands_separator', 'decimal_places'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', updateExamples);
        }
    });
</script>
@endpush
