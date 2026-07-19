@extends('layouts.admin')

@section('content')
@php
  // Грациозные дефолты, чтобы вью не падала, даже если контроллер передал минимум.
  $exists      = $exists      ?? false;
  $size        = $size        ?? null;           // bytes
  $modifiedAt  = $modifiedAt  ?? null;           // unix timestamp
  $indexed     = $indexed     ?? false;          // true => sitemap.xml — это индекс
  $count       = $count       ?? null;           // всего URL (если известно)
  $parts       = $parts       ?? [];             // массив путей к частям на файловой системе (если есть)
  $outputDir   = $outputDir   ?? public_path('sitemaps');
  $baseUrl     = rtrim((string)config('app.url'), '/');
  $publicBase  = $baseUrl . '/sitemaps';
  $sitemapUrl  = route('seo.sitemap.xml');      // публичный URL на sitemap.xml (роут фронта)
  $newsEnabled   = config('seo.features.news_sitemap');
  $imagesEnabled = config('seo.features.images_sitemap');
@endphp

<div class="mb-5">
  <h1 class="text-2xl font-semibold">Sitemap</h1>
  <div class="text-sm text-gray-500 mt-1">
    Управляйте картами сайта: быстрое открытие, пересборка, проверка структуры.
  </div>
</div>

@if(session('status'))
  <div class="p-3 bg-emerald-50 text-emerald-800 rounded border border-emerald-200 mb-4">
    {{ session('status') }}
  </div>
@endif

{{-- Верхняя панель действий --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
  <a href="{{ $sitemapUrl }}" target="_blank" rel="noopener"
     class="px-3 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition"
     title="Открыть sitemap.xml в новой вкладке">
    Открыть sitemap.xml
  </a>

  <button type="button"
          class="px-3 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition"
          title="Скопировать публичный URL sitemap.xml"
          data-url="{{ $sitemapUrl }}"
          onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='URL скопирован'; setTimeout(()=>this.textContent='Скопировать URL',1500); });">
    Скопировать URL
  </button>

  <form method="post" action="{{ route('seo.sitemaps.rebuild') }}" class="inline">
    @csrf
    <button class="px-3 py-2 rounded border border-sky-700 text-sky-700 bg-white hover:bg-sky-50 transition"
            title="Пересобрать sitemap из базы SEO-страниц">
      Пересобрать sitemap
    </button>
  </form>

  @if($newsEnabled && Route::has('seo.sitemap.news'))
    <a href="{{ route('seo.sitemap.news') }}" target="_blank" rel="noopener"
       class="px-3 py-2 rounded border border-amber-600 text-amber-700 bg-white hover:bg-amber-50 transition"
       title="Открыть news-sitemap.xml (если есть свежие материалы)">
      Открыть news-sitemap.xml
    </a>
  @endif

  @if($imagesEnabled && Route::has('seo.sitemap.images'))
    <a href="{{ route('seo.sitemap.images') }}" target="_blank" rel="noopener"
       class="px-3 py-2 rounded border border-purple-600 text-purple-700 bg-white hover:bg-purple-50 transition"
       title="Открыть images-sitemap.xml">
      Открыть images-sitemap.xml
    </a>
  @endif
</div>

{{-- Карточка состояния основного sitemap.xml --}}
<div class="bg-white rounded shadow p-4 mb-4">
  <div class="flex items-start justify-between gap-4">
    <div>
      <div class="text-lg font-medium mb-1">Основной файл</div>
      <div class="text-sm text-gray-600">Путь: <code>{{ $outputDir }}/sitemap.xml</code></div>
      <div class="text-sm text-gray-600">Публичный URL: <a class="text-blue-600 hover:underline" href="{{ $sitemapUrl }}" target="_blank" rel="noopener">{{ $sitemapUrl }}</a></div>
      <div class="mt-2 flex flex-wrap gap-2">
        <span class="inline-block px-2 py-0.5 rounded text-white {{ $exists ? 'bg-green-600':'bg-gray-500' }}">
          {{ $exists ? 'существует' : 'нет файла' }}
        </span>
        @if(!is_null($count))
          <span class="inline-block px-2 py-0.5 rounded bg-blue-100 text-blue-800">
            URL всего: {{ number_format($count, 0, ' ', ' ') }}
          </span>
        @endif
        <span class="inline-block px-2 py-0.5 rounded {{ $indexed ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}"
              title="{{ $indexed ? 'sitemap.xml — индекс, содержит ссылки на части' : 'sitemap.xml — обычный urlset' }}">
          {{ $indexed ? 'index' : 'urlset' }}
        </span>
      </div>
    </div>

    <div class="text-right text-sm text-gray-600">
      <div>Размер: {{ $exists && $size !== null ? number_format($size, 0, ' ', ' ') . ' байт' : '—' }}</div>
      <div>Изменён: {{ $exists && $modifiedAt ? date('d.m.Y H:i:s', $modifiedAt) : '—' }}</div>
    </div>
  </div>
