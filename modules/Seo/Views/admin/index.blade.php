@extends('layouts.admin')

@section('content')
@php
  use Illuminate\Support\Str;
  $base = rtrim((string)config('app.url'), '/');
  // Текущие параметры для ссылок/форм
  $qParam       = $q ?? '';
  $perPageParam = $perPage ?? request()->integer('per_page', 10);

  // ссылки в кабинеты
  $hostOnly   = parse_url($base, PHP_URL_HOST) ?: $base;
  $yandexUrl  = 'https://webmaster.yandex.ru/site/?host=' . $hostOnly;
  // Google ждёт resource_id с протоколом и хвостовым / для корня
  $gscResource = rtrim($base, '/') . '/';
  $googleUrl = 'https://search.google.com/search-console?resource_id=' . urlencode($gscResource);
@endphp

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-semibold">SEO — страницы</h1>
    <div class="text-sm text-gray-500">
      Всего: {{ number_format($items->total(), 0, ',', ' ') }}
      @if(!empty($qParam)) • Поиск: <code>{{ $qParam }}</code>@endif
    </div>
  </div>

  <div class="flex items-center gap-2">
    {{-- Пересобрать sitemap (быстро) --}}
    @if(Route::has('seo.sitemaps.rebuild'))
      <form method="post" action="{{ route('seo.sitemaps.rebuild') }}" class="inline">
        @csrf
        <button
          class="inline-flex items-center px-3 py-2 rounded border border-sky-700 text-sky-700 bg-white hover:bg-sky-50 transition"
          title="Пересобрать sitemap.xml (и части, если их много)"
        >
          Пересобрать sitemap
        </button>
      </form>
    @endif

    {{-- Открыть sitemap.xml --}}
    @if(Route::has('seo.sitemap.xml'))
      <a href="{{ route('seo.sitemap.xml') }}" target="_blank" rel="noopener"
         class="inline-flex items-center px-3 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition"
         title="Открыть sitemap.xml в новой вкладке">
        Открыть sitemap.xml
      </a>
    @endif

    {{-- Я.Мастер (Yandex Webmaster) --}}
    <a href="{{ $yandexUrl }}" target="_blank" rel="noopener noreferrer"
       class="inline-flex items-center gap-1.5 px-3 py-2 rounded border border-amber-700 text-amber-700 bg-white hover:bg-amber-50 transition"
       title="Открыть Яндекс.Вебмастер для {{ $hostOnly }}">
      {{-- Yandex icon --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12.6 2c-3.6 0-6.1 2.3-6.1 6.2v13.8h3.4v-7.4h1.2l3.7 7.4h3.8l-4.4-8.2c2.3-.9 3.5-2.9 3.5-5.6C17.7 4 15.6 2 12.6 2Zm0 3c1.7 0 2.7 1.2 2.7 3.1s-1 3.1-2.7 3.1h-2.7V8.2c0-1.9 1-3.2 2.7-3.2Z"/>
      </svg>
      Я-мастер
    </a>

    {{-- G.Мастер (Google Search Console) --}}
    <a href="{{ $googleUrl }}" target="_blank" rel="noopener noreferrer"
       class="inline-flex items-center gap-1.5 px-3 py-2 rounded border border-emerald-700 text-emerald-700 bg-white hover:bg-emerald-50 transition"
       title="Открыть Google Search Console для {{ $gscResource }}">
      {{-- Google magnifier-ish icon --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M10 2a8 8 0 1 1 0 16 8 8 0 0 1 0-16Zm0 3a5 5 0 1 0 .001 10.001A5 5 0 0 0 10 5Zm9.7 14.3-3.1-3.1a1 1 0 1 0-1.4 1.4l3.1 3.1a1 1 0 0 0 1.4-1.4Z"/>
      </svg>
      G-мастер
    </a>

    {{-- robots.txt редактор --}}
    @if(Route::has('seo.robots.edit'))
      <a href="{{ route('seo.robots.edit') }}"
         class="inline-flex items-center gap-1.5 px-3 py-2 rounded border border-slate-700 text-slate-700 bg-white hover:bg-slate-50 transition"
         title="Редактировать robots.txt">
        {{-- file-text icon --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V7h3.5L14 3.5ZM8.5 11a1 1 0 1 0 0 2H15a1 1 0 1 0 0-2H8.5Zm0 4a1 1 0 1 0 0 2H15a1 1 0 1 0 0-2H8.5Z"/>
        </svg>
        robots.txt
      </a>
    @endif

    {{-- Синхронизация всего контента (страницы + новости) --}}
    <form method="post" action="{{ route('seo.pages.sync') }}" class="inline">
      @csrf
      <button
        class="inline-flex items-center px-3 py-2 rounded border border-amber-700 text-amber-700 bg-white hover:bg-amber-50 transition"
        title="Импортировать или обновить SEO для всех новостей и страниц (ручные поля не перезатираются)"
      >
        Синхронизировать
      </button>
    </form>

    {{-- Создать вручную --}}
    <a href="{{ route('seo.pages.create') }}"
       class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition"
       title="Создать SEO-запись вручную">
      Создать
    </a>
  </div>
</div>

{{-- флэш-статус --}}
@if(session('status'))
  <div class="mb-3 p-3 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
    {{ session('status') }}
  </div>
@endif

{{-- подробности ошибок синхронизации (если были) --}}
@if(session('sync_errors') && is_array(session('sync_errors')) && count(session('sync_errors')))
  <div class="mb-3 p-3 rounded bg-red-50 text-red-800 border border-red-200">
    <div class="font-semibold mb-1">Ошибки синхронизации:</div>
    <ul class="list-disc pl-5 space-y-1">
      @foreach(session('sync_errors') as $msg)
        <li>{{ $msg }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- Поиск + пер-страничность --}}
<form method="get" action="{{ route('seo.pages.index') }}" class="mb-3 flex flex-col md:flex-row md:items-center gap-2">
  <input type="text" name="q" value="{{ $qParam }}" placeholder="Искать по slug / title"
         class="border p-2 rounded w-full md:flex-1" />
  <select name="per_page" class="border p-2 rounded md:w-44">
    @foreach([10,25,50,100] as $opt)
      <option value="{{ $opt }}" {{ (int)$perPageParam === $opt ? 'selected':'' }}>{{ $opt }} на странице</option>
    @endforeach
  </select>
  <button class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Искать</button>
  @if(!empty($qParam))
    <a href="{{ route('seo.pages.index') }}" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded">Сброс</a>
  @endif
</form>

{{-- 🔎 Фильтры по статусу и мета --}}
<div class="mb-3 flex flex-col lg:flex-row lg:items-center gap-2">
  <div class="text-xs text-gray-600">Статус:</div>
  <div class="flex items-center gap-1">
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-index=""
            title="Все" onclick="setFilter('index','')">Все</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-index="1"
            title="Только index" onclick="setFilter('index','1')">index</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-index="0"
            title="Только noindex" onclick="setFilter('index','0')">noindex</button>
  </div>

  <div class="text-xs text-gray-600 lg:ml-4">Follow:</div>
  <div class="flex items-center gap-1">
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-follow=""
            title="Все" onclick="setFilter('follow','')">Все</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-follow="1"
            title="Только follow" onclick="setFilter('follow','1')">follow</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-follow="0"
            title="Только nofollow" onclick="setFilter('follow','0')">nofollow</button>
  </div>

  <div class="text-xs text-gray-600 lg:ml-4">Мета:</div>
  <div class="flex items-center gap-1 flex-wrap">
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-meta=""
            title="Любые" onclick="setFilter('meta','')">Все</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-meta="canonical"
            title="Есть canonical" onclick="toggleMeta('canonical')">canonical</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-meta="og"
            title="Есть OpenGraph/Twitter" onclick="toggleMeta('og')">og</button>
    <button type="button" class="px-2 py-1 rounded border text-xs" data-filter-meta="jsonld"
            title="Есть JSON-LD" onclick="toggleMeta('jsonld')">json-ld</button>
    <button type="button" class="px-2 py-1 rounded border text-xs bg-gray-100"
            title="Сбросить мета-фильтры" onclick="setFilter('meta','')">Сбросить мета</button>
  </div>
</div>

{{-- Легенда значков --}}
<div class="mb-2 text-xs text-gray-500 space-x-2">
  <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 rounded">canonical</span>
  <span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-800 rounded">og</span>
  <span class="inline-block px-2 py-0.5 bg-amber-100 text-amber-800 rounded">json-ld</span>
  <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 rounded">source: news/page</span>
  <span class="inline-block px-2 py-0.5 bg-rose-100 text-rose-700 rounded">manual N</span>
  <span class="inline-block px-2 py-0.5 bg-slate-200 text-slate-700 rounded">locked</span>
</div>

<table class="w-full bg-white rounded shadow text-sm">
  <thead>
    <tr class="text-left">
      <th class="p-3 w-1/3">Slug</th>
      <th class="p-3">Title</th>
      <th class="p-3">Статус</th>
      <th class="p-3">Мета</th>
      <th class="p-3 w-64 text-right">Действия</th>
    </tr>
  </thead>
  <tbody id="seoTableBody">
  @forelse($items as $p)
    @php
      $viewUrl = !empty($p->canonical)
        ? $p->canonical
        : ($base . '/' . ltrim((string)$p->slug, '/'));
      $manualCount = is_array($p->manual_fields ?? null) ? count($p->manual_fields) : 0;
      $hasCanonical = !empty($p->canonical);
      $hasOg = !empty($p->og);
      $hasJsonld = !empty($p->jsonld);
    @endphp
    <tr class="border-t align-top hover:bg-gray-50"
        data-index="{{ $p->robots_index ? 1 : 0 }}"
        data-follow="{{ $p->robots_follow ? 1 : 0 }}"
        data-canonical="{{ $hasCanonical ? 1 : 0 }}"
        data-og="{{ $hasOg ? 1 : 0 }}"
        data-jsonld="{{ $hasJsonld ? 1 : 0 }}">
      <td class="p-3 font-mono break-all">
        {{ Str::limit($p->slug, 120) }}
        <div class="mt-1 space-x-1">
          @if(!empty($p->source_type))
            <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
              source: {{ $p->source_type }}@if($p->source_id)#{{ $p->source_id }}@endif
            </span>
          @endif
          @if($manualCount > 0)
            <span class="inline-block px-2 py-0.5 bg-rose-100 text-rose-700 rounded text-xs" title="Поля, правленные вручную, не затираются синхронизацией">
              manual {{ $manualCount }}
            </span>
          @endif
          @if(!empty($p->locked))
            <span class="inline-block px-2 py-0.5 bg-slate-200 text-slate-700 rounded text-xs" title="Запись заблокирована от автосоздания/пересоздания">
              locked
            </span>
          @endif
          @if($p->updated_at)
            <span class="inline-block px-2 py-0.5 bg-gray-50 text-gray-500 rounded text-xs" title="Обновлено">
              {{ $p->updated_at->format('d.m.Y H:i') }}
            </span>
          @endif
        </div>
      </td>

      <td class="p-3">
        {{ Str::limit($p->title ?? '—', 120) }}
        @if(!empty($p->h1))
          <div class="text-xs text-gray-500 mt-1">H1: {{ Str::limit($p->h1, 120) }}</div>
        @endif
      </td>

      <td class="p-3 whitespace-nowrap">
        <span class="inline-block px-2 py-0.5 rounded text-white {{ $p->robots_index ? 'bg-green-600':'bg-gray-500' }}"
              title="{{ $p->robots_index ? 'Страница индексируется' : 'Страница исключена из индекса' }}">
          {{ $p->robots_index ? 'index' : 'noindex' }}
        </span>
        <span class="inline-block px-2 py-0.5 rounded text-white {{ $p->robots_follow ? 'bg-green-600':'bg-gray-500' }}"
              title="{{ $p->robots_follow ? 'По ссылкам переходить' : 'По ссылкам не переходить' }}">
          {{ $p->robots_follow ? 'follow' : 'nofollow' }}
        </span>
      </td>

      <td class="p-3">
        @if($hasCanonical)
          <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs mr-1">canonical</span>
        @endif
        @if($hasOg)
          <span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-800 rounded text-xs mr-1">og</span>
        @endif
        @if($hasJsonld)
          <span class="inline-block px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-xs">json-ld</span>
        @endif
      </td>

      <td class="p-3 text-right">
        <div class="flex items-center justify-end gap-2 flex-wrap">
          <a href="{{ route('seo.pages.edit', $p->id) }}"
             class="text-blue-600 hover:underline" title="Редактировать SEO">
            Редактировать
          </a>

          <a href="{{ $viewUrl }}" target="_blank" rel="noopener"
             class="text-gray-700 hover:underline" title="Открыть страницу на сайте">
            Просмотр
          </a>

          <button type="button"
                  class="text-gray-600 hover:underline"
                  title="Скопировать URL страницы"
                  data-url="{{ $viewUrl }}"
                  onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='Скопировано'; setTimeout(()=>this.textContent='Копировать URL',1500); });">
            Копировать URL
          </button>

          {{-- точечная пересинхронизация одной записи --}}
          <form action="{{ route('seo.pages.refresh', $p->id) }}" method="post" class="inline">
            @csrf
            <button class="text-gray-700 hover:underline" title="Пересинхронизировать только эту запись">
              Обновить
            </button>
          </form>

          {{-- удаление --}}
          <form action="{{ route('seo.pages.destroy', $p->id) }}" method="post" class="inline"
                onsubmit="return confirm('Удалить SEO-запись? Если включён автосинк, источник может создать её снова.');">
            @csrf @method('DELETE')
            <button class="text-red-600 hover:underline">Удалить</button>
          </form>
        </div>
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="5" class="p-6 text-center text-gray-500">
        Записей пока нет. <a class="text-blue-600 hover:underline" href="{{ route('seo.pages.create') }}">Создать первую</a>.
      </td>
    </tr>
  @endforelse
  </tbody>
</table>

<div class="mt-4">
  {{-- сохраняем q и per_page при пагинации --}}
  {{ $items->appends(['q' => $qParam, 'per_page' => $perPageParam])->links() }}
</div>

{{-- ===== JS фильтрации (клиентская, быстрая) ===== --}}
<script>
  const state = {
    index: new URLSearchParams(location.search).get('index') ?? '',
    follow: new URLSearchParams(location.search).get('follow') ?? '',
    meta: (new URLSearchParams(location.search).get('meta') ?? '').split(',').filter(Boolean), // ['canonical','og',...]
  };

  function setFilter(key, val) {
    if (key === 'meta') {
      state.meta = val ? val.split(',').filter(Boolean) : [];
    } else {
      state[key] = val;
    }
    updateUI();
    applyFilter();
    syncUrl();
  }

  function toggleMeta(name) {
    const i = state.meta.indexOf(name);
    if (i === -1) state.meta.push(name); else state.meta.splice(i,1);
    updateUI();
    applyFilter();
    syncUrl();
  }

  function updateUI() {
    // highlight buttons
    document.querySelectorAll('[data-filter-index]').forEach(b=>{
      const v = b.getAttribute('data-filter-index');
      b.classList.toggle('bg-black', String(state.index)===String(v));
      b.classList.toggle('text-white', String(state.index)===String(v));
    });
    document.querySelectorAll('[data-filter-follow]').forEach(b=>{
      const v = b.getAttribute('data-filter-follow');
      b.classList.toggle('bg-black', String(state.follow)===String(v));
      b.classList.toggle('text-white', String(state.follow)===String(v));
    });
    document.querySelectorAll('[data-filter-meta]').forEach(b=>{
      const v = b.getAttribute('data-filter-meta');
      if (!v) {
        const active = state.meta.length === 0;
        b.classList.toggle('bg-black', active);
        b.classList.toggle('text-white', active);
      } else {
        const active = state.meta.includes(v);
        b.classList.toggle('bg-black', active);
        b.classList.toggle('text-white', active);
      }
    });
  }

  function applyFilter() {
    const rows = document.querySelectorAll('#seoTableBody tr');
    rows.forEach(tr=>{
      const okIndex = state.index === '' || tr.dataset.index === state.index;
      const okFollow = state.follow === '' || tr.dataset.follow === state.follow;

      // meta: если массив пуст — пропускаем; иначе требуем И всех выбранных флагов
      let okMeta = true;
      if (state.meta.length) {
        okMeta = state.meta.every(flag => tr.dataset[flag] === '1');
      }

      tr.style.display = (okIndex && okFollow && okMeta) ? '' : 'none';
    });
  }

  function syncUrl() {
    const qs = new URLSearchParams(location.search);
    if (state.index !== '') qs.set('index', state.index); else qs.delete('index');
    if (state.follow !== '') qs.set('follow', state.follow); else qs.delete('follow');
    if (state.meta.length) qs.set('meta', state.meta.join(',')); else qs.delete('meta');
    history.replaceState(null, '', location.pathname + (qs.toString() ? '?' + qs.toString() : ''));
  }

  // init once DOM loaded
  document.addEventListener('DOMContentLoaded', ()=>{
    updateUI();
    applyFilter();
  });
</script>
@endsection
