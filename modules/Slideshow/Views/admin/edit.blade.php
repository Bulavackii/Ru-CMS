@extends('layouts.admin')

@section('title', 'Редактирование слайдшоу')
@section('header', '🎞️ Слайды: ' . $slideshow->title)

@section('content')
    {{-- 📥 Форма добавления слайда --}}
    <form method="POST" action="{{ route('admin.slides.store') }}" enctype="multipart/form-data"
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6 mb-8 max-w-2xl space-y-6">
        @csrf
        <input type="hidden" name="slideshow_id" value="{{ $slideshow->id }}">

        <div>
            <label for="media" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">🖼️ Файл</label>
            <input type="file" name="media" id="media" required
                class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        <div>
            <label for="caption" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Подпись</label>
            <input type="text" name="caption" id="caption"
                class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        <div>
            <label for="link" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">🔗 Ссылка</label>
            <input type="url" name="link" id="link" placeholder="https://example.com"
                class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        <div>
            <label for="order" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">🔢 Порядок</label>
            <input type="number" name="order" id="order" value="0"
                class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
        </div>

        <div>
            <label for="position" class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">📍 Позиция</label>
            <select name="position" id="position"
                class="w-full border border-gray-300 dark:border-gray-700 rounded px-4 py-2 bg-white dark:bg-gray-800 text-sm shadow-sm">
                <option value="top" {{ old('position', $slideshow->position ?? '') == 'top' ? 'selected' : '' }}>🔝 Вверху</option>
                <option value="bottom" {{ old('position', $slideshow->position ?? '') == 'bottom' ? 'selected' : '' }}>🔻 Внизу</option>
            </select>
        </div>

        <div class="text-right">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2 rounded-md text-sm font-semibold shadow transition">
                <i class="fas fa-plus-circle"></i> Добавить слайд
            </button>
        </div>
    </form>

    {{-- 📂 Список слайдов --}}
    @if ($slideshow->items->count())
        <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-white">📂 Текущие слайды</h2>

        <ul id="sortable-slides" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($slideshow->items->sortBy('order') as $slide)
                <li data-id="{{ $slide->id }}" id="slide-{{ $slide->id }}"
                    class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-800 transition relative cursor-move">
                    @if ($slide->media_type === 'image')
                        <img src="{{ asset('storage/' . $slide->file_path) }}" class="w-full h-48 object-cover" alt="Слайд">
                    @else
                        <video controls class="w-full h-48 object-cover">
                            <source src="{{ asset('storage/' . $slide->file_path) }}">
                        </video>
                    @endif

                    <div class="p-3 text-sm border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 space-y-1">
                        <div><strong>📝 Подпись:</strong> <span class="caption">{{ $slide->caption ?: '—' }}</span></div>
                        <div><strong>🔗 Ссылка:</strong> <span class="link">
                            @if ($slide->link)
                                <a href="{{ $slide->link }}" class="text-blue-600 hover:underline" target="_blank">{{ $slide->link }}</a>
                            @else
                                —
                            @endif
                        </span></div>
                    </div>

                    {{-- ✏️ и 🗑️ Кнопки действий --}}
                    <div class="absolute top-2 right-2 flex space-x-2">
                        {{-- Редактировать --}}
                        <button type="button" class="text-blue-600 hover:text-blue-800 text-base"
                            title="Редактировать"
                            onclick="openEditModal({{ $slide->id }}, '{{ addslashes($slide->caption) }}', '{{ addslashes($slide->link) }}')">
                            <i class="fas fa-edit"></i>
                        </button>

                        {{-- Удалить --}}
                        <form method="POST" action="{{ route('admin.slides.destroy', $slide->id) }}"
                            onsubmit="return confirm('Удалить этот слайд?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-base" title="Удалить">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="text-right mt-6">
            <button id="save-order"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-semibold shadow transition">
                💾 Сохранить порядок
            </button>
        </div>
    @else
        <div class="text-gray-500 dark:text-gray-400">📭 Нет слайдов для отображения</div>
    @endif

    {{-- 🔧 Модальное окно редактирования --}}
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md space-y-4 shadow-xl">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white">✏️ Редактировать слайд</h2>
            <input type="hidden" id="editId">
            <input type="text" id="editCaption" class="w-full border rounded px-3 py-2" placeholder="Подпись">
            <input type="url" id="editLink" class="w-full border rounded px-3 py-2" placeholder="Ссылка (https://...)">
            <div class="flex justify-end gap-2">
                <button onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Отмена</button>
                <button onclick="submitEdit()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Сохранить</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    function openEditModal(id, caption, link) {
        document.getElementById('editId').value = id;
        document.getElementById('editCaption').value = caption;
        document.getElementById('editLink').value = link;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function submitEdit() {
        const id = document.getElementById('editId').value;
        const caption = document.getElementById('editCaption').value;
        const link = document.getElementById('editLink').value;

        fetch(`/admin/slideshow/slides/${id}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ caption, link })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const slideEl = document.getElementById(`slide-${id}`);
                slideEl.querySelector('.caption').innerText = caption || '—';
                const linkEl = slideEl.querySelector('.link');
                if (link) {
                    linkEl.innerHTML = `<a href="${link}" class="text-blue-600 hover:underline" target="_blank">${link}</a>`;
                } else {
                    linkEl.innerHTML = '—';
                }
                closeEditModal();
            } else {
                alert('Ошибка при сохранении');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const el = document.getElementById('sortable-slides');
        const saveBtn = document.getElementById('save-order');

        if (!el || !saveBtn) return;

        new Sortable(el, {
            animation: 150,
            handle: '.cursor-move',
        });

        saveBtn.addEventListener('click', function() {
            const ids = Array.from(el.children).map(item => item.dataset.id);

            fetch("{{ route('admin.slides.sort') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        order: ids
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Порядок слайдов сохранён!');
                    } else {
                        alert('⚠️ Ошибка при сохранении');
                    }
                })
                .catch(() => alert('❌ Сетевой сбой при сохранении'));
        });
    });
</script>
@endpush
