@extends('System::Views.admin.modules')

@section('content')
<div class="container-fluid py-4">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">🌍 Редактирование страны: {{ $country->flag ?? '🏳️' }} {{ $country->name }}</h1>
            <p class="text-muted mb-0">Код: <code>{{ $country->code }}</code></p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.localization.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
            <a href="{{ route('admin.localization.settings', $country->code) }}" class="btn btn-outline-primary">
                <i class="fas fa-cog"></i> Настройки
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
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Форма -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Основные параметры</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.localization.update', $country->code) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Название -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Название *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $country->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Название на родном языке -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="native_name" class="form-label">Название (родное)</label>
                                    <input type="text" class="form-control @error('native_name') is-invalid @enderror"
                                           id="native_name" name="native_name" value="{{ old('native_name', $country->native_name) }}">
                                    @error('native_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Флаг -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="flag" class="form-label">Флаг (эмодзи)</label>
                                    <input type="text" class="form-control @error('flag') is-invalid @enderror"
                                           id="flag" name="flag" value="{{ old('flag', $country->flag) }}">
                                    @error('flag')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Статус -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label d-block">Статус</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="active" name="active"
                                               {{ old('active', $country->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">
                                            Активна
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">💰 Валюта</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="currency_code" class="form-label">Код валюты *</label>
                                    <input type="text" class="form-control @error('currency_code') is-invalid @enderror"
                                           id="currency_code" name="currency_code" value="{{ old('currency_code', $country->currency_code) }}"
                                           maxlength="3" required>
                                    @error('currency_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="currency_symbol" class="form-label">Символ валюты</label>
                                    <input type="text" class="form-control @error('currency_symbol') is-invalid @enderror"
                                           id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $country->currency_symbol) }}">
                                    @error('currency_symbol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">🌐 Локаль и время</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="locale" class="form-label">Локаль *</label>
                                    <input type="text" class="form-control @error('locale') is-invalid @enderror"
                                           id="locale" name="locale" value="{{ old('locale', $country->locale) }}" required>
                                    @error('locale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Часовой пояс *</label>
                                    <input type="text" class="form-control @error('timezone') is-invalid @enderror"
                                           id="timezone" name="timezone" value="{{ old('timezone', $country->timezone) }}" required>
                                    @error('timezone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">📅 Форматы</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Формат даты *</label>
                                    <select class="form-select @error('date_format') is-invalid @enderror"
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

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="time_format" class="form-label">Формат времени *</label>
                                    <select class="form-select @error('time_format') is-invalid @enderror"
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

                        <h6 class="mb-3">🔢 Форматирование чисел</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="decimal_separator" class="form-label">Разделитель дробной части *</label>
                                    <select class="form-select @error('decimal_separator') is-invalid @enderror"
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

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="thousands_separator" class="form-label">Разделитель тысяч *</label>
                                    <select class="form-select @error('thousands_separator') is-invalid @enderror"
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

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="decimal_places" class="form-label">Знаков после запятой *</label>
                                    <input type="number" class="form-control @error('decimal_places') is-invalid @enderror"
                                           id="decimal_places" name="decimal_places" value="{{ old('decimal_places', $country->decimal_places) }}"
                                           min="0" max="6" required>
                                    @error('decimal_places')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.localization.index') }}" class="btn btn-outline-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="col-lg-4">
            <!-- Примеры форматирования -->
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">📊 Примеры форматирования</h6>
                </div>
                <div class="card-body">
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
                                <td>Валюта:</td>
                                <td class="text-end"><strong>{{ $country->formatCurrency($examples['currency']) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Дата:</td>
                                <td class="text-end"><strong>{{ $country->formatDate($examples['date']) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Время:</td>
                                <td class="text-end"><strong>{{ $country->formatTime($examples['time']) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Число:</td>
                                <td class="text-end"><strong>{{ $country->formatNumber($examples['number']) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Информация -->
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">ℹ️ Техническая информация</h6>
                </div>
                <div class="card-body small">
                    <p class="mb-2"><strong>ID:</strong> {{ $country->id }}</p>
                    <p class="mb-2"><strong>Создано:</strong> {{ $country->created_at->format('d.m.Y H:i') }}</p>
                    <p class="mb-2"><strong>Обновлено:</strong> {{ $country->updated_at->format('d.m.Y H:i') }}</p>
                    <p class="mb-0"><strong>Настроек:</strong> {{ count($settings) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

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
