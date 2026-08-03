@extends('layouts.admin')

@section('title', __('admin.seo.title'))

@section('content')
@php
  use Illuminate\Support\Str;

  $base         = rtrim((string) config('app.url'), '/');
  $qParam       = $q ?? '';
  $perPageParam = $perPage ?? request()->integer('per_page', 10);

  $indexFilter  = request('index', '');
  $followFilter = request('follow', '');
  $metaFilter   = array_filter(explode(',', (string) request('meta')));
  $hasFilters   = $qParam !== '' || $indexFilter !== '' || $followFilter !== '' || $metaFilter !== [];

  // Ссылка «Все» для группы фильтров — сохраняет остальные параметры
  $filterUrl = function (array $overrides) {
      $params = array_merge(request()->except('page'), $overrides);
      return route('seo.pages.index', array_filter($params, fn ($v) => $v !== '' && $v !== null));
  };
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div class="flex items-center gap-3 min-w-0">
    <span class="admin-icon-badge"><i class="fas fa-magnifying-glass-chart"></i></span>
    <div class="min-w-0">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.seo.title') }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('admin.seo.index_hint') }}
      </p>
    </div>
  </div>

  <div class="flex items-center gap-2 flex-shrink-0">
    <form action="{{ route('seo.pages.sync') }}" method="post" class="inline">
      @csrf
      <button class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                     hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition"
              title="{{ __('admin.seo.collect') }}">
        <i class="fas fa-rotate"></i> {{ __('admin.seo.sync') }}
      </button>
    </form>

    <a href="{{ route('seo.pages.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
      <i class="fas fa-plus"></i> {{ __('admin.seo.add_url') }}
    </a>
  </div>
</div>

@if (session('status'))
  <div class="admin-card border-l-4 border-indigo-500 p-4 mb-5 text-sm text-gray-800 dark:text-gray-200">
    <i class="fas fa-circle-info text-indigo-500 mr-1"></i> {{ session('status') }}
  </div>
@endif

@if ($errors->any())
  <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
    <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
      @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif

@if (!empty(session('sync_errors')))
  <div class="admin-card border-l-4 border-yellow-500 p-4 mb-5">
    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-500 mb-1">{{ __('admin.seo.sync_failed') }}</p>
    <ul class="text-xs text-gray-600 dark:text-gray-400 list-disc list-inside">
      @foreach (session('sync_errors') as $line)<li>{{ $line }}</li>@endforeach
    </ul>
  </div>
@endif

{{-- ── Сводка ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
  @foreach([
      [__('admin.seo.stat_urls'), $stats['total'], 'fa-list', __('admin.seo.stat_urls_hint')],
      [__('admin.seo.stat_noindex'), $stats['noindex'], 'fa-eye-slash', 'noindex'],
      [__('admin.seo.stat_locked'), $stats['locked'], 'fa-lock', __('admin.seo.stat_locked_hint')],
      [__('admin.seo.stat_problems'), $stats['problems'], 'fa-triangle-exclamation', __('admin.seo.stat_problems_hint')],
  ] as [$label, $value, $icon, $hint])
    <div class="admin-card p-4">
      <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
        <i class="fas {{ $icon }} text-indigo-500"></i> {{ $label }}
      </div>
      <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $value }}</div>
      <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $hint }}</div>
    </div>
  @endforeach
</div>

{{-- ── Служебные инструменты ── --}}
<div class="admin-card p-4 mb-5">
  <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
    <i class="fas fa-screwdriver-wrench text-indigo-500"></i> {{ __('admin.seo.tools') }}
  </h2>
  <div class="flex flex-wrap items-center gap-2">
    <form action="{{ route('seo.sitemaps.rebuild') }}" method="post" class="inline">
      @csrf
      <button class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                     hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
        <i class="fas fa-sitemap"></i> {{ __('admin.seo.rebuild_sitemap') }}
      </button>
    </form>

    <a href="{{ route('seo.sitemaps.index') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
      <i class="fas fa-gauge"></i> {{ __('admin.seo.sitemap_state') }}
    </a>

    <a href="{{ route('seo.sitemap.xml') }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
      <i class="fas fa-file-code"></i> sitemap.xml
    </a>

    <a href="{{ route('seo.robots.edit') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
      <i class="fas fa-robot"></i> robots.txt
    </a>

    <a href="{{ route('seo.redirects.index') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
      <i class="fas fa-signs-post"></i> {{ __('admin.seo.redirects') }}
    </a>

    <span class="mx-1 h-6 w-px bg-gray-200 dark:bg-gray-700"></span>

    <a href="https://webmaster.yandex.ru/" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 transition">
      <i class="fas fa-arrow-up-right-from-square text-xs"></i> {{ __('admin.seo.webmaster') }}
    </a>
    <a href="https://search.google.com/search-console" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 transition">
      <i class="fas fa-arrow-up-right-from-square text-xs"></i> Google Search Console
    </a>
  </div>
