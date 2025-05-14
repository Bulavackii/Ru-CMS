@csrf

<x-admin.input label="Название" name="title" :value="old('title', $method->title ?? '')" required />

<div>
    <label class="block font-semibold mb-1">Описание</label>
    <textarea name="description" class="w-full border rounded px-4 py-2" rows="3">{{ old('description', $method->description ?? '') }}</textarea>
</div>

<x-admin.input label="Стоимость (₽)" name="price" type="number" step="0.01" :value="old('price', $method->price ?? '')" />

<label class="inline-flex items-center mt-2">
    <input type="checkbox" name="active" value="1" {{ old('active', $method->active ?? true) ? 'checked' : '' }}
        class="form-checkbox rounded text-blue-600 mr-2">
    Активен
</label>
