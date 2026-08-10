@extends('layouts.admin')

@section('title', __('admin.slideshow.edit_title'))


@section('content')
  {{-- ── Шапка страницы ── --}}
  <div class="admin-accent-bar mb-0"></div>
  <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
              flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
      <span class="admin-icon-badge"><i class="fas fa-images"></i></span>
      <div class="min-w-0 space-y-1">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $slideshow->title }}</h1>
        <div class="flex flex-wrap items-center gap-2 text-xs">
          @if ($slideshow->published)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
              <i class="fa-regular fa-eye"></i> Опубликовано
            </span>
          @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
              <i class="fa-regular fa-eye-slash"></i> Скрыто
            </span>
          @endif
          <span class="inline-flex items-center gap-1 px-2 py-0.5 font-semibold
                       {{ $slideshow->position === 'top'
                          ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
                          : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' }}">
            <i class="fas {{ $slideshow->position === 'top' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
            {{ $slideshow->position === 'top' ? 'Верх' : 'Низ' }}
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            ID {{ $slideshow->id }}
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
            <i class="fa-regular fa-images"></i> слайдов: {{ $slideshow->items->count() }}
          </span>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3 shrink-0">
      <a href="{{ route('admin.slideshow.preview', $slideshow->id) }}" target="_blank"
         class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
        <i class="fa-regular fa-eye"></i> Предпросмотр
      </a>
      <a href="{{ route('admin.slideshow.index') }}"
         class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
        <i class="fa-solid fa-arrow-left"></i> К списку
      </a>
    </div>
  </div>

  {{-- ░░░ НАСТРОЙКИ СЛАЙДШОУ ░░░
       Подписи были эмодзи (🏷️ 📍 ⏱️ 🎬 📏 📝): в остальной панели значки
       рисует Font Awesome, и раздел выбивался из ряда. Галочки заменены на
       общий тумблер `.admin-toggle` — тот же, что в «Меню». Имена полей и
       адрес формы не менялись. --}}
  <div class="admin-card mb-6">
    <div class="sl-cardhead">
      <i class="fas fa-sliders"></i> {{ __('admin.slideshow.settings') }}
    </div>

    <form method="POST" action="{{ route('admin.slideshow.update', $slideshow->id) }}" class="p-5">
      @csrf
      @method('PUT')

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="sl-field">
          <label class="sl-label" for="s-title"><i class="fas fa-tag"></i> {{ __('admin.slideshow.name_short') }}</label>
          <input id="s-title" type="text" name="title" value="{{ old('title', $slideshow->title) }}" class="sl-input" required>
          <span class="sl-hint">{{ __('admin.slideshow.name_hint') }}</span>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="s-position"><i class="fas fa-location-dot"></i> {{ __('admin.slideshow.position_short') }}</label>
          <select id="s-position" name="position" class="sl-input">
            <option value="top" {{ $slideshow->position === 'top' ? 'selected' : '' }}>{{ __('admin.slideshow.top') }}</option>
            <option value="bottom" {{ $slideshow->position === 'bottom' ? 'selected' : '' }}>{{ __('admin.slideshow.bottom') }}</option>
          </select>
          <span class="sl-hint">
            {{ __('admin.slideshow.will_appear') }}
            {{ $slideshow->position === 'top' ? __('admin.slideshow.above_content') : __('admin.slideshow.below_blocks') }}
          </span>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="s-autoplay"><i class="fas fa-stopwatch"></i> {{ __('admin.slideshow.autoplay') }}</label>
          <input id="s-autoplay" type="number" name="autoplay_delay"
                 value="{{ old('autoplay_delay', $slideshow->autoplay_delay ?? 5000) }}"
                 min="1000" max="30000" step="500" class="sl-input">
          <span class="sl-hint">{{ __('admin.slideshow.autoplay_hint') }}</span>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="s-effect"><i class="fas fa-wand-magic-sparkles"></i> {{ __('admin.slideshow.effect') }}</label>
          <select id="s-effect" name="transition_effect" class="sl-input">
            <option value="slide" {{ ($slideshow->transition_effect ?? 'slide') === 'slide' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_slide') }}</option>
            <option value="fade" {{ ($slideshow->transition_effect ?? '') === 'fade' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_fade') }}</option>
            <option value="cube" {{ ($slideshow->transition_effect ?? '') === 'cube' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_cube') }}</option>
            <option value="coverflow" {{ ($slideshow->transition_effect ?? '') === 'coverflow' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_cover') }}</option>
            <option value="flip" {{ ($slideshow->transition_effect ?? '') === 'flip' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_flip') }}</option>
          </select>
        </div>

        <div class="sl-field">
          {{-- Пример вынесен в подсказку: в подписи он делал её вдвое длиннее
               соседних и ломал ровный ряд полей. --}}
          <label class="sl-label" for="s-height"><i class="fas fa-ruler-vertical"></i> {{ __('admin.slideshow.height_short') }}</label>
          <input id="s-height" type="text" name="height" value="{{ old('height', $slideshow->height) }}"
                 class="sl-input" placeholder="{{ __('admin.slideshow.height_ph') }}">
          <span class="sl-hint">{{ __('admin.slideshow.height_hint') }}</span>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="s-desc"><i class="fas fa-align-left"></i> {{ __('admin.slideshow.description') }}</label>
          <textarea id="s-desc" name="description" rows="2" class="sl-input">{{ old('description', $slideshow->description) }}</textarea>
          <span class="sl-hint">{{ __('admin.slideshow.description_hint') }}</span>
        </div>
      </div>

      {{-- Переключатели отдельной полосой, а не ячейкой сетки: раньше они
           стояли в одной колонке с полями и ряд разъезжался. --}}
      <div class="sl-switches">
        <label class="sl-switch">
          <span class="admin-toggle">
            <input type="checkbox" name="published" value="1" {{ $slideshow->published ? 'checked' : '' }}>
            <span class="track"></span><span class="knob"></span>
          </span>
          <span>{{ __('admin.slideshow.published') }}</span>
        </label>

        <label class="sl-switch">
          <span class="admin-toggle">
            <input type="checkbox" name="show_pagination" value="1" {{ ($slideshow->show_pagination ?? true) ? 'checked' : '' }}>
            <span class="track"></span><span class="knob"></span>
          </span>
          <span>{{ __('admin.slideshow.show_pagination') }}</span>
        </label>

        <label class="sl-switch">
          <span class="admin-toggle">
            <input type="checkbox" name="show_navigation" value="1" {{ ($slideshow->show_navigation ?? true) ? 'checked' : '' }}>
            <span class="track"></span><span class="knob"></span>
          </span>
          <span>{{ __('admin.slideshow.show_nav') }}</span>
        </label>

        <button type="submit" class="sl-btn sl-btn--primary sl-switches__save">
          <i class="fas fa-floppy-disk"></i> {{ __('admin.slideshow.save_settings') }}
        </button>
      </div>
    </form>
  </div>

  {{-- ░░░ СЛАЙДЫ ░░░
       Раньше страница открывалась восемью полями формы добавления, а сами
       слайды лежали под ними — до них приходилось прокручивать. Теперь
       сверху список, а форма разворачивается по кнопке (и раскрыта сразу,
       если слайдов ещё нет). --}}
  <div class="admin-card mb-6" x-data="{ adding: {{ $slideshow->items->count() ? 'false' : 'true' }} }">
    <div class="sl-cardhead sl-cardhead--row">
      <span><i class="fa-regular fa-images"></i> {{ __('admin.slideshow.current_slides') }}</span>

      <span class="sl-cardhead__right">
        <span class="sl-count">{{ $slideshow->items->count() }}</span>
        <button type="button" class="sl-btn sl-btn--primary" @click="adding = !adding">
          <i class="fas" :class="adding ? 'fa-xmark' : 'fa-plus'"></i>
          <span x-text="adding ? @js(__('admin.cancel')) : @js(__('admin.slideshow.add_slide'))">{{ __('admin.slideshow.add_slide') }}</span>
        </button>
      </span>
    </div>

    {{-- ── Форма добавления ── --}}
    <form method="POST" action="{{ route('admin.slides.store') }}" enctype="multipart/form-data"
          class="sl-addform" x-show="adding" x-cloak>
      @csrf
      <input type="hidden" name="slideshow_id" value="{{ $slideshow->id }}">

      <div class="sl-dropzone" id="dropbox">
        <div id="previewBox" class="sl-dropzone__preview">
          <svg id="previewIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M4 5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm8-1.5V8h4.5L12 3.5Z"/>
          </svg>
        </div>

        <div class="sl-dropzone__body">
          <p class="sl-dropzone__text">
            {{ __('admin.slideshow.drop_here') }}
            <span class="sl-dropzone__or">{{ __('admin.slideshow.or') }}</span>
            {{ __('admin.slideshow.click') }} <b>{{ __('admin.slideshow.browse') }}</b>
          </p>
          <p class="sl-hint">{{ __('admin.slideshow.size_hint') }}</p>

          <div class="sl-dropzone__actions">
            <input id="media" name="media" type="file" class="hidden" accept="image/*,video/*" required>
            <button type="button" id="browseBtn" class="sl-btn sl-btn--primary">{{ __('admin.slideshow.browse') }}</button>
            <span id="fileName" class="sl-hint">{{ __('admin.slideshow.no_file') }}</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="sl-field">
          <label class="sl-label" for="caption"><i class="fas fa-align-left"></i> {{ __('admin.slideshow.caption') }}</label>
          <input id="caption" name="caption" type="text" class="sl-input" placeholder="{{ __('admin.slideshow.caption_ph') }}">
        </div>

        <div class="sl-field">
          <label class="sl-label" for="alt_text"><i class="fas fa-magnifying-glass"></i> {{ __('admin.slideshow.alt') }}</label>
          <input id="alt_text" name="alt_text" type="text" class="sl-input" placeholder="{{ __('admin.slideshow.alt_ph') }}">
          <span class="sl-hint">{{ __('admin.slideshow.alt_hint') }}</span>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="link"><i class="fas fa-link"></i> {{ __('admin.slideshow.link') }}</label>
          <input id="link" name="link" type="url" class="sl-input" placeholder="https://example.com">
        </div>

        <div class="sl-field">
          <label class="sl-label" for="text_position"><i class="fas fa-location-dot"></i> {{ __('admin.slideshow.text_pos') }}</label>
          <select id="text_position" name="text_position" class="sl-input">
            <option value="top-left">{{ __('admin.slideshow.tp_tl') }}</option>
            <option value="top-center">{{ __('admin.slideshow.tp_tc') }}</option>
            <option value="top-right">{{ __('admin.slideshow.tp_tr') }}</option>
            <option value="center">{{ __('admin.slideshow.tp_c') }}</option>
            <option value="bottom-left">{{ __('admin.slideshow.tp_bl') }}</option>
            <option value="bottom-center">{{ __('admin.slideshow.tp_bc') }}</option>
            <option value="bottom-right" selected>{{ __('admin.slideshow.tp_br') }}</option>
          </select>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="text_color"><i class="fas fa-font"></i> {{ __('admin.slideshow.text_color') }}</label>
          <input id="text_color" name="text_color" type="color" value="#ffffff" class="sl-color">
        </div>

        <div class="sl-field">
          <label class="sl-label" for="background_color"><i class="fas fa-fill-drip"></i> {{ __('admin.slideshow.bg_color') }}</label>
          <input id="background_color" name="background_color" type="color" value="#2563eb" class="sl-color">
        </div>

        <div class="sl-field">
          <label class="sl-label" for="order"><i class="fas fa-list-ol"></i> {{ __('admin.slideshow.order') }}</label>
          <input id="order" name="order" type="number" value="{{ $slideshow->items->count() }}" class="sl-input">
          <span class="sl-hint">{{ __('admin.slideshow.order_hint') }}</span>
        </div>

        <div class="sl-field">
          <label class="sl-label" for="position"><i class="fas fa-arrows-up-down"></i> {{ __('admin.slideshow.position_short') }}</label>
          <select id="position" name="position" class="sl-input">
            <option value="top" {{ old('position', $slideshow->position ?? '') == 'top' ? 'selected' : '' }}>{{ __('admin.slideshow.top') }}</option>
            <option value="bottom" {{ old('position', $slideshow->position ?? '') == 'bottom' ? 'selected' : '' }}>{{ __('admin.slideshow.bottom') }}</option>
          </select>
        </div>
      </div>

      <div class="sl-addform__foot">
        <button type="submit" class="sl-btn sl-btn--primary">
          <i class="fas fa-plus"></i> {{ __('admin.slideshow.add_slide') }}
        </button>
      </div>
    </form>

    {{-- ── Список слайдов ── --}}
    @if ($slideshow->items->count())
      <div class="p-5">
        <p class="sl-hint sl-dragnote"><i class="fas fa-up-down-left-right"></i> {{ __('admin.slideshow.drag_hint') }}</p>

        <ul id="sortable-slides" class="sl-grid">
          @foreach ($slideshow->items->sortBy('order') as $slide)
            <li data-id="{{ $slide->id }}" id="slide-{{ $slide->id }}" class="sl-slide cursor-move">
              <div class="sl-slide__media">
                @if ($slide->media_type === 'image')
                  <img src="{{ asset('storage/' . $slide->file_path) }}" alt="{{ $slide->alt_text }}">
                @else
                  <video controls><source src="{{ asset('storage/' . $slide->file_path) }}"></video>
                @endif

                <span class="sl-slide__kind">
                  <i class="fas {{ $slide->media_type === 'image' ? 'fa-image' : 'fa-film' }}"></i>
                </span>

                {{-- Действия.
                     ⚠️ Кнопка «Редактировать» НЕ РАБОТАЛА: аргументы подставлялись
                     через @json, а тот выводит строку в ДВОЙНЫХ кавычках — первая
                     же кавычка закрывала атрибут onclick="…". Правильная директива
                     для JS внутри HTML-атрибута — @js (Illuminate\Support\Js). --}}
                <span class="sl-slide__actions">
                  <button type="button" class="sl-icon" title="{{ __('admin.edit') }}"
                          onclick="openEditModal({{ $slide->id }}, @js($slide->caption ?? ''), @js($slide->alt_text ?? ''), @js($slide->link ?? ''), @js($slide->text_position ?? 'bottom-right'), @js($slide->text_color ?? '#ffffff'), @js($slide->background_color ?? '#2563eb'))">
                    <i class="fas fa-pen"></i>
                  </button>

                  <form method="POST" action="{{ route('admin.slides.destroy', $slide->id) }}"
                        onsubmit="return confirm(@js(__('admin.slideshow.slide_delete_confirm')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="sl-icon sl-icon--danger" title="{{ __('admin.delete') }}">
                      <i class="fas fa-trash-can"></i>
                    </button>
                  </form>
                </span>
              </div>

              <div class="sl-slide__body">
                <span class="sl-slide__caption">{{ $slide->caption ?: '—' }}</span>
                @if ($slide->link)
                  <a class="sl-slide__link" href="{{ $slide->link }}" target="_blank" rel="noopener">
                    <i class="fas fa-link"></i> {{ $slide->link }}
                  </a>
                @endif
              </div>
            </li>
          @endforeach
        </ul>

        @if ($slideshow->items->count() > 1)
          <div class="sl-addform__foot">
            <button id="save-order" type="button" class="sl-btn sl-btn--primary">
              <i class="fas fa-floppy-disk"></i> {{ __('admin.slideshow.save_order') }}
            </button>
          </div>
        @endif
      </div>
    @else
      <div class="sl-empty" x-show="!adding" x-cloak>
        <i class="fa-regular fa-images"></i>
        <p>{{ __('admin.slideshow.no_slides') }}</p>
        <button type="button" class="sl-btn sl-btn--primary" @click="adding = true">
          <i class="fas fa-plus"></i> {{ __('admin.slideshow.add_slide') }}
        </button>
      </div>
    @endif
  </div>

  {{-- ░░░ МОДАЛ РЕДАКТИРОВАНИЯ ░░░ --}}
  {{-- Затемнение задаём инлайн: bg-black/50 в этой Tailwind-сборке не рендерится
       (opacity-модификаторы отсутствуют — см. CLAUDE.md). --}}
  <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,.5)">
    <div class="sl-modal__box bg-white dark:bg-gray-800 w-full max-w-lg p-6 shadow-xl">
      <h3 class="sl-modal__title"><i class="fas fa-pen"></i> {{ __('admin.slideshow.edit_slide') }}</h3>
      <input type="hidden" id="editId">
      <div class="grid gap-3 sl-modal__fields">
        <div>
          <label class="sl-label"><i class="fas fa-align-left"></i> {{ __('admin.slideshow.caption') }}</label>
          <input id="editCaption" type="text" class="sl-input" placeholder="{{ __('admin.slideshow.caption') }}">
        </div>
        <div>
          <label class="sl-label"><i class="fas fa-magnifying-glass"></i> {{ __('admin.slideshow.alt') }}</label>
          <input id="editAltText" type="text" class="sl-input" placeholder="{{ __('admin.slideshow.alt_short') }}">
        </div>
        <div>
          <label class="sl-label"><i class="fas fa-link"></i> {{ __('admin.slideshow.link') }}</label>
          <input id="editLink" type="url" class="sl-input" placeholder="https://...">
        </div>
        <div>
          <label class="sl-label"><i class="fas fa-location-dot"></i> {{ __('admin.slideshow.text_pos') }}</label>
          <select id="editTextPosition" class="sl-input">
            <option value="top-left">{{ __('admin.slideshow.tp_tl') }}</option>
            <option value="top-center">{{ __('admin.slideshow.tp_tc') }}</option>
            <option value="top-right">{{ __('admin.slideshow.tp_tr') }}</option>
            <option value="center">{{ __('admin.slideshow.tp_c') }}</option>
            <option value="bottom-left">{{ __('admin.slideshow.tp_bl') }}</option>
            <option value="bottom-center">{{ __('admin.slideshow.tp_bc') }}</option>
            <option value="bottom-right">{{ __('admin.slideshow.tp_br') }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="sl-label"><i class="fas fa-font"></i> {{ __('admin.slideshow.text_color') }}</label>
            <input id="editTextColor" type="color" class="sl-color">
          </div>
          <div>
            <label class="sl-label"><i class="fas fa-fill-drip"></i> {{ __('admin.slideshow.bg_color') }}</label>
            <input id="editBackgroundColor" type="color" class="sl-color">
          </div>
        </div>
      </div>
      <div class="mt-5 flex justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="sl-btn">{{ __('admin.cancel') }}</button>
        <button type="button" onclick="submitEdit()" class="sl-btn sl-btn--primary">{{ __('admin.save') }}</button>
      </div>
    </div>
  </div>
@endsection

@include('Slideshow::admin.partials.styles')

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
          alert(@js(__('admin.slideshow.save_failed')));
        }
      }).catch(() => alert(@js(__('admin.slideshow.network_failed'))));
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
        .then(d => alert(d.success
          ? @js(__('admin.slideshow.order_saved'))
          : @js(__('admin.slideshow.order_failed'))))
        .catch(() => alert(@js(__('admin.slideshow.network_failed'))));
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
          img.className = '';
          img.src = URL.createObjectURL(file);
          previewBox.appendChild(img);
        } else if (file.type.startsWith('video/')) {
          const v = document.createElement('video');
          v.className = '';
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
          drop.classList.add('is-over');
        })
      );
      ['dragleave','drop'].forEach(ev =>
        drop.addEventListener(ev, e => {
          e.preventDefault();
          drop.classList.remove('is-over');
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