</div>

{{-- ── Фильтры (одной карточкой, работают на сервере) ── --}}
<form method="GET" action="{{ route('seo.pages.index') }}" class="admin-card p-5 mb-5">
  <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
    <i class="fas fa-filter text-indigo-500"></i> {{ __('admin.common.filters') }}
  </h2>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
    <div class="md:col-span-3">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.header.search') }}</label>
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
             width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
        </svg>
        <input type="text" name="q" value="{{ $qParam }}" placeholder="{{ __('admin.seo.search_ph') }}"
               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.seo.per_page') }}</label>
      <select name="per_page"
              class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        @foreach([10, 25, 50, 100] as $n)
          <option value="{{ $n }}" @selected((int) $perPageParam === $n)>{{ $n }}</option>
        @endforeach
      </select>
    </div>
  </div>

  {{-- Значения фильтров-чипов переносим в форму, чтобы поиск их не сбрасывал --}}
  <input type="hidden" name="index" value="{{ $indexFilter }}">
  <input type="hidden" name="follow" value="{{ $followFilter }}">
  <input type="hidden" name="meta" value="{{ implode(',', $metaFilter) }}">

  <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('admin.seo.indexing_f') }}</span>
    <span class="flex items-center gap-1">
      @foreach(['' => __('admin.common.all'), '1' => 'index', '0' => 'noindex'] as $value => $label)
        <a href="{{ $filterUrl(['index' => $value]) }}"
           class="seo-chip {{ (string) $indexFilter === (string) $value ? 'seo-chip--active' : '' }}">{{ $label }}</a>
      @endforeach
    </span>

    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('admin.seo.links_f') }}</span>
    <span class="flex items-center gap-1">
      @foreach(['' => __('admin.common.all'), '1' => 'follow', '0' => 'nofollow'] as $value => $label)
        <a href="{{ $filterUrl(['follow' => $value]) }}"
           class="seo-chip {{ (string) $followFilter === (string) $value ? 'seo-chip--active' : '' }}">{{ $label }}</a>
      @endforeach
    </span>

    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('admin.seo.meta_f') }}</span>
    <span class="flex items-center gap-1 flex-wrap">
      @foreach(['canonical' => 'canonical', 'og' => 'og', 'jsonld' => 'json-ld'] as $flag => $label)
        @php
          $next = in_array($flag, $metaFilter, true)
              ? array_values(array_diff($metaFilter, [$flag]))
              : array_merge($metaFilter, [$flag]);
        @endphp
        <a href="{{ $filterUrl(['meta' => implode(',', $next)]) }}"
           class="seo-chip {{ in_array($flag, $metaFilter, true) ? 'seo-chip--active' : '' }}">{{ $label }}</a>
      @endforeach
    </span>
  </div>

  <div class="mt-4 flex flex-wrap items-center gap-2">
    <button type="submit"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
      <i class="fas fa-magnifying-glass"></i> {{ __('admin.common.apply') }}
    </button>
    @if($hasFilters)
      <a href="{{ route('seo.pages.index') }}"
         class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
        <i class="fas fa-rotate-left"></i> {{ __('admin.users.reset') }}
      </a>
    @endif
  </div>
</form>

@if($items->isEmpty())
  {{-- ── Пустое состояние ── --}}
  <div class="admin-card p-10 text-center">
    <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-magnifying-glass-chart"></i></span>

    @if($hasFilters)
      <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.seo.nothing_found') }}</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('admin.seo.no_match') }}</p>
      <a href="{{ route('seo.pages.index') }}"
         class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
        <i class="fas fa-rotate-left"></i> {{ __('admin.seo.reset_filters') }}
      </a>
    @else
      <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.seo.no_urls') }}</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-5">
        {{ __('admin.seo.about') }}
      </p>

      <div class="text-left max-w-2xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-2 mb-6">
        @foreach([
            ['fa-rotate', __('admin.seo.card_sync'), __('admin.seo.card_sync_hint')],
            ['fa-pen', __('admin.seo.card_manual'), __('admin.seo.card_manual_hint')],
            ['fa-sitemap', 'sitemap.xml', __('admin.seo.card_sitemap_hint')],
        ] as [$icon, $title, $text])
          <div class="border border-gray-200 dark:border-gray-700 p-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
              <i class="fas {{ $icon }} text-indigo-500"></i> {{ $title }}
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $text }}</p>
          </div>
        @endforeach
      </div>

      <form action="{{ route('seo.pages.sync') }}" method="post" class="inline">
        @csrf
        <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
          <i class="fas fa-rotate"></i> {{ __('admin.seo.sync_now') }}
        </button>
      </form>
    @endif
  </div>
