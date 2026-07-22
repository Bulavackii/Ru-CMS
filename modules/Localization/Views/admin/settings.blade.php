@extends('layouts.admin')

@section('title', 'Настройки страны')

@section('content')
<div class="container-fluid py-4">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">⚙️ Настройки: {{ $country->flag ?? '🏳️' }} {{ $country->name }}</h1>
            <p class="text-muted mb-0">Дополнительные параметры локализации</p>
        </div>
        <a href="{{ route('admin.localization.edit', $country->code) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Назад к стране
        </a>
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

    <div class="row">
        <!-- Форма добавления настройки -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">➕ Добавить настройку</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="key" class="form-label">Ключ *</label>
                            <input type="text" class="form-control @error('key') is-invalid @enderror"
                                   id="key" name="key" value="{{ old('key') }}"
                                   placeholder="welcome_message" required>
                            @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Латинские буквы, цифры, подчеркивания</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="value" class="form-label">Значение</label>
                            <textarea class="form-control @error('value') is-invalid @enderror"
                                      id="value" name="value" rows="3"
                                      placeholder="Введите значение...">{{ old('value') }}</textarea>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Тип *</label>
                                    <select class="form-select @error('type') is-invalid @enderror"
                                            id="type" name="type" required>
                                        @foreach($types as $value => $label)
                                        <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="group" class="form-label">Группа *</label>
                                    <select class="form-select @error('group') is-invalid @enderror"
                                            id="group" name="group" required>
                                        @foreach($groups as $value => $label)
                                        <option value="{{ $value }}" {{ old('group') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror"
                                   id="description" name="description" value="{{ old('description') }}"
                                   placeholder="Описание настройки">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Добавить настройку
                        </button>
                    </form>
                </div>
            </div>

            <!-- Быстрые настройки -->
            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">⚡ Быстрые настройки</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="key" value="week_start">
                        <input type="hidden" name="type" value="number">
                        <input type="hidden" name="group" value="date">
                        <input type="hidden" name="description" value="Первый день недели (0=Вс, 1=Пн)">
                        <div class="input-group">
                            <span class="input-group-text">Первый день недели</span>
                            <input type="number" class="form-control" name="value"
                                   value="{{ $settings['week_start'] ?? 1 }}" min="0" max="1">
                            <button class="btn btn-outline-primary" type="submit">✓</button>
                        </div>
                    </form>

                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="key" value="tax_rate">
                        <input type="hidden" name="type" value="number">
                        <input type="hidden" name="group" value="currency">
                        <input type="hidden" name="description" value="Ставка налога (%)">
                        <div class="input-group">
                            <span class="input-group-text">Налог (%)</span>
                            <input type="number" class="form-control" name="value"
                                   value="{{ $settings['tax_rate'] ?? 0 }}" min="0" max="100" step="0.1">
                            <button class="btn btn-outline-primary" type="submit">✓</button>
                        </div>
                    </form>

                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST">
                        @csrf
                        <input type="hidden" name="key" value="currency_position">
                        <input type="hidden" name="type" value="string">
                        <input type="hidden" name="group" value="currency">
                        <input type="hidden" name="description" value="Позиция символа валюты">
                        <div class="input-group">
                            <span class="input-group-text">Позиция валюты</span>
                            <select class="form-select" name="value">
                                <option value="before" {{ ($settings['currency_position'] ?? 'before') === 'before' ? 'selected' : '' }}>Слева</option>
                                <option value="after" {{ ($settings['currency_position'] ?? 'before') === 'after' ? 'selected' : '' }}>Справа</option>
                            </select>
                            <button class="btn btn-outline-primary" type="submit">✓</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Список существующих настроек -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Существующие настройки</h5>
                    <span class="badge bg-primary">{{ count($settings) }} шт.</span>
                </div>
                <div class="card-body p-0">
                    @if(empty($settings))
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-cog fa-3x mb-3"></i>
                        <p>Настройки отсутствуют. Добавьте первую настройку.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ключ</th>
                                    <th>Значение</th>
                                    <th>Тип</th>
                                    <th>Группа</th>
                                    <th>Описание</th>
                                    <th class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($settings as $key => $value)
                                @php
                                    $setting = \Modules\Localization\Models\LocalizationSetting::where('country_id', $country->id)->where('key', $key)->first();
                                @endphp
                                @if($setting)
                                <tr>
                                    <td><code>{{ $key }}</code></td>
                                    <td>
                                        @if(is_array($value) || is_object($value))
                                            <pre class="small mb-0">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            <span class="font-monospace">{{ $value }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $types[$setting->type] ?? $setting->type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $groups[$setting->group] ?? $setting->group }}</span>
                                    </td>
                                    <td class="text-muted small">{{ $setting->description }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.localization.settings.delete', $country->code) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Удалить настройку {{ $key }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="key" value="{{ $key }}">
                                            @if($setting->is_system)
                                                <span class="badge bg-warning text-dark" title="Системная настройка">🔒</span>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            <!-- JSON экспорт/импорт -->
            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">📦 Импорт/Экспорт JSON</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-outline-success w-100 mb-2" onclick="exportSettings()">
                                <i class="fas fa-download"></i> Экспорт настроек
                            </button>
                            <small class="text-muted">Скачать все настройки этой страны в JSON</small>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-warning w-100 mb-2" onclick="document.getElementById('importFile').click()">
                                <i class="fas fa-upload"></i> Импорт настроек
                            </button>
                            <input type="file" id="importFile" accept=".json" style="display: none;" onchange="importSettings(event)">
                            <small class="text-muted">Загрузить настройки из JSON файла</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Экспорт настроек
    function exportSettings() {
        const settings = @json($settings);
        const country = @json($country->code);

        const dataStr = JSON.stringify(settings, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `localization_${country}_settings.json`;
        link.click();

        URL.revokeObjectURL(url);
    }

    // Импорт настроек
    function importSettings(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);

                if (!confirm(`Импортировать ${Object.keys(settings).length} настроек? Это перезапишет существующие значения.`)) {
                    return;
                }

                // Отправляем настройки по одной
                const country = @json($country->code);
                let imported = 0;
                const total = Object.keys(settings).length;

                for (const [key, value] of Object.entries(settings)) {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('key', key);
                    formData.append('value', typeof value === 'object' ? JSON.stringify(value) : value);
                    formData.append('type', Array.isArray(value) ? 'array' : (typeof value === 'object' ? 'json' : 'string'));
                    formData.append('group', 'imported');
                    formData.append('description', 'Импортировано из JSON');

                    fetch(`/admin/localization/settings/${country}/save`, {
                        method: 'POST',
                        body: formData
                    }).then(() => {
                        imported++;
                        if (imported === total) {
                            location.reload();
                        }
                    });
                }
            } catch (error) {
                alert('Ошибка импорта: ' + error.message);
            }
        };
        reader.readAsText(file);
    }
</script>
@endpush
