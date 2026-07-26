@extends('layouts.admin')

@section('title', 'Добавить страну')

@section('content')
<div class="">
    <!-- Заголовок -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">🌍 Добавить новую страну</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-0">Настройте параметры локализации для страны</p>
        </div>
        <a href="{{ route('admin.localization.index') }}" class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>

    <!-- Форма -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2">
            <div class="admin-card">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h5 class="mb-0">Основные параметры</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('admin.localization.store') }}" method="POST">
                        @csrf

                        <!-- Быстрый импорт из пресетов -->
                        @if(!empty($presets))
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">⚡ Быстрое заполнение из пресета</label>
                            <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" id="presetSelect">
                                <option value="">-- Выберите пресет --</option>
                                @foreach($presets as $code => $data)
                                <option value="{{ $code }}">{{ $data['flag'] ?? '🏳️' }} {{ $data['name'] }} ({{ $code }})</option>
                                @endforeach
                            </select>
                            <small class="text-gray-500 dark:text-gray-400">Выберите пресет, чтобы автоматически заполнить поля</small>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <!-- Код страны -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Код (ISO 2) *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('code') is-invalid @enderror"
                                           id="code" name="code" value="{{ old('code') }}"
                                           placeholder="RU" maxlength="2" required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-gray-500 dark:text-gray-400">2 буквы (RU, US, DE)</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Название -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Название *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}"
                                           placeholder="Россия" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <!-- Название на родном языке -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="native_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Название (родное)</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('native_name') is-invalid @enderror"
                                           id="native_name" name="native_name" value="{{ old('native_name') }}"
                                           placeholder="Россия">
                                    @error('native_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Флаг -->
                            <div class="">
                                <div class="mb-3">
                                    <label for="flag" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Флаг (эмодзи)</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('flag') is-invalid @enderror"
                                           id="flag" name="flag" value="{{ old('flag') }}"
                                           placeholder="🇷🇺">
                                    @error('flag')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">💰 Валюта</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="currency_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Код валюты *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('currency_code') is-invalid @enderror"
                                           id="currency_code" name="currency_code" value="{{ old('currency_code') }}"
                                           placeholder="RUB" maxlength="3" required>
                                    @error('currency_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="currency_symbol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Символ валюты</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('currency_symbol') is-invalid @enderror"
                                           id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol') }}"
                                           placeholder="₽">
                                    @error('currency_symbol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">🌐 Локаль и время</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="locale" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Локаль *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('locale') is-invalid @enderror"
                                           id="locale" name="locale" value="{{ old('locale') }}"
                                           placeholder="ru_RU" required>
                                    @error('locale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-gray-500 dark:text-gray-400">ru_RU, en_US, de_DE</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="">
                                <div class="mb-3">
                                    <label for="timezone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Часовой пояс *</label>
                                    <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('timezone') is-invalid @enderror"
                                           id="timezone" name="timezone" value="{{ old('timezone') }}"
                                           placeholder="Europe/Moscow" required>
                                    @error('timezone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-gray-500 dark:text-gray-400">Europe/Moscow, America/New_York</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">📅 Форматы</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="date_format" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Формат даты *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('date_format') is-invalid @enderror"
                                            id="date_format" name="date_format" required>
                                        @foreach($dateFormats as $value => $label)
                                        <option value="{{ $value }}" {{ old('date_format') === $value ? 'selected' : '' }}>
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
                                    <label for="time_format" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Формат времени *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('time_format') is-invalid @enderror"
                                            id="time_format" name="time_format" required>
                                        @foreach($timeFormats as $value => $label)
                                        <option value="{{ $value }}" {{ old('time_format') === $value ? 'selected' : '' }}>
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

                        <h6 class="mb-3">🔢 Форматирование чисел</h6>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="decimal_separator" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Разделитель дробной части *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('decimal_separator') is-invalid @enderror"
                                            id="decimal_separator" name="decimal_separator" required>
                                        @foreach($decimalSeparators as $value => $label)
                                        <option value="{{ $value }}" {{ old('decimal_separator') === $value ? 'selected' : '' }}>
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
                                    <label for="thousands_separator" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Разделитель тысяч *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('thousands_separator') is-invalid @enderror"
                                            id="thousands_separator" name="thousands_separator" required>
                                        @foreach($thousandsSeparators as $value => $label)
                                        <option value="{{ $value }}" {{ old('thousands_separator') === $value ? 'selected' : '' }}>
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
                                    <label for="decimal_places" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Знаков после запятой *</label>
                                    <input type="number" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('decimal_places') is-invalid @enderror"
                                           id="decimal_places" name="decimal_places" value="{{ old('decimal_places', 2) }}"
                                           min="0" max="6" required>
                                    @error('decimal_places')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Статус -->
                        <div class="mb-3">
                            <div class="flex items-center gap-2 form-switch">
                                <input class="border-gray-400" type="checkbox" id="active" name="active"
                                       {{ old('active', true) ? 'checked' : '' }} checked>
                                <label class="text-sm text-gray-700 dark:text-gray-300" for="active">
                                    Активна (страна будет доступна для использования)
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 mt-4">
                            <a href="{{ route('admin.localization.index') }}" class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">Отмена</a>
                            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                                <i class="fas fa-save"></i> Создать страну
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Подсказки -->
        <div class="">
            <div class="admin-card bg-light">
                <div class="p-5">
                    <h6 class="mb-3">ℹ️ Подсказки</h6>
                    <ul class="small text-muted mb-0" style="list-style: none; padding-left: 0;">
                        <li class="mb-2"><i class="fas fa-info-circle text-info"></i> Код страны должен быть уникальным</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info"></i> Используйте ISO 3166-1 alpha-2</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info"></i> Локаль влияет на переводы</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info"></i> Часовой пояс важен для дат</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info"></i> Можно добавить доп. настройки позже</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Заполнение из пресета
    document.getElementById('presetSelect')?.addEventListener('change', function(e) {
        const presets = @json($presets);
        const code = e.target.value;

        if (!code || !presets[code]) return;

        const preset = presets[code];

        // Заполняем поля
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
