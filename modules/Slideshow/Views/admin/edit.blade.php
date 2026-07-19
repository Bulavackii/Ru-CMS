@extends('layouts.admin')

@section('title', 'Редактирование слайдшоу')
@section('header', '🎞️ Слайды: ' . $slideshow->title)

@section('content')
  {{-- ░░░ НАСТРОЙКИ СЛАЙДШОУ ░░░ --}}
  <div class="mb-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-visible">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
      <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">⚙️ Настройки слайдшоу</span>
      <div class="flex gap-2">
        <a href="{{ route('admin.slideshow.preview', $slideshow->id) }}" target="_blank"
           class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm">
          <i class="fa-regular fa-eye"></i> Предпросмотр
        </a>
        <a href="{{ route('admin.slideshow.index') }}"
           class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
          <i class="fa-regular fa-arrow-left"></i> К списку
        </a>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.slideshow.update', $slideshow->id) }}" class="p-5">
      @csrf
      @method('PUT')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🏷️ Название</label>
          <input type="text" name="title" value="{{ old('title', $slideshow->title) }}"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" required>
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📍 Позиция</label>
          <select name="position" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
            <option value="top" {{ $slideshow->position === 'top' ? 'selected' : '' }}>Вверху</option>
            <option value="bottom" {{ $slideshow->position === 'bottom' ? 'selected' : '' }}>Внизу</option>
          </select>
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">⏱️ Задержка автоплея (мс)</label>
          <input type="number" name="autoplay_delay" value="{{ old('autoplay_delay', $slideshow->autoplay_delay ?? 5000) }}" min="1000" max="30000" step="500"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🎬 Эффект перехода</label>
          <select name="transition_effect" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
            <option value="slide" {{ ($slideshow->transition_effect ?? 'slide') === 'slide' ? 'selected' : '' }}>Слайд</option>
            <option value="fade" {{ ($slideshow->transition_effect ?? '') === 'fade' ? 'selected' : '' }}>Плавное затухание</option>
            <option value="cube" {{ ($slideshow->transition_effect ?? '') === 'cube' ? 'selected' : '' }}>Куб</option>
            <option value="coverflow" {{ ($slideshow->transition_effect ?? '') === 'coverflow' ? 'selected' : '' }}>Обложка</option>
            <option value="flip" {{ ($slideshow->transition_effect ?? '') === 'flip' ? 'selected' : '' }}>Переворот</option>
          </select>
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📏 Высота (CSS, например: 500px или 50vh)</label>
          <input type="text" name="height" value="{{ old('height', $slideshow->height) }}"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2"
                 placeholder="500px или 50vh">
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📝 Описание</label>
          <textarea name="description" rows="2"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">{{ old('description', $slideshow->description) }}</textarea>
        </div>
        <div class="flex items-center gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="published" value="1" {{ $slideshow->published ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700 dark:text-gray-300">✅ Опубликовано</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="show_pagination" value="1" {{ ($slideshow->show_pagination ?? true) ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700 dark:text-gray-300">🔘 Показывать пагинацию</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="show_navigation" value="1" {{ ($slideshow->show_navigation ?? true) ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700 dark:text-gray-300">⬅️➡️ Показывать навигацию</span>
          </label>
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <button type="submit" class="px-4 py-2 rounded-md bg-black text-white hover:bg-gray-800">
          <i class="fa-solid fa-save"></i> Сохранить настройки
        </button>
      </div>
    </form>
  </div>
  {{-- ░░░ ФОРМА ДОБАВЛЕНИЯ СЛАЙДА ░░░ --}}
  <form method="POST" action="{{ route('admin.slides.store') }}" enctype="multipart/form-data"
        class="w-full mb-10">
    @csrf
    <input type="hidden" name="slideshow_id" value="{{ $slideshow->id }}">

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-visible w-full">
      {{-- Заголовок секции --}}
      <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">Добавить слайд</span>
        <span class="text-xs px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">изображение или видео</span>
      </div>

      <div class="p-5 grid gap-5">
        {{-- Файл (Drop-zone + Обзор) --}}
        <div>
          <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">🖼️ Файл</label>

          <div id="dropbox"
               class="group relative flex flex-col sm:flex-row items-stretch gap-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/60 p-4 hover:border-blue-400 transition">

            {{-- Превью --}}
            <div id="previewBox"
                 class="w-full sm:w-44 h-40 rounded-lg overflow-hidden bg-white dark:bg-gray-900 flex items-center justify-center">
              {{-- иконка-заглушка --}}
              <svg id="previewIcon" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300"
                   viewBox="0 0 24 24" fill="currentColor"><path d="M4 5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm8-1.5V8h4.5L12 3.5Z"/></svg>
            </div>

            {{-- Правая колонка: инструкции + кнопки --}}
            <div class="flex-1 flex flex-col gap-3">
              <div class="text-sm text-gray-600 dark:text-gray-300">
                Перетащите файл сюда <span class="text-gray-400">или</span> нажмите <span class="font-medium">Обзор…</span>
                <div class="text-xs text-gray-400 mt-1">
                  Рекомендуемый размер изображений — не меньше 1280×720.
                </div>
              </div>

              <div class="flex items-center gap-3">
                <input id="media" name="media" type="file" class="hidden" accept="image/*,video/*" required>

                <button type="button" id="browseBtn"
                        class="px-3 h-10 rounded-md bg-black text-white hover:bg-gray-800 transition">
                  Обзор…
                </button>

                <span id="fileName"
                      class="text-xs text-gray-500 truncate">Файл не выбран</span>
              </div>
            </div>
          </div>
        </div>

        {{-- Подпись --}}
        <div>
          <label for="caption" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📝 Подпись</label>
          <input id="caption" name="caption" type="text"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2"
                 placeholder="Короткий текст на слайде">
        </div>

        {{-- Alt-текст для SEO --}}
        <div>
          <label for="alt_text" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🔍 Alt-текст (SEO)</label>
          <input id="alt_text" name="alt_text" type="text"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2"
                 placeholder="Описание изображения для поисковых систем">
          <p class="text-xs text-gray-500 mt-1">Рекомендуется заполнять для лучшей индексации</p>
        </div>

        {{-- Ссылка --}}
        <div>
          <label for="link" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🔗 Ссылка</label>
          <input id="link" name="link" type="url" placeholder="https://example.com"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
        </div>

        {{-- Позиция текста --}}
        <div>
          <label for="text_position" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📍 Позиция текста</label>
          <select id="text_position" name="text_position"
                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
            <option value="top-left">Вверху слева</option>
            <option value="top-center">Вверху по центру</option>
            <option value="top-right">Вверху справа</option>
            <option value="center">По центру</option>
            <option value="bottom-left">Внизу слева</option>
            <option value="bottom-center">Внизу по центру</option>
            <option value="bottom-right" selected>Внизу справа</option>
          </select>
        </div>

        {{-- Цвета текста --}}
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="text_color" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 Цвет текста</label>
            <input id="text_color" name="text_color" type="color" value="#ffffff"
                   class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
          <div>
            <label for="background_color" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 Цвет фона</label>
            <input id="background_color" name="background_color" type="color" value="#2563eb"
                   class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
        </div>

        {{-- Порядок + Позиция --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="order" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🔢 Порядок</label>
            <input id="order" name="order" type="number" value="0"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
          </div>
          <div>
            <label for="position" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📍 Позиция</label>
            <select id="position" name="position"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
              <option value="top" {{ old('position', $slideshow->position ?? '') == 'top' ? 'selected' : '' }}>🔝 Вверху</option>
              <option value="bottom" {{ old('position', $slideshow->position ?? '') == 'bottom' ? 'selected' : '' }}>🔻 Внизу</option>
            </select>
          </div>
        </div>

        {{-- Кнопка добавления --}}
        <div class="flex justify-end">
          <button type="submit"
                  class="inline-flex items-center gap-2 rounded-md bg-black text-white px-4 py-2 hover:bg-gray-800 shadow">
            <i class="fa-solid fa-plus"></i> Добавить слайд
          </button>
        </div>
      </div>
    </div>
  </form>

  {{-- ░░░ ТЕКУЩИЕ СЛАЙДЫ ░░░ --}}
  @if ($slideshow->items->count())
    <h2 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">📂 Текущие слайды</h2>

    <ul id="sortable-slides" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      @foreach ($slideshow->items->sortBy('order') as $slide)
        <li data-id="{{ $slide->id }}" id="slide-{{ $slide->id }}"
            class="relative border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition cursor-move">

          @if ($slide->media_type === 'image')
            <img src="{{ asset('storage/' . $slide->file_path) }}" class="w-full h-48 object-cover" alt="Слайд">
          @else
            <video class="w-full h-48 object-cover" controls>
              <source src="{{ asset('storage/' . $slide->file_path) }}">
            </video>
          @endif

          <div class="p-3 text-sm border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 space-y-1">
            <div><strong>📝 Подпись:</strong> <span class="caption">{{ $slide->caption ?: '—' }}</span></div>
            <div><strong>🔗 Ссылка:</strong> <span class="link">
              @if ($slide->link)
                <a href="{{ $slide->link }}" class="text-blue-600 hover:underline" target="_blank">{{ $slide->link }}</a>
              @else — @endif
            </span></div>
          </div>

          {{-- Действия --}}
          <div class="absolute top-2 right-2 flex gap-2">
            <button type="button"
                    class="grid place-items-center w-9 h-9 rounded-lg bg-white/90 dark:bg-gray-900/90 text-blue-600 hover:text-blue-800 shadow"
                    title="Редактировать"
                    onclick="openEditModal({{ $slide->id }}, @json($slide->caption ?? ''), @json($slide->alt_text ?? ''), @json($slide->link ?? ''), @json($slide->text_position ?? 'bottom-right'), @json($slide->text_color ?? '#ffffff'), @json($slide->background_color ?? '#2563eb'))">
              <i class="fas fa-edit"></i>
            </button>

            <form method="POST" action="{{ route('admin.slides.destroy', $slide->id) }}"
                  onsubmit="return confirm('Удалить этот слайд?')">
              @csrf
              @method('DELETE')
              <button type="submit"
                      class="grid place-items-center w-9 h-9 rounded-lg bg-white/90 dark:bg-gray-900/90 text-red-600 hover:text-red-700 shadow"
                      title="Удалить">
                <i class="fas fa-trash-alt"></i>
              </button>
            </form>
          </div>
        </li>
      @endforeach
    </ul>

    {{-- Кнопка сохранения порядка --}}
    <div class="mt-6 flex justify-end">
      <button id="save-order"
              class="inline-flex items-center gap-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 shadow">
        <i class="fa-solid fa-floppy-disk"></i> Сохранить порядок
      </button>
    </div>
  @else
    <div class="text-gray-500 dark:text-gray-400">📭 Нет слайдов для отображения</div>
  @endif

  {{-- ░░░ МОДАЛ РЕДАКТИРОВАНИЯ ░░░ --}}
  <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl max-h-[90vh] overflow-y-auto">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">✏️ Редактировать слайд</h3>
      <input type="hidden" id="editId">
      <div class="grid gap-3">
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">📝 Подпись</label>
          <input id="editCaption" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" placeholder="Подпись">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🔍 Alt-текст (SEO)</label>
          <input id="editAltText" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" placeholder="Alt-текст">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🔗 Ссылка</label>
          <input id="editLink" type="url" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" placeholder="https://...">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">📍 Позиция текста</label>
          <select id="editTextPosition" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
            <option value="top-left">Вверху слева</option>
            <option value="top-center">Вверху по центру</option>
            <option value="top-right">Вверху справа</option>
            <option value="center">По центру</option>
            <option value="bottom-left">Внизу слева</option>
            <option value="bottom-center">Внизу по центру</option>
            <option value="bottom-right">Внизу справа</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 Цвет текста</label>
            <input id="editTextColor" type="color" class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 Цвет фона</label>
            <input id="editBackgroundColor" type="color" class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
        </div>
      </div>
      <div class="mt-5 flex justify-end gap-2">
        <button onclick="closeEditModal()" class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">Отмена</button>
        <button onclick="submitEdit()" class="px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white">Сохранить</button>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ local_js('sortable.min.js') }}"></script>
  <script>
    /* ====== МОДАЛ РЕДАКТИРОВАНИЯ ====== */
    function openEditModal(id, caption, altText, link, textPosition, textColor, backgroundColor) {
      document.getElementById('editId').value = id;
      document.getElementById('editCaption').value = caption || '';
      document.getElementById('editAltText').value = altText || '';
      document.getElementById('editLink').value = link || '';
      document.getElementById('editTextPosition').value = textPosition || 'bottom-right';
      document.getElementById('editTextColor').value = textColor || '#ffffff';
      document.getElementById('editBackgroundColor').value = backgroundColor || '#2563eb';
      document.getElementById('editModal').classList.remove('hidden');
      document.getElementById('editModal').classList.add('flex');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
      document.getElementById('editModal').classList.remove('flex');
    }
    function submitEdit() {
      const id = document.getElementById('editId').value;
      const caption = document.getElementById('editCaption').value;
      const altText = document.getElementById('editAltText').value;
      const link = document.getElementById('editLink').value;
      const textPosition = document.getElementById('editTextPosition').value;
      const textColor = document.getElementById('editTextColor').value;
      const backgroundColor = document.getElementById('editBackgroundColor').value;

      fetch(`/admin/slideshow/slides/${id}/update`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ caption, alt_text: altText, link, text_position: textPosition, text_color: textColor, background_color: backgroundColor })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          location.reload(); // Перезагружаем страницу для обновления всех данных
        } else {
          alert('Ошибка при сохранении');
        }
      }).catch(()=>alert('Сетевой сбой'));
    }

    /* ====== СОРТИРОВКА ====== */
    document.addEventListener('DOMContentLoaded', function () {
      const list = document.getElementById('sortable-slides');
      const saveBtn = document.getElementById('save-order');
      if (list) {
        new Sortable(list, { animation: 150, handle: '.cursor-move' });
      }
      saveBtn?.addEventListener('click', () => {
        const ids = Array.from(list.children).map(el => el.dataset.id);
        fetch("{{ route('admin.slides.sort') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ order: ids })
        })
        .then(r => r.json())
        .then(d => alert(d.success ? '✅ Порядок слайдов сохранён!' : '⚠️ Ошибка при сохранении'))
        .catch(()=>alert('❌ Сетевой сбой при сохранении'));
      });
    });

    /* ====== DROP-ZONE + ОБЗОР (фикс «двойного клика») ====== */
    (function () {
      const drop = document.getElementById('dropbox');
      const input = document.getElementById('media');
      const browseBtn = document.getElementById('browseBtn');
      const fileName = document.getElementById('fileName');
      const previewBox = document.getElementById('previewBox');
      const previewIcon = document.getElementById('previewIcon');

      let opening = false; // защита от двойного программного клика

      function openDialogSafe() {
        if (opening) return;
        opening = true;
        input.click();
        setTimeout(() => opening = false, 400);
      }

      // Обзор — останавливаем всплытие и открываем диалог безопасно
      browseBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openDialogSafe();
      });

      // Клик по зоне — но не по самой кнопке/инпуту
      drop.addEventListener('click', (e) => {
        if (e.target === input || e.target.closest('#browseBtn')) return;
        openDialogSafe();
      });

      function showPreview(file) {
        fileName.textContent = file.name;
        previewBox.innerHTML = '';
        previewIcon.classList.add('hidden');

        if (file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.className = 'w-full h-full object-cover';
          img.src = URL.createObjectURL(file);
          previewBox.appendChild(img);
        } else if (file.type.startsWith('video/')) {
          const v = document.createElement('video');
          v.className = 'w-full h-full object-cover';
          v.muted = true; v.autoplay = true; v.loop = true;
          v.src = URL.createObjectURL(file);
          previewBox.appendChild(v);
        } else {
          previewIcon.classList.remove('hidden');
          previewBox.appendChild(previewIcon);
        }
      }

      input.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (file) showPreview(file);
      });

      // Drag & Drop
      ['dragenter','dragover'].forEach(ev =>
        drop.addEventListener(ev, e => {
          e.preventDefault();
          drop.classList.add('ring-2','ring-blue-400');
        })
      );
      ['dragleave','drop'].forEach(ev =>
        drop.addEventListener(ev, e => {
          e.preventDefault();
          drop.classList.remove('ring-2','ring-blue-400');
        })
      );
      drop.addEventListener('drop', e => {
        const f = e.dataTransfer?.files?.[0];
        if (!f) return;
        input.files = e.dataTransfer.files; // чтобы форма отправила файл
        showPreview(f);
      });
    })();
  </script>
@endpush
