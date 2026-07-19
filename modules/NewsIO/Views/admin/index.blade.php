@extends('layouts.admin')

@section('title', 'Импорт/Экспорт новостей')

@section('content')
<div class="space-y-6">
  {{-- ======= Шапка ======= --}}
  <header class="flex items-start md:items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
        🧩 Импорт / Экспорт новостей
      </h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Быстрые операции с контентом. Форматы: JSON · NDJSON · CSV · ZIP.
      </p>
    </div>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ===================== EXPORT ===================== --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
      <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-800">
        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">📤 Экспорт</span>
        <span class="inline-flex items-center rounded-full text-[11px] px-2 py-0.5 bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">выгрузка</span>
      </div>

      <form method="POST" action="{{ route('admin.newsio.export') }}" class="grid gap-5 px-5 py-5">
        @csrf

        {{-- Формат --}}
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Формат файла</label>
          <select name="format"
                  class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="json">JSON (массив)</option>
            <option value="ndjson">NDJSON (по строке)</option>
            <option value="csv">CSV</option>
            <option value="zip">ZIP (manifest.json + media/*)</option>
          </select>
          <p class="mt-1 text-xs text-gray-500">ZIP включает <code>manifest.json</code> и папку <code>media/*</code>.</p>
        </div>

        {{-- Категории --}}
        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Категории (фильтр)</label>
          <select name="category_ids[]" multiple
                  class="w-full h-36 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @foreach ($categories as $c)
              <option value="{{ $c->id }}">{{ $c->title }} (ID: {{ $c->id }})</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-gray-500">Оставьте пустым — выгрузятся все категории.</p>
        </div>

        {{-- Даты --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">С даты</label>
            <input type="date" name="date_from"
                   class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">По дату</label>
            <input type="date" name="date_to"
                   class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>

        {{-- Публикация + ZIP-медиа --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Опубликованные</label>
            <select name="published"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="all">Все</option>
              <option value="1">Только опубликованные</option>
              <option value="0">Только черновики</option>
            </select>
          </div>

          <label class="flex items-center gap-2 sm:mt-6 text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="with_media" value="1" class="rounded border-gray-400 dark:border-gray-600">
            <span class="text-sm">Вложить обложки (ZIP)</span>
          </label>
        </div>

        <aside class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
          💡 Совет: сначала отфильтруйте категории/даты, затем скачайте файл в нужном формате.
        </aside>

        <div class="flex justify-end">
          <button class="inline-flex items-center gap-2 rounded-lg bg-black text-white px-4 py-2 hover:bg-gray-800 shadow transition
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
            <i class="fa-solid fa-download"></i> Скачать
          </button>
        </div>
      </form>
    </section>

    {{-- ===================== IMPORT (с drag&drop + рабочий dry-run) ===================== --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
      <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-800">
        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">📥 Импорт</span>
        <span class="inline-flex items-center rounded-full text-[11px] px-2 py-0.5 bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">dry-run + загрузка</span>
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
          :class="{'ring-2 ring-blue-500 border-blue-500': dragging}"
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
              <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Файл импорта</label>

              <input id="importFile" name="file" type="file" accept=".json,.txt,.csv,.zip" class="hidden" @change="onFile($event)" required>

              <div class="flex items-center gap-3">
                <button type="button"
                        class="px-3 h-10 rounded-md bg-black text-white hover:bg-gray-800 transition shadow
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black"
                        @click="openDialog()">
                  Обзор…
                </button>
                <span class="text-xs md:text-sm text-gray-500 truncate" x-text="fileName || 'Файл не выбран'"></span>
              </div>

              <p class="text-xs text-gray-500 mt-2">
                Поддерживаются: JSON / NDJSON / CSV / ZIP (manifest.json + media/*). Можно перетащить файл в область.
              </p>
            </div>
          </div>

          {{-- кликабельный фон — откроет диалог выбора файла --}}
          <button type="button" class="absolute inset-0 rounded-xl focus:outline-none" @click="openDialog()" aria-label="Выбрать файл"></button>
        </div>

        {{-- Параметры совпадений --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Обновлять по</label>
            <select name="update_by"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="slug">slug</option>
              <option value="id">id</option>
              <option value="none">не обновлять (всегда создавать)</option>
            </select>
            <p class="mt-1 text-[11px] text-gray-500">Определяет обновление или создание записи.</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Категории сопоставлять по</label>
            <select name="match_category_by"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="id">id</option>
              <option value="slug">slug</option>
              <option value="title">title</option>
            </select>
            <p class="mt-1 text-[11px] text-gray-500">Ключ связи с категориями.</p>
          </div>

          <label class="flex items-center gap-2 sm:mt-6 text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="create_missing_cats" value="1" class="rounded border-gray-400 dark:border-gray-600">
            <span class="text-sm">Создавать новые категории</span>
          </label>
        </div>

        {{-- Кнопки --}}
        <div class="flex flex-wrap items-center gap-3">
          <button type="button"
                  class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 disabled:opacity-60"
                  :disabled="loading || !hasFile"
                  @click="runDryRun($event)">
            <span x-show="!loading">Проверить (dry-run)</span>
            <span x-show="loading">Проверка…</span>
          </button>

          <button
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 shadow transition
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 disabled:opacity-60"
            :disabled="loading || !hasFile">
            <i class="fa-solid fa-file-import"></i> Импортировать
          </button>
        </div>

        {{-- Ошибка dry-run --}}
        <template x-if="error">
          <div class="p-3 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm" x-text="error"></div>
        </template>

        {{-- Результат dry-run --}}
        <template x-if="summary">
          <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm leading-6">
            <div class="font-semibold mb-2">✅ Проверка завершена. Готово к импорту.</div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-1">
              <div>Всего записей: <span class="font-medium" x-text="summary.total"></span></div>
              <div>С slug: <span class="font-medium" x-text="summary.with_slug"></span></div>
              <div>С id: <span class="font-medium" x-text="summary.with_id"></span></div>
              <div>Ссылок на категории: <span class="font-medium" x-text="summary.cats_refs"></span></div>
              <div>Категории по id: <span class="font-medium" x-text="summary.cats_by_id"></span></div>
              <div>Категории по slug: <span class="font-medium" x-text="summary.cats_by_slug"></span></div>
              <div>Категории по title: <span class="font-medium" x-text="summary.cats_by_title"></span></div>
              <div>Обновлять по: <span class="font-medium" x-text="summary.update_by"></span></div>
              <div>Сопоставлять категории по: <span class="font-medium" x-text="summary.match_by"></span></div>
            </div>
          </div>
        </template>

        {{-- Ошибки импорта --}}
        @if(session('import_errors'))
          <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-sm">
            <div class="font-semibold mb-2 text-red-800">
              ⚠️ Ошибки импорта (показано {{ min(5, session('import_errors_count', 0)) }} из {{ session('import_errors_count', 0) }}):
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
          В JSON объект новости может содержать:
          <code>id, slug, title, content, template, published, cover, price, stock, is_promo, meta_title, meta_description, meta_keywords, meta_header, categories: [{id|slug|title}]</code>.<br>
          В CSV поле <code>categories</code> — список через запятую (slug), напр.: <code>news,updates</code>.
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
        } catch (e) {
          this.error = 'Ошибка проверки: ' + (e?.message || e);
        } finally {
          this.loading = false;
        }
      }
    }
  }
</script>
@endsection
