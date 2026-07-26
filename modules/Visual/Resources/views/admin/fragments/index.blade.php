@extends('layouts.admin')
@section('title','Фрагменты')

@section('content')
@php
  use Modules\Visual\Models\Fragment;

  $zoneLabels = Fragment::ZONE_LABELS;
  $hasFilters = request()->hasAny(['search', 'zone', 'status']);
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div class="flex items-center gap-3 min-w-0">
    <span class="admin-icon-badge"><i class="fas fa-puzzle-piece"></i></span>
    <div class="min-w-0">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">Фрагменты</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Дополнительные блоки в готовых страницах сайта и панели: объявления, баннеры, сноски.
      </p>
    </div>
  </div>

  <a href="{{ route('admin.visual.fragments.create') }}"
     class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
    <i class="fas fa-plus"></i> Новый фрагмент
  </a>
</div>

@includeIf('layouts.partials.flash')

{{-- ── Фильтры ── --}}
<form method="GET" action="{{ route('admin.visual.fragments.index') }}" class="admin-card p-5 mb-5">
  <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
    <i class="fas fa-filter text-indigo-500"></i> Фильтры
  </h2>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
    <div class="md:col-span-2">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Поиск</label>
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
             width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
        </svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Название или slug…"
               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Зона</label>
      <select name="zone" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <option value="">Все зоны</option>
        @foreach($zoneLabels as $value => $label)
          <option value="{{ $value }}" @selected(request('zone') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Статус</label>
      <select name="status" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <option value="">Все</option>
        <option value="active" @selected(request('status') === 'active')>Включённые</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Выключенные</option>
      </select>
    </div>
  </div>

  <div class="mt-4 flex flex-wrap items-center gap-2">
    <button type="submit"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
      <i class="fas fa-magnifying-glass"></i> Применить
    </button>
    @if($hasFilters)
      <a href="{{ route('admin.visual.fragments.index') }}"
         class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
        <i class="fas fa-rotate-left"></i> Сбросить
      </a>
    @endif
  </div>
</form>

@if($fragments->isEmpty())
  {{-- ── Пустое состояние ── --}}
  <div class="admin-card p-10 text-center">
    <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-puzzle-piece"></i></span>

    @if($hasFilters)
      <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Ничего не найдено</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Под выбранные фильтры не подходит ни один фрагмент.</p>
      <a href="{{ route('admin.visual.fragments.index') }}"
         class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
        <i class="fas fa-rotate-left"></i> Сбросить фильтры
      </a>
    @else
      <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Фрагментов пока нет</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-5">
        Фрагмент — это блок, который выводится в готовой странице: полоса объявления над шапкой,
        сноска под содержимым, напоминание в панели. Шапку и подвал он не заменяет: пока фрагмент
        выключен или пуст, страницы выглядят как обычно. Шесть заготовок создаются при установке —
        добавить их можно командой <span class="font-mono">php artisan fragments:seed-default</span>.
      </p>
      <a href="{{ route('admin.visual.fragments.create') }}"
         class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
        <i class="fas fa-plus"></i> Создать фрагмент
      </a>
    @endif
  </div>
@else
  {{-- ── Массовые действия ── --}}
  <form method="POST" action="{{ route('admin.visual.fragments.bulkToggle') }}" id="fragBulkForm" class="admin-card p-4 mb-4">
    @csrf
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">С отмеченными:</span>
      <select name="action"
              class="border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <option value="enable">Включить</option>
        <option value="disable">Выключить</option>
      </select>
      <button type="submit"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
        <i class="fas fa-bolt"></i> Применить
      </button>
      <button type="submit" form="fragBulkRebuild"
              class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                     hover:border-indigo-400 hover:text-indigo-600 px-4 py-2 text-sm font-semibold transition">
        <i class="fas fa-arrows-rotate"></i> Пересобрать HTML
      </button>
      <span id="fragBulkCounter" class="text-sm text-gray-500 dark:text-gray-400"></span>
    </div>
  </form>

  {{-- ── Таблица ── --}}
  <div class="admin-card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
        <tr>
          <th class="px-4 py-3 text-center w-10"><input type="checkbox" id="fragSelectAll" class="border-gray-400"></th>
          <th class="px-4 py-3 text-left font-semibold">Фрагмент</th>
          <th class="px-4 py-3 text-left font-semibold">Где показывается</th>
          <th class="px-4 py-3 text-left font-semibold">Содержимое</th>
          <th class="px-4 py-3 text-center font-semibold">Статус</th>
          <th class="px-4 py-3 text-center font-semibold">Действия</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @foreach($fragments as $f)
          @php
            $length = mb_strlen(trim(strip_tags((string) $f->html_cached)));
            $isSystem = $f->isSystem();
          @endphp
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <td class="px-4 py-3 text-center align-top">
              <input type="checkbox" name="ids[]" value="{{ $f->id }}" form="fragBulkForm"
                     class="frag-checkbox border-gray-400">
            </td>

            <td class="px-4 py-3 align-top">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-gray-900 dark:text-white">{{ $f->title }}</span>
                @if($isSystem)
                  <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5"
                        title="Slug и зона закреплены системой">системный</span>
                @endif
              </div>
              <div class="text-xs font-mono text-gray-400 mt-0.5">{{ $f->slug }}</div>
              @if($f->updated_at)
                <div class="text-xs text-gray-400 mt-1">обновлён {{ $f->updated_at->format('d.m.Y H:i') }}</div>
              @endif
            </td>

            <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
              {{ $zoneLabels[$f->zone] ?? ($f->zone ?: 'Без зоны') }}
              @if(!$f->zone)
                <div class="text-xs text-gray-400 mt-1">выводится только вручную по slug</div>
              @endif
            </td>

            <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
              @if($length > 0)
                {{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $f->html_cached)), 60) }}
                <div class="text-xs text-gray-400 mt-1">{{ $length }} симв.</div>
              @else
                <span class="text-xs text-yellow-700"><i class="fas fa-triangle-exclamation"></i> пусто — ничего не выведется</span>
              @endif
            </td>

            <td class="px-4 py-3 text-center align-top">
              @if($f->is_active)
                <span class="inline-block text-xs bg-green-100 text-green-800 px-2 py-0.5">включён</span>
              @else
                <span class="inline-block text-xs bg-gray-200 text-gray-700 px-2 py-0.5">выключен</span>
              @endif
            </td>

            <td class="px-4 py-3 text-center align-top whitespace-nowrap">
              <a href="{{ route('admin.visual.fragments.edit', $f) }}"
                 class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                        text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                 title="Редактировать"><i class="fas fa-pen"></i></a>

              <a href="{{ route('admin.visual.fragments.history', $f) }}"
                 class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                        text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                 title="История версий"><i class="fas fa-clock-rotate-left"></i></a>

              <button type="submit" form="frag-duplicate-{{ $f->id }}"
                      class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                             text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                      title="Дублировать"><i class="fas fa-copy"></i></button>

              <button type="submit" form="frag-rebuild-{{ $f->id }}"
                      class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                             text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                      title="Пересобрать HTML из шаблона"><i class="fas fa-arrows-rotate"></i></button>

              <button type="submit" form="frag-delete-{{ $f->id }}"
                      class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                             text-gray-600 dark:text-gray-300 hover:border-red-400 hover:text-red-600 transition"
                      title="Удалить"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $fragments->links() }}</div>

  {{-- Формы действий — вне таблицы: вложенные формы HTML запрещает --}}
  <form id="fragBulkRebuild" method="POST" action="{{ route('admin.visual.fragments.bulkRebuild') }}" class="hidden">
    @csrf
  </form>
  @foreach($fragments as $f)
    <form id="frag-duplicate-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.duplicate', $f) }}" class="hidden">@csrf</form>
    <form id="frag-rebuild-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.rebuild', $f) }}" class="hidden"
          onsubmit="return confirm('Пересобрать HTML фрагмента «{{ addslashes($f->title) }}» из шаблона? Ручные правки содержимого будут заменены.');">
      @csrf
    </form>
    <form id="frag-delete-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.destroy', $f) }}" class="hidden"
          onsubmit="return confirm('Удалить фрагмент «{{ addslashes($f->title) }}»?');">
      @csrf @method('DELETE')
    </form>
  @endforeach

  <p class="admin-hint mt-5 p-3">
    Выключенный или пустой фрагмент на страницу не попадает — вёрстка остаётся прежней.
    Системные фрагменты (site-header, site-footer) массовым переключением не затрагиваются.
  </p>
@endif
@endsection

@push('scripts')
<script>
  (function () {
    const selectAll = document.getElementById('fragSelectAll');
    const boxes = () => document.querySelectorAll('.frag-checkbox');
    const counter = document.getElementById('fragBulkCounter');
    const rebuildForm = document.getElementById('fragBulkRebuild');

    const refresh = () => {
      const checked = [...document.querySelectorAll('.frag-checkbox:checked')];
      if (counter) counter.textContent = checked.length ? `отмечено: ${checked.length}` : '';
      if (selectAll) selectAll.checked = checked.length > 0 && checked.length === boxes().length;

      // Массовая пересборка — своя форма, копируем в неё отмеченные id
      if (rebuildForm) {
        rebuildForm.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
        checked.forEach(cb => {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'ids[]';
          hidden.value = cb.value;
          rebuildForm.appendChild(hidden);
        });
      }
    };

    selectAll?.addEventListener('change', function () {
      boxes().forEach(cb => { cb.checked = this.checked; });
      refresh();
    });
    boxes().forEach(cb => cb.addEventListener('change', refresh));
    refresh();
  })();
</script>
@endpush
