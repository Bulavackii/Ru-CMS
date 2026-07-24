@extends('layouts.admin')

@section('title', 'Страницы')

@push('scripts')
  {{-- Поиск + превью + фронтовая сортировка --}}
  <script>
    // --- серверный поиск с debounce
    // Раньше тут была filterPages(), прятавшая строки на КЛИЕНТЕ: она
    // фильтровала только текущие 10 строк страницы, а серверный ?q= (который
    // index() уже умеет) не вызывался вовсе — страницы за пределами первой
    // десятки в поиск не попадали. Теперь поле — часть GET-формы, и ввод с
    // задержкой отправляет реальный запрос на сервер (поиск по всей таблице).
    function initSearch() {
      const input = document.getElementById('searchInput');
      const form  = document.getElementById('searchForm');
      if (!input || !form) return;
      let timer;
      input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 400);
      });
    }

    // --- открытие/скрытие превью
    function toggleContent(id, btn) {
      const row = document.getElementById(`page-content-${id}`);
      const label = btn.querySelector('span') || btn;

      if (!row.dataset.loaded) {
        // первый показ — грузим HTML
        label.textContent = 'Загружаю…';
        row.classList.remove('hidden');
        fetch(`/admin/pages/${id}/preview`)
          .then(r => r.text())
          .then(html => {
            row.querySelector('.page-content-body').innerHTML = html;
            row.dataset.loaded = '1';
            label.textContent = 'Скрыть';
            // плавная прокрутка к открытому превью
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
          })
          .catch(() => (label.textContent = 'Показать'));
      } else {
        const hidden = row.classList.toggle('hidden');
        label.textContent = hidden ? 'Показать' : 'Скрыть';
        if (!hidden) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    // --- простая фронтовая сортировка (по колонке)
    document.addEventListener('DOMContentLoaded', () => {
      initSearch();
      const table = document.getElementById('pagesTable');
      const body  = table.querySelector('tbody');

      function sortBy(getKey, dir = 1) {
        const rows = Array.from(body.querySelectorAll('tr.page-row'));
        rows.sort((a, b) => {
          const ka = getKey(a);
          const kb = getKey(b);
          // числовой или строковый компаратор
          const na = parseFloat(ka); const nb = parseFloat(kb);
          const cmp = (isFinite(na) && isFinite(nb))
            ? (na - nb)
            : (ka.localeCompare(kb, undefined, { sensitivity: 'base' }));
          return cmp * dir;
        });
        // переносим пары (строка + её скрытая превью-строка)
        rows.forEach(row => {
          const preview = row.nextElementSibling?.classList.contains('page-content') ? row.nextElementSibling : null;
          body.appendChild(row);
          if (preview) body.appendChild(preview);
        });
      }

      // клики по заголовкам
      const headers = table.querySelectorAll('thead th[data-sort]');
      headers.forEach(th => {
        let dir = 1;
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
          headers.forEach(h => h.classList.remove('text-indigo-700','dark:text-indigo-300'));
          th.classList.add('text-indigo-700','dark:text-indigo-300');
          const key = th.dataset.sort;
          if (key === 'title')      sortBy(tr => (tr.querySelector('.page-title')?.textContent || ''), dir *= -1);
          else if (key === 'slug')  sortBy(tr => (tr.querySelector('[data-slug]')?.dataset.slug || ''), dir *= -1);
          else if (key === 'cats')  sortBy(tr => (tr.querySelector('[data-cats]')?.dataset.cats || ''), dir *= -1);
          else if (key === 'pub')   sortBy(tr => (tr.dataset.published || '0'), dir *= -1);
          else if (key === 'home')  sortBy(tr => (tr.dataset.home || '0'), dir *= -1);
        });
      });

      // автопрокрутка к якорю /#page-123
      const hashId = (location.hash || '').replace('#page-','');
      if (hashId) {
        const target = document.getElementById(`page-row-${hashId}`);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  </script>
@endpush

@section('content')
  {{-- ── Шапка страницы: акцентная полоса + бейдж + поиск + действие ── --}}
  <div class="admin-accent-bar mb-0"></div>
  <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
              flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-3 min-w-0">
      <span class="admin-icon-badge"><i class="fa-solid fa-file-lines"></i></span>
      <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Страницы</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Статические и контентные страницы сайта.</p>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 sm:items-center w-full lg:w-auto">
      <form method="GET" action="{{ route('admin.pages.index') }}" id="searchForm"
            class="relative w-full sm:w-80" role="search">
        <input id="searchInput"
               name="q"
               type="text"
               value="{{ $query }}"
               placeholder="🔍 Поиск по заголовку и содержимому…"
               autocomplete="off"
               class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 pr-9 shadow-sm w-full text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
        @if ($query)
          <a href="{{ route('admin.pages.index') }}"
             class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
             title="Сбросить поиск" aria-label="Сбросить поиск">
            <i class="fa-solid fa-xmark"></i>
          </a>
        @endif
      </form>
      <a href="{{ route('admin.pages.create') }}"
         class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 shadow-sm text-sm font-semibold transition shrink-0">
        <i class="fa-solid fa-plus"></i> Новая
      </a>
    </div>
  </div>

  <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 shadow-sm">
    <table id="pagesTable" class="min-w-full bg-white dark:bg-gray-900 text-sm">
      <thead class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
        <tr>
          <th class="px-4 py-2.5 text-left" data-sort="title">📄 Заголовок</th>
          <th class="px-4 py-2.5 text-left hidden md:table-cell" data-sort="slug">🔗 Slug</th>
          <th class="px-4 py-2.5 text-left hidden md:table-cell" data-sort="cats">🏷️ Категории</th>
          <th class="px-4 py-2.5 text-center hidden sm:table-cell" data-sort="pub">Опублик.</th>
          <th class="px-4 py-2.5 text-center hidden sm:table-cell" data-sort="home">На главной</th>
          <th class="px-4 py-2.5 text-center">Превью</th>
          <th class="px-4 py-2.5 text-center">Действия</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($pages as $page)
          {{-- основная строка --}}
          <tr id="page-row-{{ $page->id }}"
              class="page-row hover:bg-indigo-50 dark:hover:bg-gray-800 transition"
              data-published="{{ $page->published ? 1 : 0 }}"
              data-home="{{ $page->show_on_homepage ? 1 : 0 }}">
            <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-white page-title">
              <a href="{{ route('frontend.pages.show', $page->slug) }}" target="_blank"
                 class="text-indigo-600 dark:text-indigo-400 hover:underline" title="Открыть страницу на сайте">
                {{ $page->title }}
              </a>
              <div class="text-xs text-gray-500 dark:text-gray-400 block md:hidden">
                {{ $page->slug }}
              </div>
            </td>

            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 hidden md:table-cell" data-slug="{{ $page->slug }}">
              {{ $page->slug }}
            </td>

            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 hidden md:table-cell"
                data-cats="{{ $page->categories->pluck('title')->join(', ') }}">
              @forelse ($page->categories as $cat)
                <span class="inline-block bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-xs px-2 py-0.5 mr-1 mb-1">
                  🏷️ {{ $cat->title }}
                </span>
              @empty
                <span class="text-xs text-gray-400 italic">—</span>
              @endforelse
            </td>

            {{-- Опубликовано --}}
            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
              @if($page->published)
                <span class="inline-flex items-center justify-center h-6 px-2 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 text-xs gap-1">
                  <i class="fa-solid fa-check text-xs"></i> Да
                </span>
              @else
                <span class="inline-flex items-center justify-center h-6 px-2 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 text-xs">Нет</span>
              @endif
            </td>

            {{-- Домой --}}
            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
              @if($page->show_on_homepage)
                <span class="inline-flex items-center justify-center h-6 px-2 bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 text-xs gap-1">
                  <i class="fa-solid fa-house text-xs"></i> Да
                </span>
              @else
                <span class="inline-flex items-center justify-center h-6 px-2 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 text-xs">Нет</span>
              @endif
            </td>

            {{-- Превью --}}
            <td class="px-4 py-2.5 text-center">
              <button
                onclick="toggleContent({{ $page->id }}, this)"
                class="inline-flex items-center gap-1 border border-gray-300 dark:border-gray-700 px-2.5 h-7 text-xs text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-gray-800">
                <i class="fa-regular fa-eye text-xs"></i> <span>Показать</span>
              </button>
            </td>

            {{-- Действия --}}
            <td class="px-4 py-2.5 text-center">
              <div class="inline-flex items-center gap-2">
                <a href="{{ route('admin.pages.edit', $page) }}"
                   class="text-indigo-600 hover:text-indigo-800"
                   title="Редактировать">
                  <i class="fa-regular fa-pen-to-square"></i>
                </a>

                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST"
                      onsubmit="return confirm('Удалить страницу «{{ $page->title }}»?')"
                      class="inline-block" title="Удалить">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="text-red-600 hover:text-red-700">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>

          {{-- строка с превью (лениво подгружается) --}}
          <tr id="page-content-{{ $page->id }}"
              data-content="{{ strip_tags($page->content) }}"
              class="page-content hidden bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <td colspan="7" class="px-6 py-4">
              <div class="prose max-w-none dark:prose-invert page-content-body text-sm text-gray-700 dark:text-gray-200">
                Загрузка…
              </div>
            </td>
          </tr>
        @empty
          {{-- Пустое состояние: разное для «поиск ничего не нашёл» и «страниц ещё нет» --}}
          <tr>
            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
              @if ($query)
                <i class="fa-regular fa-face-frown text-2xl mb-2 block"></i>
                По запросу «<b>{{ $query }}</b>» ничего не найдено.
                <a href="{{ route('admin.pages.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">Сбросить поиск</a>
              @else
                <i class="fa-regular fa-file-lines text-2xl mb-2 block"></i>
                Пока нет ни одной страницы.
                <a href="{{ route('admin.pages.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">Создать первую</a>
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Пагинация --}}
  <div class="mt-6">
    {{ $pages->links('vendor.pagination.tailwind') }}
  </div>
@endsection
