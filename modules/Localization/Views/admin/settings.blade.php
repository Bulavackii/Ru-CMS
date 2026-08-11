@extends('layouts.admin')

@section('title', __('admin.localization.s_title'))

@section('content')
{{--
    Та же вёрстка, что у списка стран и форм: шапка с акцентной полосой,
    подписи моноширинным капсом, карточки вместо таблицы. Прежняя страница
    оставалась на Bootstrap (h5/h6, table-light, badge bg-info,
    alert-dismissible с кнопками закрытия, которые ничего не закрывали —
    Bootstrap JS в проекте не подключён).

    Имена полей, адреса форм и функции экспорта/импорта не менялись.
--}}

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-5
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="loc-flag-badge">{{ $country->flag ?? '🏳️' }}</span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ __('admin.localization.s_heading') }}: {{ $country->name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.localization.s_sub') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.localization.edit', $country->code) }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-left"></i> {{ __('admin.localization.s_back') }}
    </a>
</div>

@includeIf('layouts.partials.flash')

<div class="set-cols">
    {{-- ── Левая колонка: добавление и быстрые настройки ── --}}
    <div>
        <section class="admin-card p-5 mb-4">
            <h2 class="loc-h2">
                <i class="fas fa-plus text-indigo-500"></i> {{ __('admin.localization.s_add') }}
            </h2>

            <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="key" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.s_key') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="key" name="key" required
                           value="{{ old('key') }}" placeholder="welcome_message"
                           class="loc-input font-mono">
                    <p class="admin-hint mt-1">{{ __('admin.localization.s_key_hint') }}</p>
                    @error('key') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label for="value" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.s_value') }}
                    </label>
                    <textarea id="value" name="value" rows="3"
                              placeholder="{{ __('admin.localization.s_value_ph') }}"
                              class="loc-input">{{ old('value') }}</textarea>
                    @error('value') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label for="type" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.localization.s_type') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type" required class="loc-input">
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="group" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.localization.s_group') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="group" name="group" required class="loc-input">
                            @foreach($groups as $value => $label)
                                <option value="{{ $value }}" @selected(old('group') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="loc-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.localization.s_desc') }}
                    </label>
                    <input type="text" id="description" name="description"
                           value="{{ old('description') }}" placeholder="{{ __('admin.localization.s_desc_ph') }}"
                           class="loc-input">
                </div>

                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-plus"></i> {{ __('admin.localization.s_add') }}
                </button>
            </form>
        </section>

        {{-- Быстрые настройки: три частых параметра одной кнопкой, без
             заполнения ключа, типа и группы руками. --}}
        <section class="admin-card p-5">
            <h2 class="loc-h2">
                <i class="fas fa-bolt text-indigo-500"></i> {{ __('admin.localization.s_quick') }}
            </h2>

            <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="set-quick">
                @csrf
                <input type="hidden" name="key" value="week_start">
                <input type="hidden" name="type" value="number">
                <input type="hidden" name="group" value="date">
                <input type="hidden" name="description" value="{{ __('admin.localization.s_week_start_desc') }}">

                <span class="set-quick__label">{{ __('admin.localization.s_week_start') }}</span>
                <input type="number" name="value" min="0" max="6" class="loc-input"
                       value="{{ $settings['week_start'] ?? 1 }}">
                <button type="submit" class="set-quick__btn" title="{{ __('admin.localization.save') }}">
                    <i class="fas fa-check"></i>
                </button>
            </form>

            <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="set-quick">
                @csrf
                <input type="hidden" name="key" value="tax_rate">
                <input type="hidden" name="type" value="number">
                <input type="hidden" name="group" value="currency">
                <input type="hidden" name="description" value="{{ __('admin.localization.s_tax_desc') }}">

                <span class="set-quick__label">{{ __('admin.localization.s_tax') }}</span>
                <input type="number" name="value" step="0.01" min="0" class="loc-input"
                       value="{{ $settings['tax_rate'] ?? 0 }}">
                <button type="submit" class="set-quick__btn" title="{{ __('admin.localization.save') }}">
                    <i class="fas fa-check"></i>
                </button>
            </form>

            <form action="{{ route('admin.localization.settings.save', $country->code) }}" method="POST" class="set-quick">
                @csrf
                <input type="hidden" name="key" value="currency_position">
                <input type="hidden" name="type" value="string">
                <input type="hidden" name="group" value="currency">
                <input type="hidden" name="description" value="{{ __('admin.localization.s_cur_pos_desc') }}">

                <span class="set-quick__label">{{ __('admin.localization.s_cur_pos') }}</span>
                <select name="value" class="loc-input">
                    <option value="before" @selected(($settings['currency_position'] ?? 'before') === 'before')>
                        {{ __('admin.localization.s_before') }}
                    </option>
                    <option value="after" @selected(($settings['currency_position'] ?? 'before') === 'after')>
                        {{ __('admin.localization.s_after') }}
                    </option>
                </select>
                <button type="submit" class="set-quick__btn" title="{{ __('admin.localization.save') }}">
                    <i class="fas fa-check"></i>
                </button>
            </form>
        </section>
    </div>

    {{-- ── Правая колонка: что уже задано ── --}}
    <div>
        <section class="admin-card p-5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="loc-h2 mb-0">
                    <i class="fas fa-sliders text-indigo-500"></i> {{ __('admin.localization.s_existing') }}
                </h2>
                <span class="set-count">{{ count($settings) }} {{ __('admin.localization.s_count_suffix') }}</span>
            </div>

            @if(empty($settings))
                <div class="set-empty">
                    <i class="fas fa-sliders"></i>
                    <p class="set-empty__title">{{ __('admin.localization.s_empty') }}</p>
                </div>
            @else
                {{-- Карточки вместо таблицы на шесть колонок: значения бывают
                     многострочными (JSON), и в ячейке они разъезжались. --}}
                <div class="set-list mt-3">
                    @foreach($settings as $key => $value)
                        @php
                            $setting = \Modules\Localization\Models\LocalizationSetting::where('country_id', $country->id)
                                ->where('key', $key)
                                ->first();
                        @endphp

                        @if($setting)
                            <article class="set-item">
                                <div class="set-item__head">
                                    <code class="set-key">{{ $key }}</code>

                                    <span class="set-tag">{{ $types[$setting->type] ?? $setting->type }}</span>
                                    <span class="set-tag is-group">{{ $groups[$setting->group] ?? $setting->group }}</span>

                                    <form action="{{ route('admin.localization.settings.delete', $country->code) }}"
                                          method="POST" class="set-item__act"
                                          onsubmit="return confirm(@js(__('admin.localization.s_confirm_delete', ['key' => $key])))">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="key" value="{{ $key }}">

                                        @if($setting->is_system)
                                            {{-- Системную настройку удалять нельзя: на неё
                                                 опирается сама CMS. --}}
                                            <span class="set-lock" title="{{ __('admin.localization.s_system') }}">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        @else
                                            <button type="submit" class="set-del" title="{{ __('admin.localization.s_delete') }}">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </form>
                                </div>

                                <div class="set-item__value">
                                    @if(is_array($value) || is_object($value))
                                        <pre>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span>{{ $value }}</span>
                                    @endif
                                </div>

                                @if($setting->description)
                                    <p class="set-item__desc">{{ $setting->description }}</p>
                                @endif
                            </article>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>

        <section class="admin-card p-5">
            <h2 class="loc-h2">
                <i class="fas fa-file-arrow-down text-indigo-500"></i> {{ __('admin.localization.s_io') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <button type="button" class="set-io" onclick="exportSettings()">
                        <i class="fas fa-download"></i> {{ __('admin.localization.s_export') }}
                    </button>
                    <p class="admin-hint mt-1">{{ __('admin.localization.s_export_hint') }}</p>
                </div>

                <div>
                    <button type="button" class="set-io" onclick="document.getElementById('importFile').click()">
                        <i class="fas fa-upload"></i> {{ __('admin.localization.s_import') }}
                    </button>
                    {{-- Поле выбора файла скрыто: кнопка выше вызывает его сама. --}}
                    <input type="file" id="importFile" accept=".json" class="hidden" onchange="importSettings(event)">
                    <p class="admin-hint mt-1">{{ __('admin.localization.s_import_hint') }}</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@include('Localization::admin.partials.form-styles')

@push('styles')
<style>
    /* Слева — узкая колонка с добавлением, справа широкая со списком:
       значения настроек бывают многострочными. */
    .set-cols{ display:grid; gap:1rem; align-items:start }
    @media (min-width:1180px){ .set-cols{ grid-template-columns:22rem minmax(0,1fr) } }

    .set-count{ padding:.15rem .5rem; font-size:.68rem; font-weight:700;
        color:#3730a3; background:#eef2ff; border:1px solid #c7d2fe;
        font-variant-numeric:tabular-nums }
    .dark .set-count{ color:#c7d2fe; background:#1e1b4b; border-color:#4338ca }

    /* Быстрая настройка: подпись, поле и кнопка в одну строку. */
    .set-quick{ display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.5rem;
        align-items:center; margin-bottom:.6rem }
    .set-quick__label{ grid-column:1 / -1; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .set-quick__btn{ display:inline-flex; align-items:center; justify-content:center;
        width:2.4rem; height:2.4rem; cursor:pointer; font-size:.8rem;
        color:#4b5563; background:var(--surface,#fff); border:1px solid #d1d5db;
        transition:border-color .15s, color .15s }
    .set-quick__btn:hover{ border-color:#6366f1; color:#4338ca }
    .dark .set-quick__btn{ color:#d1d5db; background:#111827; border-color:#4b5563 }

    .set-list{ display:grid; gap:.5rem }
    .set-item{ padding:.7rem .85rem; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#eef2f7); transition:border-color .15s }
    .set-item:hover{ border-color:#c7d2fe }

    .set-item__head{ display:flex; flex-wrap:wrap; align-items:center; gap:.4rem }
    .set-key{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.8rem;
        font-weight:700; color:var(--surface-ink,#111827) }

    .set-tag{ padding:.1rem .4rem; font-size:.66rem; font-weight:600;
        color:#4b5563; background:#f3f4f6; border:1px solid #e5e7eb }
    .set-tag.is-group{ color:#3730a3; background:#eef2ff; border-color:#c7d2fe }
    .dark .set-tag{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .set-item__act{ margin-left:auto; display:inline-flex }
    .set-del, .set-lock{ display:inline-flex; align-items:center; justify-content:center;
        width:1.85rem; height:1.85rem; font-size:.72rem; border:1px solid #e5e7eb;
        color:#6b7280; background:var(--surface,#fff) }
    .set-del{ cursor:pointer; transition:border-color .15s, color .15s }
    .set-del:hover{ border-color:#dc2626; color:#b91c1c }
    .set-lock{ color:#b45309; background:#fffbeb; border-color:#fde68a }

    .set-item__value{ margin-top:.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.82rem; color:var(--surface-ink,#111827); word-break:break-word }
    .set-item__value pre{ margin:0; white-space:pre-wrap; font-size:.75rem }

    .set-item__desc{ margin:.3rem 0 0; font-size:.75rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    .set-empty{ padding:2.5rem 1rem; text-align:center }
    .set-empty i{ display:block; margin-bottom:.6rem; font-size:1.75rem; color:#c7d2fe }
    .set-empty__title{ margin:0; font-size:.95rem; font-weight:700; color:var(--surface-ink,#111827) }

    .set-io{ display:flex; align-items:center; justify-content:center; gap:.5rem; width:100%;
        padding:.6rem 1rem; font-size:.85rem; font-weight:600; cursor:pointer;
        color:#4b5563; background:var(--surface,#fff); border:1px solid #d1d5db;
        transition:border-color .15s, color .15s }
    .set-io:hover{ border-color:#6366f1; color:#4338ca }
    .dark .set-io{ color:#d1d5db; background:#111827; border-color:#4b5563 }
</style>
@endpush

@push('scripts')
<script>
    // Экспорт настроек страны в JSON-файл.
    function exportSettings() {
        const settings = @json($settings);
        const country = @json($country->code);

        const dataStr = JSON.stringify(settings, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `localization_${country}_settings.json`;
        link.click();

        URL.revokeObjectURL(url);
    }

    // Импорт: настройки отправляются по одной тем же маршрутом, что и
    // форма добавления.
    function importSettings(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            try {
                const settings = JSON.parse(e.target.result);

                if (!confirm(@js(__('admin.localization.s_import_confirm')).replace(':count', Object.keys(settings).length))) {
                    return;
                }

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
