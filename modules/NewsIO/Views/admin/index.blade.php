@extends('layouts.admin')

@section('title', 'Импорт/Экспорт новостей')

@section('content')
{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex items-center gap-3">
  <span class="admin-icon-badge"><i class="fas fa-right-left"></i></span>
  <div class="min-w-0 flex-1">
    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.newsio.title') }}</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      {{ __('admin.newsio.hint') }}
    </p>
  </div>

  {{-- Сводка: сразу видно, какой объём данных доступен к выгрузке --}}
  <div class="flex flex-wrap items-center gap-2 text-xs shrink-0">
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
      <i class="fas fa-newspaper"></i> {{ __('admin.common.total') }} {{ $stats['news'] }}
    </span>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
      <i class="fas fa-circle-check"></i> {{ __('admin.common.published') }}: {{ $stats['published'] }}
    </span>
    @if($stats['drafts'] > 0)
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
        <i class="fas fa-clock"></i> Черновиков: {{ $stats['drafts'] }}
      </span>
    @endif
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
      <i class="fas fa-tags"></i> {{ __('admin.newsio.categories_count') }} {{ $stats['cats'] }}
    </span>
  </div>
</div>

<div class="space-y-6">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ===================== EXPORT ===================== --}}
    <section class="admin-card overflow-hidden">
      <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-800">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
          <i class="fas fa-download text-indigo-500"></i> {{ __('admin.newsio.export') }}
        </span>
        <span class="inline-flex items-center text-xs px-2 py-0.5 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ __('admin.newsio.export_sub') }}</span>
      </div>

      <form method="POST" action="{{ route('admin.newsio.export') }}" class="grid gap-5 px-5 py-5">
        @csrf

        {{-- Формат --}}
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.format') }}</label>
          <select name="format"
                  class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="json">{{ __('admin.newsio.json_array') }}</option>
            <option value="ndjson">{{ __('admin.newsio.ndjson') }}</option>
            <option value="csv">CSV</option>
            <option value="zip">ZIP (manifest.json + media/*)</option>
          </select>
          <p class="mt-1 text-xs text-gray-500">{{ __('admin.newsio.zip_includes') }} <code>manifest.json</code> {{ __('admin.newsio.and_folder') }} <code>media/*</code>.</p>
        </div>

        {{-- Категории: чекбоксы-чипы вместо тесного multiple-select, в котором
             нужно было выделять с Ctrl и не было видно объёма. --}}
        <div>
          <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.newsio.cat_filter') }}</label>

            @if ($categories->isNotEmpty())
              <div class="flex items-center gap-2 text-xs">
                <button type="button" id="catSelectAll"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 border border-gray-300 dark:border-gray-600 font-medium
                               text-gray-700 dark:text-gray-200 hover:border-indigo-400 hover:text-indigo-600 transition">
                  <i class="fas fa-check-double"></i> {{ __('admin.newsio.select_all') }}
                </button>
                <button type="button" id="catClearAll"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 border border-gray-300 dark:border-gray-600 font-medium
                               text-gray-700 dark:text-gray-200 hover:border-indigo-400 hover:text-indigo-600 transition">
                  <i class="fas fa-xmark"></i> {{ __('admin.newsio.deselect') }}
                </button>
                <span id="catSelectedCount" class="text-gray-500 dark:text-gray-400"></span>
              </div>
            @endif
          </div>

          @if ($categories->isEmpty())
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('admin.newsio.no_categories') }}</p>
          @else
            <div class="cat-picker flex flex-wrap gap-2">
              @foreach ($categories as $c)
                <label class="cat-chip" title="{{ __('admin.newsio.news_in_cat') }} {{ $c->news_count }}">
                  <input type="checkbox" name="category_ids[]" value="{{ $c->id }}" class="sr-only">
                  <span class="cat-chip-body">
                    {{ $c->title }}
                    <span class="cat-count">{{ $c->news_count }}</span>
                  </span>
                </label>
              @endforeach
            </div>
          @endif

          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ __('admin.newsio.none_selected') }}
          </p>
        </div>

        {{-- Даты --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.date_from') }}</label>
            <input type="date" name="date_from"
                   class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.date_to') }}</label>
            <input type="date" name="date_to"
                   class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        {{-- Публикация + ZIP-медиа --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.published') }}</label>
            <select name="published"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="all">{{ __('admin.newsio.all') }}</option>
              <option value="1">{{ __('admin.newsio.published_only') }}</option>
              <option value="0">{{ __('admin.newsio.drafts_only') }}</option>
            </select>
          </div>

          <label class="flex items-center gap-2 sm:mt-6 text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="with_media" value="1" class="rounded border-gray-400 dark:border-gray-600">
            <span class="text-sm">{{ __('admin.newsio.with_covers') }}</span>
          </label>
        </div>

        <aside class="admin-note px-3 py-2 text-xs">
          <i class="fas fa-lightbulb"></i> {{ __('admin.newsio.tip') }}
        </aside>

        <div class="flex justify-end">
          <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fa-solid fa-download"></i> {{ __('admin.newsio.download') }}
          </button>
        </div>
      </form>
    </section>

    {{-- ===================== IMPORT (с drag&drop + рабочий dry-run) ===================== --}}
    <section class="admin-card overflow-hidden">
      <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-800">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
          <i class="fas fa-upload text-indigo-500"></i> {{ __('admin.newsio.import') }}
        </span>
        <span class="inline-flex items-center text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">{{ __('admin.newsio.import_sub') }}</span>
      </div>

      <form method="POST"
            action="{{ route('admin.newsio.import') }}"
            enctype="multipart/form-data"
            class="grid gap-5 px-5 py-5"
            x-data="importIO()"
            x-init="init()">
        @csrf

        {{-- Drop-zone + Обзор --}}
        <div
          class="group relative rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/60 p-4 md:p-5"
          :class="{'ring-2 ring-indigo-500 border-indigo-500': dragging}"
          @dragenter.prevent="dragging = true"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="onDrop($event)">

          <div class="flex flex-col sm:flex-row gap-4">
            <div class="w-full sm:w-48 h-40 rounded-lg overflow-hidden bg-white dark:bg-gray-900 grid place-items-center ring-1 ring-gray-100 dark:ring-gray-800">
              <svg x-show="!previewUrl" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm8-1.5V8h4.5L12 3.5Z"/>
              </svg>
              <img x-show="previewUrl" :src="previewUrl" class="w-full h-full object-cover" alt="preview">
            </div>

            <div class="flex-1">
              <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.file') }}</label>

              <input id="importFile" name="file" type="file" accept=".json,.txt,.csv,.zip" class="hidden" @change="onFile($event)" required>

              <div class="flex items-center gap-3">
                <button type="button"
                        class="inline-flex items-center gap-2 px-4 h-10 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition"
                        @click="openDialog()">
                  <i class="fas fa-folder-open"></i> {{ __('admin.newsio.browse') }}
                </button>
                <span class="text-xs md:text-sm text-gray-500 truncate" x-text="fileName || 'Файл не выбран'"></span>
              </div>

              <p class="text-xs text-gray-500 mt-2">
                {{ __('admin.newsio.file_hint') }}
              </p>
            </div>
          </div>

          {{-- кликабельный фон — откроет диалог выбора файла --}}
          <button type="button" class="absolute inset-0 rounded-xl focus:outline-none" @click="openDialog()" aria-label="{{ __('admin.newsio.choose_file') }}"></button>
        </div>

        {{-- Параметры совпадений --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.update_by') }}</label>
            <select name="update_by"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="slug">slug</option>
              <option value="id">id</option>
              <option value="none">{{ __('admin.newsio.never_update') }}</option>
            </select>
            <p class="mt-1 text-[11px] text-gray-500">{{ __('admin.newsio.update_hint') }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('admin.newsio.match_cats') }}</label>
            <select name="match_category_by"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="id">id</option>
              <option value="slug">slug</option>
              <option value="title">title</option>
            </select>
            <p class="mt-1 text-[11px] text-gray-500">{{ __('admin.newsio.match_hint') }}</p>
          </div>

          <label class="flex items-center gap-2 sm:mt-6 text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="create_missing_cats" value="1" class="rounded border-gray-400 dark:border-gray-600">
            <span class="text-sm">{{ __('admin.newsio.create_cats') }}</span>
          </label>
        </div>

        {{-- Кнопки --}}
        <div class="flex flex-wrap items-center gap-3">
          <button type="button"
                  class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                         text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-60"
                  :disabled="loading || !hasFile"
                  @click="runDryRun($event)">
            <i class="fas fa-vial"></i>
            <span x-show="!loading">{{ __('admin.newsio.dry_run') }}</span>
            <span x-show="loading">{{ __('admin.newsio.checking') }}</span>
          </button>

          <button
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition disabled:opacity-60"
            :disabled="loading || !hasFile">
            <i class="fa-solid fa-file-import"></i> {{ __('admin.newsio.do_import') }}
          </button>
        </div>

        {{-- Ошибка dry-run --}}
        <template x-if="error">
          <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 text-sm" x-text="error"></div>
        </template>

        {{-- Результат dry-run. Палитры amber в сборке нет (см. CLAUDE.md) — берём indigo. --}}
        <template x-if="summary">
          <div class="border-l-4 border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-gray-800 dark:text-gray-100 p-4 text-sm leading-6">
            <div class="font-semibold mb-2 flex items-center gap-2">
              <i class="fas fa-circle-check text-green-600"></i> {{ __('admin.newsio.check_done') }}
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-1">
              <div>{{ __('admin.newsio.total_records') }} <span class="font-medium" x-text="summary.total"></span></div>
              <div>{{ __('admin.newsio.with_slug') }} <span class="font-medium" x-text="summary.with_slug"></span></div>
              <div>{{ __('admin.newsio.with_id') }} <span class="font-medium" x-text="summary.with_id"></span></div>
              <div>{{ __('admin.newsio.cat_links') }} <span class="font-medium" x-text="summary.cats_refs"></span></div>
              <div>{{ __('admin.newsio.cats_by_id') }} <span class="font-medium" x-text="summary.cats_by_id"></span></div>
              <div>{{ __('admin.newsio.cats_by_slug') }} <span class="font-medium" x-text="summary.cats_by_slug"></span></div>
              <div>{{ __('admin.newsio.cats_by_title') }} <span class="font-medium" x-text="summary.cats_by_title"></span></div>
              <div>{{ __('admin.newsio.update_by_label') }} <span class="font-medium" x-text="summary.update_by"></span></div>
              <div>{{ __('admin.newsio.match_by_label') }} <span class="font-medium" x-text="summary.match_by"></span></div>
            </div>
          </div>
        </template>

        {{-- Предупреждения dry-run: что пойдёт не так при импорте.
             Раньше сервер присылал сюда весь дамп записей, и блока не было вовсе. --}}
        <template x-if="warnings && warnings.length">
          <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-900 dark:text-yellow-100 p-4 text-sm">
            <div class="font-semibold mb-2 flex items-center gap-2">
              <i class="fas fa-triangle-exclamation"></i>
              {{ __('admin.newsio.attention') }} (<span x-text="warnings.length"></span>):
            </div>
            <ul class="list-disc list-inside space-y-1">
              <template x-for="(w, i) in warnings" :key="i">
                <li x-text="w"></li>
              </template>
            </ul>
          </div>
        </template>

        {{-- Ошибки импорта --}}
        @if(session('import_errors'))
          <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 p-4 text-sm">
            <div class="font-semibold mb-2 text-red-800 dark:text-red-200 flex items-center gap-2">
              <i class="fas fa-circle-exclamation"></i>
              Ошибки импорта (показано {{ min(5, session('import_errors_count', 0)) }} из {{ session('import_errors_count', 0) }}):
            </div>
            <ul class="list-disc list-inside space-y-1 text-red-700">
              @foreach(session('import_errors', []) as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            @if(session('import_errors_count', 0) > 5)
              <p class="mt-2 text-xs text-red-600">... и еще {{ session('import_errors_count', 0) - 5 }} ошибок. Проверьте логи для полного списка.</p>
            @endif
          </div>
        @endif

        <div class="text-xs text-gray-500 leading-relaxed">
          {{ __('admin.newsio.json_note') }}
          <code>id, slug, title, content, template, published, cover, price, stock, is_promo, meta_title, meta_description, meta_keywords, meta_header, categories: [{id|slug|title}]</code>.<br>
          {{ __('admin.newsio.csv_note') }} <code>categories</code> {{ __('admin.newsio.csv_list') }} <code>news,updates</code>.
        </div>
      </form>
    </section>

  </div>
</div>

{{-- ===== JS: dnd + рабочий dry-run (Alpine) ===== --}}
<script>
  function importIO () {
    return {
      // state
      file: null,
      fileName: '',
      previewUrl: '',
      dragging: false,
      loading: false,
      error: null,
      summary: null,
      warnings: [],

      get hasFile() { return !!this.file; },

      init() {
        // зачистка ObjectURL при смене файла/уходе со страницы
        window.addEventListener('beforeunload', () => {
          if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        });
      },

      openDialog() {
        document.getElementById('importFile')?.click();
      },

      onFile(e) {
        const f = e.target.files?.[0];
        this.setFile(f);
      },

      onDrop(e) {
        this.dragging = false;
        const f = e.dataTransfer?.files?.[0];
        if (!f) return;
        // пробуем прокинуть файл в input.files (для корректной отправки формы)
        const input = document.getElementById('importFile');
        if (input) {
          const dt = new DataTransfer();
          dt.items.add(f);
          input.files = dt.files;
        }
        this.setFile(f);
      },

      setFile(f) {
        this.error = null;
        this.summary = null;
        this.warnings = [];
        this.file = f || null;
        this.fileName = f ? f.name : '';
        // превью только для изображений
        if (this.previewUrl) { URL.revokeObjectURL(this.previewUrl); this.previewUrl = ''; }
        if (f && f.type && f.type.startsWith('image/')) {
          this.previewUrl = URL.createObjectURL(f);
        }
      },

      async runDryRun(ev) {
        try {
          this.error = null;
          this.summary = null;
          this.warnings = [];
          if (!this.hasFile) { this.error = 'Выберите файл для проверки.'; return; }
          this.loading = true;

          const form = ev.target.closest('form');
          const data = new FormData(form);
          // Важно: сервер ожидает поле "file" — оно уже в форме (input hidden)
          const res = await fetch('{{ route('admin.newsio.dryrun') }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: data
          });

          if (!res.ok) {
            // читаем текст/JSON ошибки, чтобы показать пользователю
            let msg = 'HTTP ' + res.status;
            try {
              const j = await res.json();
              if (j?.message) msg = j.message;
            } catch (_) {}
            throw new Error(msg);
          }

          const json = await res.json();
          // ожидаем summary в json.preview или в корне
          this.summary = json.preview || json;
          // предупреждения приходят массивом строк (Importer::collectWarnings)
          this.warnings = Array.isArray(json.warnings) ? json.warnings : [];
        } catch (e) {
          this.error = 'Ошибка проверки: ' + (e?.message || e);
        } finally {
          this.loading = false;
        }
      }
    }
  }
</script>

{{-- Массовый выбор категорий: «Выбрать все» / «Снять» + счётчик выбранных. --}}
<script>
  (function () {
    const boxes = () => [...document.querySelectorAll('.cat-picker input[type="checkbox"]')];
    const counter = document.getElementById('catSelectedCount');

    function refresh() {
      if (!counter) return;
      const total = boxes().length;
      const n = boxes().filter(b => b.checked).length;
      counter.textContent = n === 0 ? 'все категории' : `выбрано ${n} из ${total}`;
    }

    document.getElementById('catSelectAll')?.addEventListener('click', () => {
      boxes().forEach(b => (b.checked = true));
      refresh();
    });

    document.getElementById('catClearAll')?.addEventListener('click', () => {
      boxes().forEach(b => (b.checked = false));
      refresh();
    });

    boxes().forEach(b => b.addEventListener('change', refresh));
    refresh();
  })();
</script>

{{-- Чипы выбора категорий: настоящий CSS-селектор input:checked, а не
     peer-checked (этого варианта в собранном tailwind.min.css нет — см. CLAUDE.md). --}}
<style>
  .cat-picker .cat-chip{ display:inline-flex; cursor:pointer; user-select:none; }
  .cat-picker .cat-chip-body{
      display:inline-flex; align-items:center; gap:.45rem;
      padding:.4rem .7rem; font-size:.82rem; line-height:1.2;
      border:1px solid #d1d5db; background:#fff; color:#374151;
      transition:background .15s ease, color .15s ease, border-color .15s ease;
  }
  .dark .cat-picker .cat-chip-body{ background:#111827; border-color:#374151; color:#d1d5db; }
  .cat-picker .cat-chip:hover .cat-chip-body{ border-color:#818cf8; color:#4f46e5; }
  .cat-picker .cat-count{
      display:inline-flex; align-items:center; justify-content:center; min-width:1.25rem;
      padding:0 .3rem; font-size:.68rem; font-weight:700;
      background:rgba(99,102,241,.12); color:#4338ca;
  }
  .dark .cat-picker .cat-count{ background:rgba(99,102,241,.25); color:#c7d2fe; }
  /* Соседний селектор работает во всех браузерах (в отличие от :has). */
  .cat-picker input:checked + .cat-chip-body{
      background:#4f46e5; border-color:#4f46e5; color:#fff;
      box-shadow:0 8px 18px -10px rgba(99,102,241,.7);
  }
  .cat-picker input:checked + .cat-chip-body .cat-count{ background:rgba(255,255,255,.22); color:#fff; }
  .cat-picker input:focus-visible + .cat-chip-body{ outline:2px solid #818cf8; outline-offset:2px; }
</style>
@endsection
