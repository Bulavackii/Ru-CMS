@extends('layouts.admin')

@section('title', 'Редактирование слайдшоу')
@section('header', '🎞️ Слайды: ' . $slideshow->title)

@section('content')
    {{-- 📥 Форма добавления нового слайда --}}
    <form method="POST" action="{{ route('admin.slides.store') }}" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6 mb-8 max-w-2xl space-y-6">
        @csrf
        <input type="hidden" name="slideshow_id" value="{{ $slideshow->id }}">

        {{-- 🖼️ Файл слайда --}}
        <div>
            <label for="media" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">🖼️ Файл (изображение или видео)</label>
            <input type="file" name="media" id="media" required
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        {{-- 📝 Подпись --}}
        <div>
            <label for="caption" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Подпись (необязательно)</label>
            <input type="text" name="caption" id="caption"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        {{-- 🔗 Ссылка --}}
        <div>
            <label for="link" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">🔗 Ссылка при клике (необязательно)</label>
            <input type="url" name="link" id="link"
                   placeholder="https://example.com"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        {{-- 🔢 Порядок --}}
        <div>
            <label for="order" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">🔢 Порядок</label>
            <input type="number" name="order" id="order" value="0"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        {{-- 📍 Позиция --}}
        <div>
            <label for="position" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📍 Позиция слайдшоу</label>
            <select name="position" id="position"
                    class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
                <option value="top" {{ old('position', $slideshow->position ?? '') == 'top' ? 'selected' : '' }}>🔝 Вверху страницы</option>
                <option value="bottom" {{ old('position', $slideshow->position ?? '') == 'bottom' ? 'selected' : '' }}>🔻 Внизу страницы</option>
            </select>
        </div>

        {{-- ✅ Кнопка --}}
        <div class="text-right">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                <i class="fas fa-plus-circle"></i> Добавить слайд
            </button>
        </div>
    </form>

    {{-- 🖼️ Существующие слайды --}}
    @if ($slideshow->items->count())
        <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-white">📂 Текущие слайды</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($slideshow->items->sortBy('order') as $slide)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm relative bg-white dark:bg-gray-800 transition">
                    @if ($slide->media_type === 'image')
                        <img src="{{ asset('storage/' . $slide->file_path) }}" class="w-full h-48 object-cover" alt="Слайд">
                    @else
                        <video controls class="w-full h-48 object-cover">
                            <source src="{{ asset('storage/' . $slide->file_path) }}">
                        </video>
                    @endif

                    <div class="p-3 text-sm border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 space-y-1">
                        <div>
                            <strong>📝 Подпись:</strong>
                            {{ $slide->caption ?: '—' }}
                        </div>
                        <div>
                            <strong>🔗 Ссылка:</strong>
                            @if ($slide->link)
                                <a href="{{ $slide->link }}" class="text-blue-600 hover:underline" target="_blank">{{ $slide->link }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    {{-- 🗑️ Удаление --}}
                    <form method="POST" action="{{ route('admin.slides.destroy', $slide->id) }}"
                          onsubmit="return confirm('Удалить этот слайд?')"
                          class="absolute top-2 right-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-red-600 hover:text-red-800 text-lg"
                                title="Удалить слайд">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-gray-500 dark:text-gray-400">📭 Нет слайдов для отображения</div>
    @endif
@endsection
