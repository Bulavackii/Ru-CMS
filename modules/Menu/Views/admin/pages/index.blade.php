@extends('layouts.admin')

@section('title', __('admin.pages.index_title'))

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
        label.textContent = @js(__('admin.common.loading'));
        row.classList.remove('hidden');
        fetch(`/admin/pages/${id}/preview`)
          .then(r => r.text())
          .then(html => {
            row.querySelector('.page-content-body').innerHTML = html;
            row.dataset.loaded = '1';
            label.textContent = @js(__('admin.pages.hide'));
            // плавная прокрутка к открытому превью
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
          })
          .catch(() => (label.textContent = @js(__('admin.pages.show'))));
      } else {
        const hidden = row.classList.toggle('hidden');
        label.textContent = hidden ? @js(__('admin.pages.show')) : @js(__('admin.pages.hide'));
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
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.pages.index_title') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.pages.index_hint') }}</p>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 sm:items-center w-full lg:w-auto">
      <form method="GET" action="{{ route('admin.pages.index') }}" id="searchForm"
            class="relative w-full sm:w-80" role="search">
        {{-- Инлайн-SVG лупа: фиксированный размер и центрирование (не зависит от
             размера иконки темы). --}}
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
             width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
        </svg>
        <input id="searchInput"
               name="q"
               type="text"
               value="{{ $query }}"
               placeholder="{{ __('admin.pages.search') }}"
               autocomplete="off"
               class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-9 py-2 shadow-sm w-full text-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
        @if ($query)
          <a href="{{ route('admin.pages.index') }}"
             class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
             title="{{ __('admin.common.reset_search') }}" aria-label="{{ __('admin.common.reset_search') }}">
            <i class="fa-solid fa-xmark"></i>
          </a>
        @endif
      </form>
      <a href="{{ route('admin.pages.create') }}"
         class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 shadow-sm text-sm font-semibold transition shrink-0">
        <i class="fa-solid fa-plus"></i> {{ __('admin.pages.create_button') }}
      </a>
    </div>
  </div>

  {{-- ── Подсказка ── --}}
  <div class="admin-hint px-4 py-3 mb-5 text-sm">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex items-center gap-2 font-medium">
        <i class="fa-solid fa-lightbulb"></i>
        <span>{{ __('admin.pages.index_note') }}</span>
      </div>
      <div class="flex items-center gap-2 text-xs shrink-0">
        <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
          {{ __('admin.common.total') }} {{ $pages->total() }}
        </span>
      </div>
    </div>
  </div>

  <div class="admin-card overflow-hidden">
   <div class="overflow-x-auto">
    <table id="pagesTable" class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
        <tr>
          <th class="px-4 py-3 text-left font-semibold" data-sort="title">{{ __('admin.news.title') }}</th>
          <th class="px-4 py-3 text-left font-semibold hidden md:table-cell" data-sort="slug">Slug</th>
          <th class="px-4 py-3 text-left font-semibold hidden md:table-cell" data-sort="cats">{{ __('admin.common.categories') }}</th>
          <th class="px-4 py-3 text-center font-semibold hidden sm:table-cell" data-sort="pub">{{ __('admin.common.status') }}</th>
          <th class="px-4 py-3 text-center font-semibold hidden sm:table-cell" data-sort="home">{{ __('admin.common.on_home') }}</th>
          <th class="px-4 py-3 text-center font-semibold">{{ __('admin.common.preview') }}</th>
          <th class="px-4 py-3 text-center font-semibold w-24">{{ __('admin.common.actions') }}</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($pages as $page)
          {{-- основная строка --}}
          <tr id="page-row-{{ $page->id }}"
              class="page-row hover:bg-indigo-50 dark:hover:bg-gray-800 transition"
              data-published="{{ $page->published ? 1 : 0 }}"
              data-home="{{ $page->show_on_homepage ? 1 : 0 }}">
            <td class="px-4 py-3 align-top page-title">
              <a href="{{ route('admin.pages.edit', $page) }}"
                 class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                {{ $page->title }}
              </a>
              <div class="mt-1 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                <span class="font-mono">ID {{ $page->id }}</span>
                <span class="md:hidden font-mono">/{{ $page->slug }}</span>
                <a href="{{ route('frontend.pages.show', $page->slug) }}" target="_blank" rel="noopener"
                   class="hover:text-indigo-600 dark:hover:text-indigo-400 transition" title="{{ __('admin.common.open_on_site') }}">
                  <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
              </div>
            </td>

            <td class="px-4 py-3 align-top hidden md:table-cell" data-slug="{{ $page->slug }}">
              <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                /{{ $page->slug }}
              </span>
            </td>

            <td class="px-4 py-3 align-top hidden md:table-cell"
                data-cats="{{ $page->categories->pluck('title')->join(', ') }}">
              @forelse ($page->categories as $cat)
                <span class="inline-flex items-center px-2 py-0.5 mr-1 mb-1 text-xs font-medium
                             bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                  {{ $cat->title }}
                </span>
              @empty
                <span class="text-gray-400 dark:text-gray-500">—</span>
              @endforelse
            </td>

            {{-- Статус публикации --}}
            <td class="px-4 py-3 align-top text-center hidden sm:table-cell">
              @if($page->published)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                             bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" title="{{ __('admin.common.visible') }}">
                  <i class="fa-solid fa-circle-check"></i> {{ __('admin.common.published') }}
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                             bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300" title="{{ __('admin.common.hidden') }}">
                  <i class="fa-solid fa-clock"></i> {{ __('admin.common.draft') }}
                </span>
              @endif
            </td>

            {{-- На главной --}}
            <td class="px-4 py-3 align-top text-center hidden sm:table-cell">
              @if($page->show_on_homepage)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                             bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                  <i class="fa-solid fa-house"></i> {{ __('admin.common.yes') }}
                </span>
              @else
                <span class="text-gray-400 dark:text-gray-500">—</span>
              @endif
            </td>

            {{-- Превью --}}
            <td class="px-4 py-3 align-top text-center">
              <button type="button"
                onclick="toggleContent({{ $page->id }}, this)"
                class="inline-flex items-center gap-1.5 border border-gray-300 dark:border-gray-700 px-2.5 py-1 text-xs
                       text-indigo-700 dark:text-indigo-300 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-800 transition">
                <i class="fa-regular fa-eye"></i> <span>{{ __('admin.pages.show') }}</span>
              </button>
            </td>

            {{-- Действия --}}
            <td class="px-4 py-3 align-top text-center">
              <div class="inline-flex items-center gap-1.5">
                <a href="{{ route('admin.pages.edit', $page) }}"
                   class="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                   title="{{ __('admin.edit') }}">
                  <i class="fa-solid fa-pen"></i>
                </a>

                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST"
                      onsubmit="return confirm(@js(__('admin.pages.delete_confirm', ['title' => $page->title])))"
                      class="inline-block" title="{{ __('admin.delete') }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit"
                          class="inline-flex items-center justify-center w-8 h-8 bg-red-600 hover:bg-red-700 text-white shadow-sm transition">
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
                {{ __('admin.common.loading') }}
              </div>
            </td>
          </tr>
        @empty
          {{-- Пустое состояние: разное для «поиск ничего не нашёл» и «страниц ещё нет» --}}
          <tr>
            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
              @if ($query)
                <i class="fa-regular fa-face-frown text-2xl mb-2 block"></i>
                {{ __('admin.pages.not_found_1') }}<b>{{ $query }}</b>{{ __('admin.pages.not_found_2') }}
                <a href="{{ route('admin.pages.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('admin.common.reset_search') }}</a>
              @else
                <i class="fa-regular fa-file-lines text-2xl mb-2 block"></i>
                {{ __('admin.pages.empty') }}
                <a href="{{ route('admin.pages.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('admin.pages.create_first') }}</a>
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
   </div>
  </div>

  {{-- Пагинация --}}
  <div class="mt-6">
    {{ $pages->links('vendor.pagination.tailwind') }}
  </div>
@endsection
