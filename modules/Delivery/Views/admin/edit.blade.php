@extends('layouts.admin')

@section('title', __('admin.delivery.edit'))

@section('content')
    {{-- 🛠️ Заголовок страницы --}}
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2 text-gray-800 dark:text-white">
        ✏️ {{ __('admin.delivery.edit') }}
    </h1>

    {{-- 📝 Форма редактирования метода --}}
    <form method="POST"
          action="{{ route('admin.delivery.update', $delivery) }}"
          class="space-y-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow max-w-2xl w-full mx-auto">
        @csrf
        @method('PUT')

        {{-- 📋 Название --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                📦 {{ __('admin.delivery.name_short') }}
            </label>
            <input type="text" id="title" name="title"
                   value="{{ old('title', $delivery->title) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   required>
        </div>

        {{-- 📝 Описание --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                📝 {{ __('admin.delivery.desc_opt') }}
            </label>
            <textarea id="description" name="description"
                      class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white resize-y"
                      rows="3"
                      placeholder="{{ __('admin.delivery.desc_ph2') }}"
                      title="{{ __('admin.delivery.desc_hint2') }}"
            >{{ old('description', $delivery->description) }}</textarea>
        </div>

        {{-- 💰 Стоимость --}}
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                💰 {{ __('admin.delivery.price') }}
            </label>
            <input type="number" id="price" name="price" step="0.01"
                   value="{{ old('price', $delivery->price) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   required>
        </div>

        {{-- 🚚 Тип доставки --}}
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🚚 {{ __('admin.delivery.type') }}
            </label>
            <select id="type" name="type"
                    class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                    required>
                <option value="courier" {{ old('type', $delivery->type) === 'courier' ? 'selected' : '' }}>🚚 {{ __('admin.delivery.courier') }}</option>
                <option value="pickup" {{ old('type', $delivery->type) === 'pickup' ? 'selected' : '' }}>🛍️ {{ __('admin.delivery.pickup') }}</option>
                <option value="post" {{ old('type', $delivery->type) === 'post' ? 'selected' : '' }}>📦 {{ __('admin.delivery.post') }}</option>
                <option value="terminal" {{ old('type', $delivery->type) === 'terminal' ? 'selected' : '' }}>🏧 {{ __('admin.delivery.terminal') }}</option>
            </select>
        </div>

        {{-- 🔑 Уникальный код --}}
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🔑 {{ __('admin.delivery.code') }}
            </label>
            <input type="text" id="code" name="code"
                   value="{{ old('code', $delivery->code) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white">
        </div>

        {{-- 📅 Сроки доставки --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="min_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    📅 {{ __('admin.delivery.min_days') }}
                </label>
                <input type="number" id="min_days" name="min_days" min="0" max="365"
                       value="{{ old('min_days', $delivery->min_days) }}"
                       class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white">
            </div>
            <div>
                <label for="max_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    📅 {{ __('admin.delivery.max_days') }}
                </label>
                <input type="number" id="max_days" name="max_days" min="0" max="365"
                       value="{{ old('max_days', $delivery->max_days) }}"
                       class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white">
            </div>
        </div>

        {{-- ⚖️ Ограничение по весу --}}
        <div>
            <label for="weight_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ⚖️ {{ __('admin.delivery.weight') }}
            </label>
            <input type="number" id="weight_limit" name="weight_limit" step="0.01" min="0" max="1000"
                   value="{{ old('weight_limit', $delivery->weight_limit) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="{{ __('admin.delivery.weight_hint') }}">
        </div>

        {{-- 🗺️ Доступные регионы --}}
        <div>
            <label for="regions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🗺️ {{ __('admin.delivery.regions') }}
            </label>
            <select id="regions" name="regions[]" multiple
                    class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                    size="8">
                @php
                    $selectedRegions = old('regions', $delivery->regions ?? []);
                @endphp
                <option value="{{ \Modules\Delivery\Models\DeliveryMethod::ALL_REGIONS }}" {{ in_array(\Modules\Delivery\Models\DeliveryMethod::ALL_REGIONS, $selectedRegions) ? 'selected' : '' }}>{{ __('admin.delivery.all_regions') }}</option>
                @foreach(\Modules\Delivery\Models\DeliveryMethod::getRussianRegions() as $region)
                    @if($region !== \Modules\Delivery\Models\DeliveryMethod::ALL_REGIONS)
                        <option value="{{ $region }}" {{ in_array($region, $selectedRegions) ? 'selected' : '' }}>{{ $region }}</option>
                    @endif
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.delivery.regions_hint') }}</p>
        </div>

        {{-- 🇷🇺 Российская служба --}}
        <div class="flex items-center gap-2 mt-2">
            <input type="checkbox" name="is_russian" id="is_russian" value="1"
                   {{ old('is_russian', $delivery->is_russian) ? 'checked' : '' }}
                   class="form-checkbox rounded text-blue-600">
            <label for="is_russian" class="text-sm text-gray-700 dark:text-gray-300">
                🇷🇺 {{ __('admin.delivery.is_russian') }}
            </label>
        </div>

        {{-- 🌐 API интеграция --}}
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="api_enabled" id="api_enabled" value="1"
                       {{ old('api_enabled', $delivery->api_enabled) ? 'checked' : '' }}
                       class="form-checkbox rounded text-blue-600"
                       onchange="toggleApiSettings(this.checked)">
                <label for="api_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                    🌐 {{ __('admin.delivery.api_on') }}
                </label>
            </div>
            
            <div id="api-settings" style="display: {{ old('api_enabled', $delivery->api_enabled) ? 'block' : 'none' }};" class="mt-3 p-4 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">{{ __('admin.delivery.api_settings') }}</p>
                <textarea name="api_settings_json" id="api_settings_json" rows="6"
                          class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-700 dark:text-white font-mono text-xs">{{ old('api_settings_json', $delivery->api_settings ? json_encode($delivery->api_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                                                            {!! __('admin.delivery.creds_hint', ['cdek' => '{"account": "…", "secure_password": "…"}', 'boxberry' => '{"token": "…"}', 'post' => '{"login": "…", "password": "…"}']) !!}
                </p>
            </div>
        </div>

        <script>
            function toggleApiSettings(enabled) {
                document.getElementById('api-settings').style.display = enabled ? 'block' : 'none';
            }
        </script>

        {{-- 🎁 Бесплатная доставка при сумме заказа --}}
        <div>
            <label for="free_delivery_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🎁 {{ __('admin.delivery.free_from') }}
            </label>
            <input type="number" id="free_delivery_threshold" name="free_delivery_threshold" step="0.01" min="0"
                   value="{{ old('free_delivery_threshold', $delivery->free_delivery_threshold) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="{{ __('admin.delivery.free_empty_hint') }}">
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.delivery.free_hint') }}</p>
        </div>

        {{-- 🔢 Порядок сортировки --}}
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🔢 {{ __('admin.delivery.sort') }}
            </label>
            <input type="number" id="sort_order" name="sort_order" min="0"
                   value="{{ old('sort_order', $delivery->sort_order ?? 0) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white">
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.delivery.sort_hint') }}</p>
        </div>

        {{-- ✅ Активность --}}
        <div class="flex items-center gap-2 mt-2">
            <input type="checkbox" name="active" value="1"
                   class="form-checkbox rounded text-blue-600 mr-2"
                   {{ old('active', $delivery->active) ? 'checked' : '' }}>
            {{ __('admin.delivery.active') }}
        </div>

        {{-- 💾 Кнопка обновления --}}
        <div class="text-right pt-4">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow-md transition-all duration-200 transform hover:scale-105">
                💾 {{ __('admin.delivery.update') }}
            </button>
        </div>
    </form>
@endsection
