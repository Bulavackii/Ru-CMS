@php
    $uid = uniqid('slide_');
@endphp

<div class="border p-4 rounded bg-white relative">
    <button type="button"
            onclick="this.closest('.border').remove()"
            class="absolute top-1 right-1 text-red-500 hover:text-red-700 text-sm">
        ✖
    </button>

    <input type="hidden" name="slides[][id]" value="{{ $slide->id ?? '' }}">

    {{-- Тип слайда --}}
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1">Тип</label>
        <select name="slides[][type]" class="w-full border rounded px-3 py-2">
            <option value="image" {{ ($slide->type ?? '') === 'image' ? 'selected' : '' }}>Изображение</option>
            <option value="video" {{ ($slide->type ?? '') === 'video' ? 'selected' : '' }}>Видео</option>
        </select>
    </div>

    {{-- Заголовок --}}
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1">Заголовок</label>
        <input type="text" name="slides[][title]" class="w-full border rounded px-3 py-2"
               value="{{ $slide->title ?? '' }}">
    </div>

    {{-- Контент --}}
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1">Контент</label>
        <textarea name="slides[][content]" rows="3" class="w-full border rounded px-3 py-2">{{ $slide->content ?? '' }}</textarea>
    </div>

    {{-- URL файла --}}
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1">Ссылка на файл</label>
        <input type="text" name="slides[][url]" class="w-full border rounded px-3 py-2"
               value="{{ $slide->url ?? '' }}">
    </div>
</div>
