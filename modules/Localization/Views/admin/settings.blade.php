@extends('layouts.admin')

@section('title', __('admin.localization.s_title'))

@section('content')
<div class="">
    <!-- Заголовок -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">⚙️ {{ __('admin.localization.s_heading') }}: {{ $country->flag ?? '🏳️' }} {{ $country->name }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-0">{{ __('admin.localization.s_sub') }}</p>
        </div>
        <a href="{{ route('admin.localization.edit', $country->code) }}" class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
            <i class="fas fa-arrow-left"></i> {{ __('admin.localization.s_back') }}
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
    <div class="admin-card border-l-4 border-red-500 p-4 alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Форма добавления настройки -->
        <div class="">
            <div class="admin-card">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h5 class="mb-0">➕ {{ __('admin.localization.s_add') }}</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="key" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.s_key') }} *</label>
                            <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('key') is-invalid @enderror"
                                   id="key" name="key" value="{{ old('key') }}"
                                   placeholder="welcome_message" required>
                            @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-gray-500 dark:text-gray-400">{{ __('admin.localization.s_key_hint') }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="value" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.s_value') }}</label>
                            <textarea class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('value') is-invalid @enderror"
                                      id="value" name="value" rows="3"
                                      placeholder="{{ __('admin.localization.s_value_ph') }}">{{ old('value') }}</textarea>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="">
                                <div class="mb-3">
                                    <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.s_type') }} *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('type') is-invalid @enderror"
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

                            <div class="">
                                <div class="mb-3">
                                    <label for="group" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.s_group') }} *</label>
                                    <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('group') is-invalid @enderror"
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
                            <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.localization.s_desc') }}</label>
                            <input type="text" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition @error('description') is-invalid @enderror"
                                   id="description" name="description" value="{{ old('description') }}"
                                   placeholder="{{ __('admin.localization.s_desc_ph') }}">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition w-100">
                            <i class="fas fa-plus"></i> {{ __('admin.localization.s_add') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Быстрые настройки -->
            <div class="admin-card mt-3">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h6 class="mb-0">⚡ {{ __('admin.localization.s_quick') }}</h6>
                </div>
                <div class="p-5">
                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="key" value="week_start">
                        <input type="hidden" name="type" value="number">
                        <input type="hidden" name="group" value="date">
                        <input type="hidden" name="description" value="{{ __('admin.localization.s_week_start_desc') }}">
                        <div class="flex gap-2">
                            <span class="input-group-text">{{ __('admin.localization.s_week_start') }}</span>
                            <input type="number" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" name="value"
                                   value="{{ $settings['week_start'] ?? 1 }}" min="0" max="1">
                            <button class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition" type="submit">✓</button>
                        </div>
                    </form>

                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="key" value="tax_rate">
                        <input type="hidden" name="type" value="number">
                        <input type="hidden" name="group" value="currency">
                        <input type="hidden" name="description" value="{{ __('admin.localization.s_tax_desc') }}">
                        <div class="flex gap-2">
                            <span class="input-group-text">{{ __('admin.localization.s_tax') }}</span>
                            <input type="number" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" name="value"
                                   value="{{ $settings['tax_rate'] ?? 0 }}" min="0" max="100" step="0.1">
                            <button class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition" type="submit">✓</button>
                        </div>
                    </form>

                    <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST">
                        @csrf
                        <input type="hidden" name="key" value="currency_position">
                        <input type="hidden" name="type" value="string">
                        <input type="hidden" name="group" value="currency">
                        <input type="hidden" name="description" value="{{ __('admin.localization.s_cur_pos_desc') }}">
                        <div class="flex gap-2">
                            <span class="input-group-text">{{ __('admin.localization.s_cur_pos') }}</span>
                            <select class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" name="value">
                                <option value="before" {{ ($settings['currency_position'] ?? 'before') === 'before' ? 'selected' : '' }}>{{ __('admin.localization.s_before') }}</option>
                                <option value="after" {{ ($settings['currency_position'] ?? 'before') === 'after' ? 'selected' : '' }}>{{ __('admin.localization.s_after') }}</option>
                            </select>
                            <button class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition" type="submit">✓</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Список существующих настроек -->
        <div class="lg:col-span-2">
            <div class="admin-card">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white flex flex-wrap items-center justify-between gap-3">
                    <h5 class="mb-0">{{ __('admin.localization.s_existing') }}</h5>
                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5">{{ count($settings) }} {{ __('admin.localization.s_count_suffix') }}</span>
                </div>
                <div class="p-5 p-0">
                    @if(empty($settings))
                    <div class="text-center py-5 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-cog fa-3x mb-3"></i>
                        <p>{{ __('admin.localization.s_empty') }}</p>
                    </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('admin.localization.s_key') }}</th>
                                    <th>{{ __('admin.localization.s_value') }}</th>
                                    <th>{{ __('admin.localization.s_type') }}</th>
                                    <th>{{ __('admin.localization.s_group') }}</th>
                                    <th>{{ __('admin.localization.s_desc') }}</th>
                                    <th class="text-right">{{ __('admin.localization.s_th_actions') }}</th>
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
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5">{{ $types[$setting->type] ?? $setting->type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $groups[$setting->group] ?? $setting->group }}</span>
                                    </td>
                                    <td class="text-gray-500 dark:text-gray-400 small">{{ $setting->description }}</td>
                                    <td class="text-right">
                                        <form action="{{ route('admin.localization.settings.delete', $country->code) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm(@js(__('admin.localization.s_confirm_delete', ['key' => $key])))">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="key" value="{{ $key }}">
                                            @if($setting->is_system)
                                                <span class="badge bg-warning text-dark" title="{{ __('admin.localization.s_system') }}">🔒</span>
                                            @else
                                                <button type="submit" class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 hover:border-red-400 hover:text-red-600 px-4 py-2 text-sm font-semibold transition" title="{{ __('admin.localization.s_delete') }}">
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
            <div class="admin-card mt-3">
                <div class="px-5 pt-5 pb-1 font-semibold text-gray-900 dark:text-white">
                    <h6 class="mb-0">📦 {{ __('admin.localization.s_io') }}</h6>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <div class="">
                            <button class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-indigo-400 hover:text-indigo-600 px-4 py-2 text-sm font-semibold transition w-full mb-2" onclick="exportSettings()">
                                <i class="fas fa-download"></i> {{ __('admin.localization.s_export') }}
                            </button>
                            <small class="text-gray-500 dark:text-gray-400">{{ __('admin.localization.s_export_hint') }}</small>
                        </div>
                        <div class="">
                            <button class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-indigo-400 hover:text-indigo-600 px-4 py-2 text-sm font-semibold transition w-full mb-2" onclick="document.getElementById('importFile').click()">
                                <i class="fas fa-upload"></i> {{ __('admin.localization.s_import') }}
                            </button>
                            <input type="file" id="importFile" accept=".json" style="display: none;" onchange="importSettings(event)">
                            <small class="text-gray-500 dark:text-gray-400">{{ __('admin.localization.s_import_hint') }}</small>
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

                if (!confirm(@js(__('admin.localization.s_import_confirm')).replace(':count', Object.keys(settings).length))) {
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
                    formData.append('description', @js(__('admin.localization.s_imported_from_json')));

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
                alert(@js(__('admin.localization.s_import_error')) + ' ' + error.message);
            }
        };
        reader.readAsText(file);
    }
</script>
@endpush
