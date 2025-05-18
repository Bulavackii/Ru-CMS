@php
    $method = $method ?? null;
@endphp

{{-- 🏷️ Название метода --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        🏷️ Название
    </label>
    <input type="text" name="title" value="{{ old('title', $method->title ?? '') }}"
           class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
           placeholder="Введите название" required>
</div>

{{-- 📝 Описание метода --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        📝 Описание
    </label>
    <textarea name="description" rows="3"
              class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
              placeholder="Дополнительная информация (необязательно)">{{ old('description', $method->description ?? '') }}</textarea>
</div>

{{-- ⚙️ Тип метода (online/offline) --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        ⚙️ Тип метода
    </label>
    <select name="type"
            class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
            required>
        <option value="offline" {{ old('type', $method->type ?? '') === 'offline' ? 'selected' : '' }}>🖐️ Offline</option>
        <option value="online" {{ old('type', $method->type ?? '') === 'online' ? 'selected' : '' }}>🌐 Online</option>
    </select>
</div>

{{-- ✅ Активность метода --}}
<div class="mb-4">
    <label class="inline-flex items-center font-medium">
        <input type="checkbox" name="active" value="1"
               class="mr-2 rounded border-gray-300 text-black shadow-sm"
               {{ old('active', $method->active ?? true) ? 'checked' : '' }}>
        ✅ Включить метод (отображать при оформлении заказа)
    </label>
</div>
