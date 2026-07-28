@csrf

{{-- 🏷️ Название метода доставки --}}
<x-admin.input
    label="🏷️ Название"
    name="title"
    :value="old('title', $method->title ?? '')"
    required
/>

{{-- 📝 Описание (необязательное) --}}
<div class="mb-4">
    <label for="description" class="block font-semibold text-sm text-gray-700 dark:text-gray-300 mb-1">
        📝 {{ __('admin.delivery.desc') }}
    </label>
    <textarea id="description" name="description" rows="3"
              class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-4 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
              placeholder="{{ __('admin.delivery.name_ph2') }}">
        {{ old('description', $method->description ?? '') }}
    </textarea>
</div>

{{-- 💰 Стоимость доставки --}}
<x-admin.input
    label="💰 {{ __('admin.delivery.price') }}"
    name="price"
    type="number"
    step="0.01"
    :value="old('price', $method->price ?? '')"
    required
/>

{{-- ✅ Активность метода --}}
<div class="mt-4">
    <label class="inline-flex items-center">
        <input type="checkbox" name="active" value="1"
               class="form-checkbox rounded text-green-600 focus:ring-green-500"
               {{ old('active', $method->active ?? true) ? 'checked' : '' }}>
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">✅ {{ __('admin.delivery.active') }}</span>
    </label>
</div>
