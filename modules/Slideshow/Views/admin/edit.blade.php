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

@push('styles')
<style>
    /* ── Раздел «Слайдшоу» ────────────────────────────────────────────
       Литеральный CSS, а не Tailwind-утилиты: в собранном
       tailwind.min.css этого проекта нет ни прозрачности через дробь
       (была `bg-gray-50/60` — не рендерилась вовсе), ни произвольных
       значений (`max-h-[90vh]`). Скругления в панели и так сняты общим
       рубильником `body.admin-sharp`. */

    .sl-cardhead{ display:flex; align-items:center; gap:.5rem; padding:.7rem 1.25rem;
        font-size:.72rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#64748b; border-bottom:1px solid #e5e7eb }
    .sl-cardhead i{ color:var(--admin-primary,#6366f1) }
    .sl-cardhead--row{ justify-content:space-between; flex-wrap:wrap; gap:.75rem }
    .sl-cardhead--row > span{ display:inline-flex; align-items:center; gap:.5rem }
    .sl-cardhead__right{ gap:.6rem }
    .dark .sl-cardhead{ color:#94a3b8; border-bottom-color:#374151 }

    .sl-count{ min-width:1.6rem; padding:.1rem .45rem; font-size:.72rem; font-weight:700;
        text-align:center; color:#4b5563; background:#f3f4f6; border:1px solid #e5e7eb }
    .dark .sl-count{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    /* ── Поля ── */
    .sl-field{ display:flex; flex-direction:column; gap:.3rem; min-width:0 }
    .sl-label{ display:inline-flex; align-items:center; gap:.4rem;
        font-size:.78rem; font-weight:600; color:#374151 }
    .sl-label i{ width:.9rem; text-align:center; color:#9ca3af }
    .dark .sl-label{ color:#d1d5db }

    .sl-input{ display:block; width:100%; padding:.5rem .75rem; font-size:.875rem;
        color:#111827; background:#fff; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s }
    .sl-input:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .dark .sl-input{ color:#f3f4f6; background:#111827; border-color:#374151 }
    textarea.sl-input{ resize:vertical }

    .sl-color{ width:100%; height:2.4rem; padding:.15rem; background:#fff;
        border:1px solid #d1d5db; cursor:pointer }
    .dark .sl-color{ background:#111827; border-color:#374151 }

    .sl-hint{ font-size:.72rem; line-height:1.4; color:#6b7280 }
    .dark .sl-hint{ color:#9ca3af }

    /* ── Переключатели ── */
    .sl-switches{ display:flex; flex-wrap:wrap; align-items:center; gap:1.25rem;
        margin-top:1.1rem; padding-top:1rem; border-top:1px solid #e5e7eb }
    .dark .sl-switches{ border-top-color:#374151 }
    .sl-switch{ display:inline-flex; align-items:center; gap:.55rem; font-size:.85rem;
        color:#374151; cursor:pointer }
    .dark .sl-switch{ color:#d1d5db }
    .sl-switches__save{ margin-left:auto }

    /* ── Кнопки ── */
    .sl-btn{ display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .8rem;
        font-size:.8rem; font-weight:600; white-space:nowrap; cursor:pointer;
        color:#374151; background:#fff; border:1px solid #d1d5db; text-decoration:none;
        transition:background-color .15s, border-color .15s, color .15s }
    .sl-btn:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
    .dark .sl-btn{ color:#d1d5db; background:#1f2937; border-color:#374151 }
    .sl-btn--primary{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary) }
    .sl-btn--primary:hover{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary); filter:brightness(1.08) }

    .sl-icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; cursor:pointer; font-size:.8rem;
        color:#4b5563; background:#fff; border:1px solid #e5e7eb;
        transition:border-color .15s, color .15s }
    .sl-icon:hover{ border-color:var(--admin-primary); color:var(--admin-primary) }
    .sl-icon--danger:hover{ border-color:#dc2626; color:#dc2626 }
    .dark .sl-icon{ color:#d1d5db; background:#111827; border-color:#374151 }

    /* ── Форма добавления ── */
    .sl-addform{ display:grid; gap:1.1rem; padding:1.25rem;
        background:#f9fafb; border-bottom:1px solid #e5e7eb }
    .dark .sl-addform{ background:#0f172a; border-bottom-color:#374151 }
    .sl-addform__foot{ display:flex; justify-content:flex-end; margin-top:.25rem }

    /* ── Зона перетаскивания ── */
    .sl-dropzone{ display:flex; flex-wrap:wrap; gap:1rem; padding:1rem;
        background:#fff; border:1px dashed #cbd5e1; transition:border-color .15s }
    .sl-dropzone:hover{ border-color:var(--admin-primary) }
    .sl-dropzone.is-over{ border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 20%, transparent) }
    .dark .sl-dropzone{ background:#111827; border-color:#374151 }

    .sl-dropzone__preview{ display:flex; align-items:center; justify-content:center;
        width:11rem; height:8rem; flex:none; overflow:hidden;
        background:#f3f4f6; border:1px solid #e5e7eb }
    .dark .sl-dropzone__preview{ background:#1f2937; border-color:#374151 }
    .sl-dropzone__preview svg{ width:2.25rem; height:2.25rem; color:#cbd5e1 }
    .sl-dropzone__preview img, .sl-dropzone__preview video{ width:100%; height:100%; object-fit:cover }

    .sl-dropzone__body{ flex:1; min-width:14rem; display:flex; flex-direction:column; gap:.5rem }
    .sl-dropzone__text{ margin:0; font-size:.85rem; color:#4b5563 }
    .dark .sl-dropzone__text{ color:#d1d5db }
    .sl-dropzone__or{ color:#9ca3af }
    .sl-dropzone__actions{ display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; margin-top:auto }

    /* ── Список слайдов ── */
    .sl-dragnote{ display:flex; align-items:center; gap:.4rem; margin:0 0 .75rem }
    .sl-grid{ display:grid; gap:1rem; margin:0; padding:0; list-style:none;
        grid-template-columns:repeat(auto-fill, minmax(15rem, 1fr)) }

    .sl-slide{ display:flex; flex-direction:column; overflow:hidden;
        background:#fff; border:1px solid #e5e7eb; transition:border-color .15s }
    .sl-slide:hover{ border-color:var(--admin-primary) }
    .dark .sl-slide{ background:#111827; border-color:#374151 }

    .sl-slide__media{ position:relative; height:9.5rem; background:#f3f4f6 }
    .dark .sl-slide__media{ background:#1f2937 }
    .sl-slide__media img, .sl-slide__media video{ width:100%; height:100%; object-fit:cover }

    .sl-slide__kind{ position:absolute; left:.5rem; top:.5rem;
        display:inline-flex; align-items:center; justify-content:center;
        width:1.6rem; height:1.6rem; font-size:.7rem; color:#fff; background:rgba(17,24,39,.65) }

    .sl-slide__actions{ position:absolute; right:.5rem; top:.5rem; display:flex; gap:.35rem }

    .sl-slide__body{ display:flex; flex-direction:column; gap:.25rem; padding:.7rem .8rem;
        border-top:1px solid #e5e7eb }
    .dark .sl-slide__body{ border-top-color:#374151 }
    .sl-slide__caption{ font-size:.85rem; font-weight:600; color:#111827;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dark .sl-slide__caption{ color:#f3f4f6 }
    .sl-slide__link{ display:block; font-size:.72rem; color:var(--admin-primary);
        text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .sl-slide__link:hover{ text-decoration:underline }

    /* ── Пусто ── */
    .sl-empty{ display:flex; flex-direction:column; align-items:center; gap:.7rem;
        padding:2.5rem 1.25rem; text-align:center; color:#6b7280 }
    .sl-empty i{ font-size:1.8rem; color:#cbd5e1 }
    .sl-empty p{ margin:0; font-size:.88rem }
    .dark .sl-empty{ color:#9ca3af }

    /* ── Модал ── */
    .sl-modal__box{ max-height:90vh; overflow-y:auto }
    .sl-modal__title{ display:flex; align-items:center; gap:.5rem; margin-bottom:1rem;
        font-size:1rem; font-weight:700; color:#111827 }
    .sl-modal__title i{ color:var(--admin-primary,#6366f1) }
    .dark .sl-modal__title{ color:#f3f4f6 }
    .sl-modal__fields{ display:grid; gap:.9rem }
</style>
@endpush

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
