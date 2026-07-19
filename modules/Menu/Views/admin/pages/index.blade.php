@extends('layouts.admin')

@section('title', 'Страницы')

@push('scripts')
  {{-- Поиск + превью + фронтовая сортировка --}}
  <script>
    // --- live-поиск по заголовку и содержимому
    function filterPages() {
      const q = (document.getElementById('searchInput').value || '').toLowerCase().trim();
      const rows = document.querySelectorAll('#pagesTable tbody tr.page-row');

      rows.forEach(row => {
        const title = (row.querySelector('.page-title')?.textContent || '').toLowerCase();
        const next = row.nextElementSibling?.classList.contains('page-content') ? row.nextElementSibling : null;
        const content = (next?.dataset?.content || '').toLowerCase();
        const match = !q || title.includes(q) || content.includes(q);
        row.style.display = match ? '' : 'none';
        if (next) next.style.display = match && !next.classList.contains('hidden') ? '' : (match ? 'none' : 'none');
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
          headers.forEach(h => h.classList.remove('text-blue-700','dark:text-blue-300'));
          th.classList.add('text-blue-700','dark:text-blue-300');
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
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📄 Страницы</h1>

    <div class="flex flex-col sm:flex-row gap-3 sm:items-center w-full sm:w-auto">
      <input id="searchInput"
             type="text"
             placeholder="🔍 Поиск по заголовку и содержимому…"
             oninput="filterPages()"
             class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 rounded-md shadow-sm w-full sm:w-80 text-sm" />
      <a href="{{ route('admin.pages.create') }}"
         class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow text-sm font-semibold transition">
        <i class="fa-solid fa-plus"></i> Новая
      </a>
    </div>
  </div>

  <div class="overflow-x-auto rounded-md border border-gray-300 dark:border-gray-700 shadow">
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

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800 [&>tr:nth-child(odd)]:bg-white [&>tr:nth-child(even)]:bg-gray-50 dark:[&>tr:nth-child(odd)]:bg-gray-900 dark:[&>tr:nth-child(even)]:bg-gray-850">
        @foreach ($pages as $page)
          {{-- основная строка --}}
          <tr id="page-row-{{ $page->id }}"
              class="page-row hover:bg-gray-100/60 dark:hover:bg-gray-800/60 transition"
              data-published="{{ $page->published ? 1 : 0 }}"
              data-home="{{ $page->show_on_homepage ? 1 : 0 }}">
            <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-white page-title">
              <a href="{{ route('frontend.pages.show', $page->slug) }}" target="_blank"
                 class="text-blue-600 dark:text-blue-400" title="Открыть страницу на сайте">
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
                <span class="inline-block bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-[11px] rounded-full px-2 py-0.5 mr-1 mb-1">
                  🏷️ {{ $cat->title }}
                </span>
              @empty
                <span class="text-xs text-gray-400 italic">—</span>
              @endforelse
            </td>

            {{-- Опубликовано --}}
            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
              @if($page->published)
                <span class="inline-flex items-center justify-center h-6 px-2 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 text-[11px] gap-1">
                  <i class="fa-solid fa-check text-[10px]"></i> Да
                </span>
              @else
                <span class="inline-flex items-center justify-center h-6 px-2 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 text-[11px]">Нет</span>
              @endif
            </td>

            {{-- Домой --}}
            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
              @if($page->show_on_homepage)
                <span class="inline-flex items-center justify-center h-6 px-2 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200 text-[11px] gap-1">
                  <i class="fa-solid fa-house text-[10px]"></i> Да
                </span>
              @else
                <span class="inline-flex items-center justify-center h-6 px-2 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 text-[11px]">Нет</span>
              @endif
            </td>

            {{-- Превью --}}
            <td class="px-4 py-2.5 text-center">
              <button
                onclick="toggleContent({{ $page->id }}, this)"
                class="inline-flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-700 px-2.5 h-7 text-xs text-blue-700 dark:text-blue-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                <i class="fa-regular fa-eye text-[12px]"></i> <span>Показать</span>
              </button>
            </td>

            {{-- Действия --}}
            <td class="px-4 py-2.5 text-center">
              <div class="inline-flex items-center gap-2">
                <a href="{{ route('admin.pages.edit', $page) }}"
                   class="text-blue-600 hover:text-blue-800"
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
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Пагинация --}}
  <div class="mt-6">
    {{ $pages->links('vendor.pagination.tailwind') }}
  </div>
@endsection
