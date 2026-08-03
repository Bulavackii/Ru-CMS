@extends('layouts.admin')

@section('content')
@php
  $base = rtrim((string)config('app.url'), '/');
  $host = parse_url($base, PHP_URL_HOST) ?: request()->getHost();
  $sitemapUrl = $base . '/sitemap.xml';
@endphp

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-semibold">robots.txt</h1>
    <div class="text-sm text-gray-500">{{ __('admin.seo.robots_hint') }}</div>
  </div>

  <div class="flex items-center gap-2">
    <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener"
       class="inline-flex items-center px-3 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition"
       title="{{ __('admin.seo.open_current') }}">
      {{ __('admin.seo.open_robots') }}
    </a>
    @if(Route::has('seo.sitemap.xml'))
      <a href="{{ route('seo.sitemap.xml') }}" target="_blank" rel="noopener"
         class="inline-flex items-center px-3 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition"
         title="{{ __('admin.seo.open_sitemap') }}">
        {{ __('admin.seo.open_sitemap') }}
      </a>
    @endif
  </div>
</div>

@if (session('status'))
  <div class="mb-4 p-3 rounded border border-emerald-300 bg-emerald-50 text-emerald-800">
    {{ session('status') }}
  </div>
@endif

@if ($errors->any())
  <div class="mb-4 p-3 rounded border border-red-300 bg-red-50 text-red-800">
    <strong>{{ __('admin.seo.check_fields') }}</strong> {{ $errors->first() }}
  </div>
@endif

<form method="post" action="{{ route('seo.robots.update') }}" class="grid lg:grid-cols-3 gap-6">
  @csrf

  {{-- Левая часть: редактор --}}
  <div class="lg:col-span-2 space-y-3">
    <div class="rounded border bg-white">
      <div class="flex items-center justify-between px-3 py-2 border-b bg-gray-50">
        <div class="text-sm font-semibold">{{ __('admin.seo.robots_editor') }}</div>
        <div class="flex items-center gap-2">
          <button type="button" class="px-2 py-1 text-xs rounded border hover:bg-gray-50 js-insert" data-snippet="User-agent: *\nDisallow:\n">{{ __('admin.seo.allow_all') }}</button>
          <button type="button" class="px-2 py-1 text-xs rounded border hover:bg-gray-50 js-insert" data-snippet="User-agent: *\nDisallow: /admin\nDisallow: /storage\n">{{ __('admin.seo.block_admin') }}</button>
          <button type="button" class="px-2 py-1 text-xs rounded border hover:bg-gray-50 js-insert" data-snippet="Host: {{ $host }}\nSitemap: {{ $sitemapUrl }}\n">Host + Sitemap</button>
          <button type="button" class="px-2 py-1 text-xs rounded border hover:bg-gray-50 js-insert" data-snippet="# Пример: запрет параметров\nUser-agent: *\nDisallow: /*?*\n">{{ __('admin.seo.params') }}</button>
        </div>
      </div>

      <textarea name="content" rows="18"
                class="w-full p-3 font-mono text-sm rounded-b outline-none"
                placeholder="User-agent: *&#10;Disallow:&#10;&#10;Host: {{ $host }}&#10;Sitemap: {{ $sitemapUrl }}"
                required>{{ old('content', $content) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">{{ __('admin.save') }}</button>
      <a href="{{ route('seo.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">{{ __('admin.seo.to_seo') }}</a>
    </div>
  </div>

  {{-- Правая часть: подсказки --}}
  <aside class="space-y-3">
    <div class="p-3 rounded border bg-white">
      <div class="font-semibold mb-1">{{ __('admin.seo.hints') }}</div>
      <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
        <li><code>User-agent</code> — для какого бота правило (обычно <code>*</code>).</li>
        <li><code>Disallow</code> — что запрещено. Пустое значение = всё разрешено.</li>
        <li><code>Allow</code> — что явно разрешено при общих запретах.</li>
        <li><code>Host</code> — основной домен (важно для Яндекса): <code>{{ $host }}</code></li>
        <li><code>Sitemap</code> — ссылка на карту сайта: <code>{{ $sitemapUrl }}</code></li>
      </ul>
    </div>
    <div class="p-3 rounded border bg-white">
      <div class="font-semibold mb-1">{{ __('admin.seo.check') }}</div>
      <div class="text-sm text-gray-700 space-y-1">
        <p>{{ __('admin.seo.check_note') }}</p>
        <ul class="list-disc pl-5">
          <li><a class="text-blue-600 hover:underline" href="https://webmaster.yandex.ru/tools/robotstxt/?host={{ $host }}" target="_blank" rel="noopener">{{ __('admin.seo.yandex_tester') }}</a></li>
          <li><a class="text-blue-600 hover:underline" href="https://search.google.com/search-console" target="_blank" rel="noopener">{{ __('admin.seo.gsc_tester') }}</a></li>
        </ul>
      </div>
    </div>
  </aside>
</form>

{{-- Небольшие хелперы вставки --}}
<script>
  (function(){
    const buttons = document.querySelectorAll('.js-insert');
    const ta = document.querySelector('textarea[name="content"]');
    function insertAtCursor(textarea, text) {
      if (!textarea) return;
      const start = textarea.selectionStart ?? textarea.value.length;
      const end   = textarea.selectionEnd ?? textarea.value.length;
      const before = textarea.value.substring(0, start);
      const after  = textarea.value.substring(end);
      textarea.value = before + text + after;
      const pos = start + text.length;
      textarea.focus();
      textarea.setSelectionRange(pos, pos);
    }
    buttons.forEach(btn => {
      btn.addEventListener('click', () => insertAtCursor(ta, btn.dataset.snippet || ''));
    });
  })();
</script>
@endsection