</div>

{{-- Список частей, если это индекс --}}
@if($indexed && is_array($parts) && count($parts))
  <div class="bg-white rounded shadow p-4 mb-4">
    <div class="text-lg font-medium mb-3">Части sitemap (sitemapindex)</div>
    <div class="overflow-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2 pr-3">Файл</th>
            <th class="py-2 pr-3">Публичный URL</th>
            <th class="py-2 pr-3">Размер</th>
            <th class="py-2 pr-3">Изменён</th>
            <th class="py-2 pr-3 text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
        @foreach($parts as $absPath)
          @php
            $file  = basename((string)$absPath);
            $url   = $publicBase . '/' . $file;
            $sz    = @filesize($absPath);
            $mtime = @filemtime($absPath);
          @endphp
          <tr class="border-b align-top hover:bg-gray-50">
            <td class="py-2 pr-3 font-mono">{{ $file }}</td>
            <td class="py-2 pr-3 break-all">
              <a href="{{ $url }}" class="text-blue-600 hover:underline" target="_blank" rel="noopener">{{ $url }}</a>
            </td>
            <td class="py-2 pr-3">{{ $sz ? number_format($sz, 0, ' ', ' ') . ' байт' : '—' }}</td>
            <td class="py-2 pr-3">{{ $mtime ? date('d.m.Y H:i:s', $mtime) : '—' }}</td>
            <td class="py-2 pr-0 text-right">
              <button type="button"
                      class="text-gray-600 hover:underline"
                      data-url="{{ $url }}"
                      onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='Скопировано'; setTimeout(()=>this.textContent='Копировать URL',1500); });">
                Копировать URL
              </button>
              <a href="{{ $url }}" class="ml-2 text-gray-700 hover:underline" target="_blank" rel="noopener">Открыть</a>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

{{-- Быстрые ссылки на доп. карты --}}
<div class="bg-white rounded shadow p-4">
  <div class="text-lg font-medium mb-2">Дополнительные карты</div>
  <div class="text-sm text-gray-600 mb-3">
    Эти карты создаются отдельными билдерами и доступны, только если включены в конфиге.
  </div>
  <div class="flex flex-wrap gap-2">
    @if($newsEnabled && Route::has('seo.sitemap.news'))
      <a href="{{ route('seo.sitemap.news') }}" target="_blank" rel="noopener"
         class="px-3 py-2 rounded border border-amber-600 text-amber-700 bg-white hover:bg-amber-50 transition">
        news-sitemap.xml
      </a>
    @else
      <span class="px-3 py-2 rounded bg-gray-100 text-gray-500" title="Включите seo.features.news_sitemap">news-sitemap.xml выключен</span>
    @endif

    @if($imagesEnabled && Route::has('seo.sitemap.images'))
      <a href="{{ route('seo.sitemap.images') }}" target="_blank" rel="noopener"
         class="px-3 py-2 rounded border border-purple-600 text-purple-700 bg-white hover:bg-purple-50 transition">
        images-sitemap.xml
      </a>
    @else
      <span class="px-3 py-2 rounded bg-gray-100 text-gray-500" title="Включите seo.features.images_sitemap">images-sitemap.xml выключен</span>
    @endif
  </div>
</div>
@endsection