@else
  {{-- ── Массовые действия ──
       Форма оборачивает только панель: чекбоксы в таблице привязаны к ней
       атрибутом form, иначе они оказались бы внутри форм строк (вложенные
       формы HTML запрещает). --}}
  <form method="POST" action="{{ route('seo.pages.bulk') }}" id="seoBulkForm" class="admin-card p-4 mb-4">
    @csrf
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('admin.seo.with_selected') }}</span>
      <select name="action"
              class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <option value="">{{ __('admin.seo.choose_action') }}</option>
        <option value="index">{{ __('admin.seo.allow_index') }}</option>
        <option value="noindex">{{ __('admin.seo.block_index') }}</option>
        <option value="lock">{{ __('admin.seo.lock_sync') }}</option>
        <option value="unlock">{{ __('admin.seo.unlock') }}</option>
        <option value="sync">{{ __('admin.seo.resync') }}</option>
        <option value="delete">{{ __('admin.delete') }}</option>
      </select>
      <button type="submit"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition"
              onclick="return confirm(@js(__('admin.seo.bulk_confirm')))">
        <i class="fas fa-bolt"></i> {{ __('admin.common.apply') }}
      </button>
      <span id="seoBulkCounter" class="text-sm text-gray-500 dark:text-gray-400"></span>
    </div>
  </form>

  {{-- ── Таблица ── --}}
  <div class="admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
        <tr>
          <th class="px-4 py-3 text-center w-10"><input type="checkbox" id="seoSelectAll" class="border-gray-400"></th>
          <th class="px-4 py-3 text-left font-semibold">{{ __('admin.seo.url') }}</th>
          <th class="px-4 py-3 text-left font-semibold">{{ __('admin.seo.title_desc') }}</th>
          <th class="px-4 py-3 text-left font-semibold">{{ __('admin.seo.indexing') }}</th>
          <th class="px-4 py-3 text-left font-semibold">{{ __('admin.seo.meta') }}</th>
          <th class="px-4 py-3 text-center font-semibold">{{ __('admin.common.actions') }}</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
      @foreach($items as $p)
        @php
          $viewUrl = !empty($p->canonical) ? $p->canonical : ($base . '/' . ltrim((string) $p->slug, '/'));
          $manualCount  = is_array($p->manual_fields ?? null) ? count($p->manual_fields) : 0;
          $hasCanonical = !empty($p->canonical);
          $hasOg        = !empty($p->og);
          $hasJsonld    = !empty($p->jsonld);
          $descLength   = mb_strlen((string) $p->description);
          $titleLength  = mb_strlen((string) $p->title);
        @endphp
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
          <td class="px-4 py-3 text-center align-top">
            <input type="checkbox" name="selected[]" value="{{ $p->id }}" form="seoBulkForm"
                   class="seo-bulk-checkbox border-gray-400">
          </td>

          <td class="px-4 py-3 align-top">
            <div class="font-mono text-xs text-gray-900 dark:text-white break-all">{{ Str::limit($p->slug, 90) }}</div>
            <div class="mt-1 flex flex-wrap gap-1">
              @if(!empty($p->source_type))
                <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5"
                      title="{{ __('admin.seo.from_sync') }}">
                  {{ $p->source_type === 'news' ? 'новость' : 'страница' }}@if($p->source_id) #{{ $p->source_id }}@endif
                </span>
              @endif
              @if($manualCount > 0)
                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5"
                      title="{{ __('admin.seo.manual_fields') }}">
                  правлено: {{ $manualCount }}
                </span>
              @endif
              @if(!empty($p->locked))
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5"
                      title="{{ __('admin.seo.no_sync') }}">{{ __('admin.seo.locked') }}</span>
              @endif
              @if($p->updated_at)
                <span class="text-xs text-gray-400">{{ $p->updated_at->format('d.m.Y H:i') }}</span>
              @endif
            </div>
          </td>

          <td class="px-4 py-3 align-top">
            <div class="text-gray-900 dark:text-white break-words">
              {{ $p->title ?: '—' }}
              @if($titleLength > 70)
                <span class="text-xs text-yellow-700" title="{{ __('admin.seo.long_title') }}">{{ $titleLength }} симв.</span>
              @endif
            </div>
            @if(!empty($p->h1))
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">H1: {{ Str::limit($p->h1, 90) }}</div>
            @endif
            <div class="text-xs mt-1 {{ $descLength === 0 ? 'text-yellow-700' : 'text-gray-500 dark:text-gray-400' }}">
              @if($descLength === 0)
                <i class="fas fa-triangle-exclamation"></i> описание не заполнено
              @else
                {{ Str::limit($p->description, 90) }}
                @if($descLength > 160)
                  <span class="text-yellow-700" title="{{ __('admin.seo.long_desc') }}">{{ $descLength }} симв.</span>
                @endif
              @endif
            </div>
          </td>

          <td class="px-4 py-3 align-top whitespace-nowrap">
            <span class="inline-block text-xs px-2 py-0.5 text-white {{ $p->robots_index ? 'bg-green-600' : 'bg-gray-500' }}">
              {{ $p->robots_index ? 'index' : 'noindex' }}
            </span>
            <span class="inline-block text-xs px-2 py-0.5 text-white {{ $p->robots_follow ? 'bg-green-600' : 'bg-gray-500' }}">
              {{ $p->robots_follow ? 'follow' : 'nofollow' }}
            </span>
          </td>

          <td class="px-4 py-3 align-top">
            <div class="flex flex-wrap gap-1">
              @if($hasCanonical)<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5">canonical</span>@endif
              @if($hasOg)<span class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5">og</span>@endif
              @if($hasJsonld)<span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5">json-ld</span>@endif
              @if(!$hasCanonical && !$hasOg && !$hasJsonld)<span class="text-xs text-gray-400">—</span>@endif
            </div>
          </td>

          <td class="px-4 py-3 align-top text-center whitespace-nowrap">
            <a href="{{ route('seo.pages.edit', $p->id) }}"
               class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                      text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
               title="{{ __('admin.edit') }}"><i class="fas fa-pen"></i></a>

            <a href="{{ $viewUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                      text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
               title="{{ __('admin.seo.open_on_site') }}"><i class="fas fa-arrow-up-right-from-square"></i></a>

            <button type="button" data-url="{{ $viewUrl }}"
                    class="seo-copy inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                           text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                    title="{{ __('admin.seo.copy_url') }}"><i class="fas fa-copy"></i></button>

            <button type="submit" form="seo-refresh-{{ $p->id }}"
                    class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                           text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                    title="{{ __('admin.seo.resync_one') }}"><i class="fas fa-rotate"></i></button>

            <button type="submit" form="seo-delete-{{ $p->id }}"
                    class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                           text-gray-600 dark:text-gray-300 hover:border-red-400 hover:text-red-600 transition"
                    title="{{ __('admin.delete') }}"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $items->links() }}
  </div>

  {{-- Формы действий строк — вне таблицы и вне формы массовых действий --}}
  @foreach($items as $p)
    <form id="seo-refresh-{{ $p->id }}" action="{{ route('seo.pages.refresh', $p->id) }}" method="post" class="hidden">@csrf</form>
    <form id="seo-delete-{{ $p->id }}" action="{{ route('seo.pages.destroy', $p->id) }}" method="post" class="hidden"
          onsubmit="return confirm('Удалить SEO-запись для {{ addslashes($p->slug) }}? Синхронизация может создать её снова.');">
      @csrf @method('DELETE')
    </form>
  @endforeach
