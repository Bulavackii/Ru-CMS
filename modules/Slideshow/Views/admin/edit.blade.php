@extends('layouts.admin')

@section('title', 'Редактирование слайдшоу')
@section('header', 'Слайды: ' . $slideshow->title)

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

  {{-- ░░░ НАСТРОЙКИ СЛАЙДШОУ ░░░ --}}
  <div class="admin-card mb-6 overflow-visible">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
      <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
        <i class="fas fa-sliders text-indigo-500"></i> Настройки слайдшоу
      </span>
    </div>
    <form method="POST" action="{{ route('admin.slideshow.update', $slideshow->id) }}" class="p-5">
      @csrf
      @method('PUT')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🏷️ {{ __('admin.slideshow.name_short') }}</label>
          <input type="text" name="title" value="{{ old('title', $slideshow->title) }}"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" required>
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📍 {{ __('admin.slideshow.position_short') }}</label>
          <select name="position" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
            <option value="top" {{ $slideshow->position === 'top' ? 'selected' : '' }}>{{ __('admin.slideshow.top') }}</option>
            <option value="bottom" {{ $slideshow->position === 'bottom' ? 'selected' : '' }}>{{ __('admin.slideshow.bottom') }}</option>
          </select>
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">⏱️ {{ __('admin.slideshow.autoplay') }}</label>
          <input type="number" name="autoplay_delay" value="{{ old('autoplay_delay', $slideshow->autoplay_delay ?? 5000) }}" min="1000" max="30000" step="500"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🎬 {{ __('admin.slideshow.effect') }}</label>
          <select name="transition_effect" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
            <option value="slide" {{ ($slideshow->transition_effect ?? 'slide') === 'slide' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_slide') }}</option>
            <option value="fade" {{ ($slideshow->transition_effect ?? '') === 'fade' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_fade') }}</option>
            <option value="cube" {{ ($slideshow->transition_effect ?? '') === 'cube' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_cube') }}</option>
            <option value="coverflow" {{ ($slideshow->transition_effect ?? '') === 'coverflow' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_cover') }}</option>
            <option value="flip" {{ ($slideshow->transition_effect ?? '') === 'flip' ? 'selected' : '' }}>{{ __('admin.slideshow.fx_flip') }}</option>
          </select>
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📏 {{ __('admin.slideshow.height') }}</label>
          <input type="text" name="height" value="{{ old('height', $slideshow->height) }}"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2"
                 placeholder="{{ __('admin.slideshow.height_ph') }}">
        </div>
        <div>
          <label class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📝 {{ __('admin.slideshow.description') }}</label>
          <textarea name="description" rows="2"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">{{ old('description', $slideshow->description) }}</textarea>
        </div>
        <div class="flex items-center gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="published" value="1" {{ $slideshow->published ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700 dark:text-gray-300">✅ {{ __('admin.slideshow.published') }}</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="show_pagination" value="1" {{ ($slideshow->show_pagination ?? true) ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700 dark:text-gray-300">🔘 {{ __('admin.slideshow.show_pagination') }}</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="show_navigation" value="1" {{ ($slideshow->show_navigation ?? true) ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700 dark:text-gray-300">⬅️➡️ {{ __('admin.slideshow.show_nav') }}</span>
          </label>
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm transition">
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
        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.slideshow.add_slide') }}</span>
        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">{{ __('admin.slideshow.image_or_video') }}</span>
      </div>

      <div class="p-5 grid gap-5">
        {{-- Файл (Drop-zone + Обзор) --}}
        <div>
          <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">🖼️ {{ __('admin.slideshow.file') }}</label>

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
                Перетащите файл сюда <span class="text-gray-400">{{ __('admin.slideshow.or') }}</span> нажмите <span class="font-medium">{{ __('admin.slideshow.browse') }}</span>
                <div class="text-xs text-gray-400 mt-1">
                  {{ __('admin.slideshow.size_hint') }}
                </div>
              </div>

              <div class="flex items-center gap-3">
                <input id="media" name="media" type="file" class="hidden" accept="image/*,video/*" required>

                <button type="button" id="browseBtn"
                        class="px-3 h-10 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm transition transition">
                  {{ __('admin.slideshow.browse') }}
                </button>

                <span id="fileName"
                      class="text-xs text-gray-500 truncate">{{ __('admin.slideshow.no_file') }}</span>
              </div>
            </div>
          </div>
        </div>

        {{-- Подпись --}}
        <div>
          <label for="caption" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📝 {{ __('admin.slideshow.caption') }}</label>
          <input id="caption" name="caption" type="text"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2"
                 placeholder="{{ __('admin.slideshow.caption_ph') }}">
        </div>

        {{-- Alt-текст для SEO --}}
        <div>
          <label for="alt_text" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🔍 {{ __('admin.slideshow.alt') }}</label>
          <input id="alt_text" name="alt_text" type="text"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2"
                 placeholder="{{ __('admin.slideshow.alt_ph') }}">
          <p class="text-xs text-gray-500 mt-1">{{ __('admin.slideshow.alt_hint') }}</p>
        </div>

        {{-- Ссылка --}}
        <div>
          <label for="link" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🔗 {{ __('admin.slideshow.link') }}</label>
          <input id="link" name="link" type="url" placeholder="https://example.com"
                 class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
        </div>

        {{-- Позиция текста --}}
        <div>
          <label for="text_position" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📍 {{ __('admin.slideshow.text_pos') }}</label>
          <select id="text_position" name="text_position"
                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
            <option value="top-left">{{ __('admin.slideshow.tp_tl') }}</option>
            <option value="top-center">{{ __('admin.slideshow.tp_tc') }}</option>
            <option value="top-right">{{ __('admin.slideshow.tp_tr') }}</option>
            <option value="center">{{ __('admin.slideshow.tp_c') }}</option>
            <option value="bottom-left">{{ __('admin.slideshow.tp_bl') }}</option>
            <option value="bottom-center">{{ __('admin.slideshow.tp_bc') }}</option>
            <option value="bottom-right" selected>{{ __('admin.slideshow.tp_br') }}</option>
          </select>
        </div>

        {{-- Цвета текста --}}
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="text_color" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 {{ __('admin.slideshow.text_color') }}</label>
            <input id="text_color" name="text_color" type="color" value="#ffffff"
                   class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
          <div>
            <label for="background_color" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 {{ __('admin.slideshow.bg_color') }}</label>
            <input id="background_color" name="background_color" type="color" value="#2563eb"
                   class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
        </div>

        {{-- Порядок + Позиция --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="order" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">🔢 {{ __('admin.slideshow.order') }}</label>
            <input id="order" name="order" type="number" value="0"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
          </div>
          <div>
            <label for="position" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">📍 {{ __('admin.slideshow.position_short') }}</label>
            <select id="position" name="position"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 px-3 py-2">
              <option value="top" {{ old('position', $slideshow->position ?? '') == 'top' ? 'selected' : '' }}>🔝 {{ __('admin.slideshow.top') }}</option>
              <option value="bottom" {{ old('position', $slideshow->position ?? '') == 'bottom' ? 'selected' : '' }}>🔻 {{ __('admin.slideshow.bottom') }}</option>
            </select>
          </div>
        </div>

        {{-- Кнопка добавления --}}
        <div class="flex justify-end">
          <button type="submit"
                  class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fa-solid fa-plus"></i> Добавить слайд
          </button>
        </div>
      </div>
    </div>
  </form>

  {{-- ░░░ ТЕКУЩИЕ СЛАЙДЫ ░░░ --}}
  @if ($slideshow->items->count())
    <h2 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">📂 {{ __('admin.slideshow.current_slides') }}</h2>

    <ul id="sortable-slides" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      @foreach ($slideshow->items->sortBy('order') as $slide)
        <li data-id="{{ $slide->id }}" id="slide-{{ $slide->id }}"
            class="relative border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition cursor-move">

          @if ($slide->media_type === 'image')
            <img src="{{ asset('storage/' . $slide->file_path) }}" class="w-full h-48 object-cover" alt="{{ __('admin.slideshow.fx_slide') }}">
          @else
            <video class="w-full h-48 object-cover" controls>
              <source src="{{ asset('storage/' . $slide->file_path) }}">
            </video>
          @endif

          <div class="p-3 text-sm border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200 space-y-1">
            <div><strong>📝 {{ __('admin.slideshow.caption_label') }}</strong> <span class="caption">{{ $slide->caption ?: '—' }}</span></div>
            <div><strong>🔗 {{ __('admin.slideshow.link_label') }}</strong> <span class="link">
              @if ($slide->link)
                <a href="{{ $slide->link }}" class="text-blue-600 hover:underline" target="_blank">{{ $slide->link }}</a>
              @else — @endif
            </span></div>
          </div>

          {{-- Действия.
               ⚠️ Кнопка «Редактировать» НЕ РАБОТАЛА: аргументы подставлялись через
               @json, а тот выводит строку в ДВОЙНЫХ кавычках — первая же кавычка
               закрывала атрибут onclick="…", и обработчик обрывался на
               `openEditModal(1, `. Правильная директива для JS внутри HTML-атрибута —
               @js (Illuminate\Support\Js): она экранирует кавычки (JSON_HEX_QUOT). --}}
          <div class="absolute top-2 right-2 flex gap-2">
            <button type="button"
                    class="grid place-items-center w-9 h-9 bg-white dark:bg-gray-900 text-indigo-600 hover:text-indigo-800 shadow"
                    title="{{ __('admin.admin.edit') }}"
                    onclick="openEditModal({{ $slide->id }}, @js($slide->caption ?? ''), @js($slide->alt_text ?? ''), @js($slide->link ?? ''), @js($slide->text_position ?? 'bottom-right'), @js($slide->text_color ?? '#ffffff'), @js($slide->background_color ?? '#2563eb'))">
              <i class="fas fa-edit"></i>
            </button>

            <form method="POST" action="{{ route('admin.slides.destroy', $slide->id) }}"
                  onsubmit="return confirm('Удалить этот слайд?')">
              @csrf
              @method('DELETE')
              <button type="submit"
                      class="grid place-items-center w-9 h-9 bg-white dark:bg-gray-900 text-red-600 hover:text-red-700 shadow"
                      title="{{ __('admin.admin.delete') }}">
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
              class="inline-flex items-center gap-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 shadow">
        <i class="fa-solid fa-floppy-disk"></i> Сохранить порядок
      </button>
    </div>
  @else
    <div class="text-gray-500 dark:text-gray-400">📭 {{ __('admin.slideshow.no_slides') }}</div>
  @endif

  {{-- ░░░ МОДАЛ РЕДАКТИРОВАНИЯ ░░░ --}}
  {{-- Затемнение задаём инлайн: bg-black/50 в этой Tailwind-сборке не рендерится
       (opacity-модификаторы отсутствуют — см. CLAUDE.md). --}}
  <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,.5)">
    <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl max-h-[90vh] overflow-y-auto">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">✏️ {{ __('admin.slideshow.edit_slide') }}</h3>
      <input type="hidden" id="editId">
      <div class="grid gap-3">
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">📝 {{ __('admin.slideshow.caption') }}</label>
          <input id="editCaption" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" placeholder="{{ __('admin.slideshow.caption') }}">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🔍 {{ __('admin.slideshow.alt') }}</label>
          <input id="editAltText" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" placeholder="{{ __('admin.slideshow.alt_short') }}">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🔗 {{ __('admin.slideshow.link') }}</label>
          <input id="editLink" type="url" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2" placeholder="https://...">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">📍 {{ __('admin.slideshow.text_pos') }}</label>
          <select id="editTextPosition" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 px-3 py-2">
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
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 {{ __('admin.slideshow.text_color') }}</label>
            <input id="editTextColor" type="color" class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">🎨 {{ __('admin.slideshow.bg_color') }}</label>
            <input id="editBackgroundColor" type="color" class="w-full h-10 rounded-md border-gray-300 dark:border-gray-700">
          </div>
        </div>
      </div>
      <div class="mt-5 flex justify-end gap-2">
        <button onclick="closeEditModal()" class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">{{ __('admin.admin.cancel') }}</button>
        <button onclick="submitEdit()" class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">{{ __('admin.admin.save') }}</button>
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
