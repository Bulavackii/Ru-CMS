@extends('layouts.admin')

@section('title', 'Добавить метод доставки')

@section('content')
    {{-- 🔰 Заголовок страницы --}}
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2 text-gray-800 dark:text-white">
        ➕ Добавить метод доставки
    </h1>

    {{-- 📝 Форма создания метода доставки --}}
    <form method="POST"
          action="{{ route('admin.delivery.store') }}"
          class="space-y-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow max-w-2xl w-full mx-auto">
        @csrf

        {{-- 📋 Название метода --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🏷️ Название метода доставки
            </label>
            <input type="text" id="title" name="title"
                   value="{{ old('title') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Например: Курьером, Почта России, Самовывоз"
                   title="Введите понятное название метода доставки"
                   required>
        </div>

        {{-- 📝 Описание (опционально) --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                📄 Описание (необязательно)
            </label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                      placeholder="Например: Доставка курьером по Москве в течение 2-3 дней"
                      title="Уточните условия или сроки этого метода доставки">{{ old('description') }}</textarea>
        </div>

        {{-- 💰 Стоимость --}}
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                💰 Стоимость (₽)
            </label>
            <input type="number" id="price" name="price" step="0.01"
                   value="{{ old('price') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Укажите цену, например: 299"
                   title="Укажите стоимость данного метода доставки в рублях"
                   required>
        </div>

        {{-- 🚚 Тип доставки --}}
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🚚 Тип доставки
            </label>
            <select id="type" name="type"
                    class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                    required>
                <option value="courier" {{ old('type') === 'courier' ? 'selected' : '' }}>🚚 Курьерская доставка</option>
                <option value="pickup" {{ old('type') === 'pickup' ? 'selected' : '' }}>🛍️ Самовывоз (Пункт выдачи)</option>
                <option value="post" {{ old('type') === 'post' ? 'selected' : '' }}>📦 Почтовая доставка</option>
                <option value="terminal" {{ old('type') === 'terminal' ? 'selected' : '' }}>🏧 Терминал/Почтомат</option>
            </select>
        </div>

        {{-- 🔑 Уникальный код --}}
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🔑 Уникальный код (опционально)
            </label>
            <input type="text" id="code" name="code"
                   value="{{ old('code') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Например: cdek, pek, boxberry, pochta">
            <p class="text-xs text-gray-500 mt-1">Используется для внутренней идентификации</p>
        </div>

        {{-- 📅 Сроки доставки --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="min_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    📅 Мин. срок (дни)
                </label>
                <input type="number" id="min_days" name="min_days" min="0" max="365"
                       value="{{ old('min_days') }}"
                       class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                       placeholder="Например: 1">
            </div>
            <div>
                <label for="max_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    📅 Макс. срок (дни)
                </label>
                <input type="number" id="max_days" name="max_days" min="0" max="365"
                       value="{{ old('max_days') }}"
                       class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                       placeholder="Например: 3">
            </div>
        </div>

        {{-- ⚖️ Ограничение по весу --}}
        <div>
            <label for="weight_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ⚖️ Ограничение по весу (кг)
            </label>
            <input type="number" id="weight_limit" name="weight_limit" step="0.01" min="0" max="1000"
                   value="{{ old('weight_limit') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Оставьте пустым, если без ограничений">
        </div>

        {{-- 🗺️ Доступные регионы --}}
        <div>
            <label for="regions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🗺️ Доступные регионы
            </label>
            <select id="regions" name="regions[]" multiple
                    class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                    size="8">
                <option value="Все регионы РФ" {{ in_array('Все регионы РФ', old('regions', [])) ? 'selected' : '' }}>Все регионы РФ</option>
                @foreach(\Modules\Delivery\Models\DeliveryMethod::getRussianRegions() as $region)
                    @if($region !== 'Все регионы РФ')
                        <option value="{{ $region }}" {{ in_array($region, old('regions', [])) ? 'selected' : '' }}>{{ $region }}</option>
                    @endif
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Удерживайте Ctrl/Cmd для выбора нескольких регионов. Выберите "Все регионы РФ" для доставки по всей России</p>
        </div>

        {{-- 🇷🇺 Российская служба --}}
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_russian" id="is_russian" value="1"
                   {{ old('is_russian') ? 'checked' : '' }}
                   class="form-checkbox rounded text-blue-600 dark:bg-gray-700 dark:border-gray-600">
            <label for="is_russian" class="text-sm text-gray-700 dark:text-gray-300">
                🇷🇺 Это российская служба доставки
            </label>
        </div>

        {{-- 🌐 API интеграция --}}
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="api_enabled" id="api_enabled" value="1"
                       {{ old('api_enabled') ? 'checked' : '' }}
                       class="form-checkbox rounded text-blue-600 dark:bg-gray-700 dark:border-gray-600"
                       onchange="toggleApiSettings(this.checked)">
                <label for="api_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                    🌐 Включить API интеграцию
                </label>
            </div>
            
            <div id="api-settings" style="display: {{ old('api_enabled') ? 'block' : 'none' }};" class="mt-3 p-4 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Настройки API (JSON):</p>
                <textarea name="api_settings_json" id="api_settings_json" rows="6"
                          class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-700 dark:text-white font-mono text-xs"
                          placeholder='{"account": "your_account", "secure_password": "your_password"}'></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Для СДЭК: {"account": "...", "secure_password": "..."}<br>
                    Для Boxberry: {"token": "..."}<br>
                    Для Почты России: {"login": "...", "password": "..."}
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
                🎁 Бесплатная доставка при сумме заказа (₽)
            </label>
            <input type="number" id="free_delivery_threshold" name="free_delivery_threshold" step="0.01" min="0"
                   value="{{ old('free_delivery_threshold') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="Оставьте пустым, если бесплатная доставка не предусмотрена">
            <p class="text-xs text-gray-500 mt-1">Если сумма заказа больше или равна указанной, доставка будет бесплатной</p>
        </div>

        {{-- 🔢 Порядок сортировки --}}
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                🔢 Порядок сортировки
            </label>
            <input type="number" id="sort_order" name="sort_order" min="0"
                   value="{{ old('sort_order', 0) }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 shadow-sm focus:ring focus:ring-blue-300 dark:bg-gray-800 dark:text-white"
                   placeholder="0">
            <p class="text-xs text-gray-500 mt-1">Меньшее число = выше в списке</p>
        </div>

        {{-- ✅ Статус активности --}}
        <div class="flex items-center gap-2">
            <input type="checkbox" name="active" id="active" value="1"
                   {{ old('active', true) ? 'checked' : '' }}
                   class="form-checkbox rounded text-blue-600 dark:bg-gray-700 dark:border-gray-600">
            <label for="active" class="text-sm text-gray-700 dark:text-gray-300">
                ✅ Метод активен (будет доступен клиентам)
            </label>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        <div class="text-right">
            <button type="submit"
                    class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow-md transition-all duration-200 transform hover:scale-105">
                💾 Сохранить
            </button>
        </div>
    </form>
@endsection