@endif
@endsection

@push('styles')
<style>
  .seo-chip{ display:inline-flex; align-items:center; padding:.25rem .6rem; font-size:.75rem; line-height:1;
      border:1px solid #d1d5db; background:#fff; color:#374151; text-decoration:none; transition:all .15s; }
  .seo-chip:hover{ border-color:#6366f1; color:#4338ca; }
  .seo-chip--active{ background:#4f46e5; border-color:#4f46e5; color:#fff; }
  .seo-chip--active:hover{ background:#4338ca; color:#fff; }
</style>
@endpush

@push('scripts')
<script>
  (function () {
    const selectAll = document.getElementById('seoSelectAll');
    const boxes = () => document.querySelectorAll('.seo-bulk-checkbox');
    const counter = document.getElementById('seoBulkCounter');

    const refresh = () => {
      const checked = document.querySelectorAll('.seo-bulk-checkbox:checked').length;
      if (counter) counter.textContent = checked ? `отмечено: ${checked}` : '';
      if (selectAll) selectAll.checked = checked > 0 && checked === boxes().length;
    };

    selectAll?.addEventListener('change', function () {
      boxes().forEach(cb => { cb.checked = this.checked; });
      refresh();
    });
    boxes().forEach(cb => cb.addEventListener('change', refresh));
    refresh();

    document.querySelectorAll('.seo-copy').forEach(btn => {
      btn.addEventListener('click', function () {
        navigator.clipboard?.writeText(this.dataset.url).then(() => {
          const icon = this.querySelector('i');
          const original = icon.className;
          icon.className = 'fas fa-check';
          setTimeout(() => { icon.className = original; }, 1200);
        });
      });
    });
  })();
</script>
@endpush
