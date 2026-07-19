@extends('layouts.admin')

@section('content')
@php
  use Illuminate\Support\Str;
  $base = rtrim((string)config('app.url'), '/');
  $qParam       = $q ?? '';
  $perPageParam = request()->integer('per_page', 25);
@endphp

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-semibold">Редиректы</h1>
    <div class="text-sm text-gray-500">
      Всего: {{ number_format($items->total(), 0, ',', ' ') }}
      @if(!empty($qParam)) • Поиск: <code>{{ $qParam }}</code>@endif
    </div>
  </div>

  <a href="{{ route('seo.redirects.create') }}"
     class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition"
     title="Добавить новое правило редиректа">
    Добавить
  </a>
</div>

@if(session('status'))
  <div class="mb-3 p-3 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
    {{ session('status') }}
  </div>
@endif

{{-- Поиск + пер-страничность --}}
<form method="get" action="{{ route('seo.redirects.index') }}" class="mb-3 flex flex-col md:flex-row md:items-center gap-2">
  <input type="text" name="q" value="{{ $qParam }}" placeholder="Поиск по FROM/TO (поддерживает часть строки)"
         class="border p-2 rounded w-full md:flex-1" />
  <select name="per_page" class="border p-2 rounded md:w-44">
    @foreach([10,25,50,100] as $opt)
      <option value="{{ $opt }}" {{ (int)$perPageParam === $opt ? 'selected':'' }}>{{ $opt }} на странице</option>
    @endforeach
  </select>
  <button class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Искать</button>
  @if(!empty($qParam))
    <a href="{{ route('seo.redirects.index') }}" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded">Сброс</a>
  @endif
</form>

{{-- Легенда --}}
<div class="mb-2 text-xs text-gray-500 space-x-2">
  <span class="inline-block px-2 py-0.5 bg-green-100 text-green-800 rounded">301</span>
  <span class="inline-block px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded">302</span>
  <span class="inline-block px-2 py-0.5 bg-gray-200 text-gray-700 rounded">410 (Gone)</span>
  <span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-800 rounded">RegExp</span>
  <span class="inline-block px-2 py-0.5 bg-sky-100 text-sky-800 rounded">Проверить</span>
</div>

<table class="w-full bg-white rounded shadow text-sm">
  <thead>
    <tr class="text-left">
      <th class="p-3 w-1/3">From</th>
      <th class="p-3 w-1/3">To</th>
      <th class="p-3">Код</th>
      <th class="p-3">RegExp</th>
      <th class="p-3">Приоритет</th>
      <th class="p-3 w-64 text-right">Действия</th>
    </tr>
  </thead>
  <tbody>
  @forelse($items as $r)
    @php
      $isRegex   = (bool)($r->is_regex ?? false);
      $fromShown = Str::limit($r->from ?? '—', 120);
      $toShown   = $r->code === '410' ? '—' : Str::limit($r->to ?? '—', 120);
      $fromUrl   = $isRegex ? null : ($base . '/' . ltrim((string)$r->from, '/'));
      $toUrl     = ($r->code !== '410' && !empty($r->to))
                    ? (Str::startsWith($r->to, ['http://','https://']) ? $r->to : ($base . '/' . ltrim((string)$r->to, '/')))
                    : null;
    @endphp
    <tr class="border-t align-top hover:bg-gray-50">
      <td class="p-3 font-mono break-all">
        {{ $fromShown }}
        <div class="mt-1 flex flex-wrap gap-2 text-xs">
          @if(!$isRegex && $fromUrl)
            <a href="{{ $fromUrl }}" target="_blank" rel="noopener" class="inline-flex items-center px-2 py-0.5 rounded bg-sky-100 text-sky-800"
               title="Открыть исходный URL в новой вкладке">Проверить</a>
            <button type="button" class="inline-flex items-center px-2 py-0.5 rounded border"
                    data-url="{{ $fromUrl }}"
                    onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='Скопировано'; setTimeout(()=>this.textContent='Копировать FROM',1500); });">
              Копировать FROM
            </button>
          @endif
        </div>
      </td>

      <td class="p-3 font-mono break-all">
        @if($r->code === '410')
          <span class="text-gray-400">—</span>
        @else
          {{ $toShown }}
          @if($toUrl)
            <div class="mt-1 flex flex-wrap gap-2 text-xs">
              <a href="{{ $toUrl }}" target="_blank" rel="noopener" class="inline-flex items-center px-2 py-0.5 rounded bg-sky-100 text-sky-800"
                 title="Открыть целевой URL">Открыть</a>
              <button type="button" class="inline-flex items-center px-2 py-0.5 rounded border"
                      data-url="{{ $toUrl }}"
                      onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='Скопировано'; setTimeout(()=>this.textContent='Копировать TO',1500); });">
                Копировать TO
              </button>
            </div>
          @endif
        @endif
      </td>

      <td class="p-3">
        @php
          $code = (string)($r->code ?? '302');
          $codeClass = $code==='410' ? 'bg-gray-600' : ($code==='301' ? 'bg-green-600' : 'bg-yellow-600');
        @endphp
        <span class="inline-block px-2 py-0.5 rounded text-white {{ $codeClass }}">
          {{ $code }}
        </span>
      </td>

      <td class="p-3">
        @if($isRegex)
          <span class="inline-block px-2 py-0.5 rounded bg-purple-100 text-purple-800">да</span>
        @else
          <span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700">нет</span>
        @endif
      </td>

      <td class="p-3">{{ (int)($r->priority ?? 0) }}</td>

      <td class="p-3 text-right">
        <div class="flex items-center justify-end gap-2 flex-wrap">
          <a class="text-blue-600 hover:underline" href="{{ route('seo.redirects.edit',$r->id) }}" title="Изменить правило">Изменить</a>
          <form action="{{ route('seo.redirects.destroy',$r->id) }}" class="inline" method="post"
                onsubmit="return confirm('Удалить правило редиректа?');">
            @csrf @method('DELETE')
            <button class="text-red-600 hover:underline">Удалить</button>
          </form>
        </div>
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="6" class="p-6 text-center text-gray-500">
        Правил не найдено. <a href="{{ route('seo.redirects.create') }}" class="text-blue-600 hover:underline">Создать первое</a>.
      </td>
    </tr>
  @endforelse
  </tbody>
</table>

<div class="mt-4">
  {{ $items->appends(['q' => $qParam, 'per_page' => $perPageParam])->links() }}
</div>

{{-- Маленькая памятка --}}
<div class="mt-6 p-3 rounded border bg-white text-xs text-gray-600 leading-relaxed">
  <div class="font-semibold mb-1">Как применяются редиректы</div>
  <ul class="list-disc pl-5 space-y-1">
    <li><strong>Priority</strong> — чем больше число, тем раньше срабатывает правило.</li>
    <li><strong>RegExp = да</strong> — поле <em>From</em> трактуется как регулярное выражение (PHP PCRE).</li>
    <li><strong>Код 410</strong> — адрес удалён (целевой URL не требуется).</li>
  </ul>
</div>
@endsection
