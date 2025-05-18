@php
    $uid = uniqid('slide_');
@endphp

{{-- 🔳 Блок слайда --}}
<div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl bg-white dark:bg-gray-900 relative shadow-sm group transition">

    {{-- ❌ Кнопка удаления --}}
    <button type="button"
            onclick="this.closest('.border').remove()"
            class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-base transition"
            title="Удалить слайд">
        <i class="fas fa-times-circle"></i>
    </button>

    {{-- 🔒 ID слайда --}}
    <input type="hidden" name="slides[][id]" value="{{ $slide->id ?? '' }}">

    {{-- 🧩 Тип слайда --}}
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🧩 Тип</label>
        <select name="slides[][type]" class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-gray-100 shadow-sm">
            <option value="image" {{ ($slide->type ?? '') === 'image' ? 'selected' : '' }}>🖼️ Изображение</option>
            <option value="video" {{ ($slide->type ?? '') === 'video' ? 'selected' : '' }}>🎬 Видео</option>
        </select>
    </div>

    {{-- 📝 Заголовок --}}
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Заголовок</label>
        <input type="text" name="slides[][title]" value="{{ $slide->title ?? '' }}"
               class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm text-gray-800 dark:text-gray-100">
    </div>

    {{-- 📄 Контент --}}
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">📄 Контент</label>
        <textarea name="slides[][content]" rows="3"
                  class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm text-gray-800 dark:text-gray-100">{{ $slide->content ?? '' }}</textarea>
    </div>

    {{-- 🔗 URL файла --}}
    <div>
        <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🔗 Ссылка на файл</label>
        <input type="text" name="slides[][url]" value="{{ $slide->url ?? '' }}"
               class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm text-gray-800 dark:text-gray-100">
    </div>
</div>
