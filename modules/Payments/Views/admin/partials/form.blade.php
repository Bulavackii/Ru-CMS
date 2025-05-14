@php
    $method = $method ?? null;
@endphp

<div>
    <label class="block font-semibold mb-1">Название</label>
    <input type="text" name="title" value="{{ old('title', $method->title ?? '') }}"
           class="w-full border border-gray-300 rounded px-3 py-2" required>
</div>

<div>
    <label class="block font-semibold mb-1">Описание</label>
    <textarea name="description" rows="3"
              class="w-full border border-gray-300 rounded px-3 py-2">{{ old('description', $method->description ?? '') }}</textarea>
</div>

<div>
    <label class="block font-semibold mb-1">Тип</label>
    <select name="type" class="w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="offline" {{ old('type', $method->type ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
        <option value="online" {{ old('type', $method->type ?? '') === 'online' ? 'selected' : '' }}>Online</option>
    </select>
</div>

<div>
    <label class="inline-flex items-center">
        <input type="checkbox" name="active" value="1"
               class="mr-2" {{ old('active', $method->active ?? true) ? 'checked' : '' }}>
        Включить метод
    </label>
</div>
