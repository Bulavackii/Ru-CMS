{{-- modules/Slideshow/Views/admin/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Слайдшоу')
@section('header', 'Управление слайдшоу')

@section('content')
@php
    $q        = trim((string)request('q', ''));
    $position = trim((string)request('position', '')); // top|bottom|''
    $perPage  = (int)request()->integer('per_page', 25);

    $knownPositions = collect(['top','bottom'])
        ->merge($slideshows->pluck('position')->filter()->unique()->values())
        ->unique()->values()->all();

    // функция отдаёт класс цвета под позицию
    $posColor = function($pos){
        return match($pos){
            'top'    => ['badge'=>'bg-emerald-100 text-emerald-900','bar'=>'bg-emerald-500/80','label'=>'Верх'],
            'bottom' => ['badge'=>'bg-sky-100 text-sky-900',      'bar'=>'bg-sky-500/80',     'label'=>'Низ'],
            default  => ['badge'=>'bg-gray-100 text-gray-800',    'bar'=>'bg-gray-400/70',    'label'=>$pos ?: '—'],
        };
    };
@endphp

{{-- Шапка --}}
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎞️ Слайдшоу</h1>
    <div class="text-xs text-gray-500 mt-1">
      Всего: {{ number_format($slideshows->total(), 0, ',', ' ') }}
      @if($q) • Поиск: <code>{{ $q }}</code>@endif
      @if($position) • Позиция: <code>{{ $position }}</code>@endif
    </div>
  </div>

  @if(Route::has('admin.slideshow.create'))
    <a href="{{ route('admin.slideshow.create') }}"
       class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-black text-white hover:bg-gray-800 shadow-sm">
      <i class="fa-solid fa-plus"></i> Создать слайдшоу
    </a>
  @endif
</div>

{{-- Фильтры как в «Картинках» --}}
<form method="get" action="{{ route('admin.slideshow.index') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-2 mb-3">
  <div class="lg:col-span-4 flex flex-wrap items-center gap-2 mb-1">
    <span class="text-sm text-gray-500">Категории (позиции):</span>

    <a href="{{ route('admin.slideshow.index', array_filter(['q'=>$q, 'per_page'=>$perPage])) }}"
       class="px-3 py-1.5 rounded-full border text-sm shadow-sm {{ $position==='' ? 'bg-black text-white' : 'bg-white hover:bg-gray-100 dark:bg-gray-900 dark:hover:bg-gray-800' }}">
      Все
    </a>

    @foreach($knownPositions as $pos)
      @php $c = $posColor($pos); @endphp
      <a href="{{ route('admin.slideshow.index', array_filter(['q'=>$q, 'position'=>$pos, 'per_page'=>$perPage])) }}"
         class="px-3 py-1.5 rounded-full border text-sm shadow-sm {{ $position===$pos ? 'bg-black text-white' : 'bg-white hover:bg-gray-100 dark:bg-gray-900 dark:hover:bg-gray-800' }}">
        {{ $c['label'] }}
      </a>
    @endforeach
  </div>

  <select name="position" class="border rounded-md px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-700">
    <option value="">Все позиции</option>
    @foreach($knownPositions as $pos)
      @php $c = $posColor($pos); @endphp
      <option value="{{ $pos }}" @selected($position===$pos)>{{ $c['label'] }}</option>
    @endforeach
  </select>

  <input type="text" name="q" value="{{ $q }}" placeholder="Искать по названию…"
         class="border rounded-md px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-700" />

  <select name="per_page" class="border rounded-md px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-700">
    @foreach([10,25,50,100] as $pp)
      <option value="{{ $pp }}" @selected($perPage===$pp)>{{ $pp }} на странице</option>
    @endforeach
  </select>

  <button class="px-3 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm shadow-sm">Искать</button>

  @if($q || $position || $perPage!==25)
    <a href="{{ route('admin.slideshow.index') }}"
       class="px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-sm dark:bg-gray-800 dark:hover:bg-gray-700">
      Сброс
    </a>
  @endif
</form>

{{-- Массовые действия --}}
<form id="bulk-delete-form" method="POST" action="{{ route('admin.slideshow.bulk-delete') }}" class="mb-3 hidden">
  @csrf
  <div class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
    <span class="text-sm text-blue-800 dark:text-blue-200">
      Выбрано: <strong id="selected-count">0</strong> слайдшоу
    </span>
    <button type="submit" 
            onclick="return confirm('Удалить выбранные слайдшоу?')"
            class="px-3 py-1.5 rounded-md bg-red-600 hover:bg-red-700 text-white text-sm">
      <i class="fa-regular fa-trash-can"></i> Удалить выбранные
    </button>
    <button type="button" onclick="clearSelection()" class="px-3 py-1.5 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-sm">
      Отменить
    </button>
  </div>
</form>

{{-- Таблица --}}
<div class="overflow-x-auto border rounded-xl shadow-sm dark:border-gray-800">
  <table class="min-w-full text-sm bg-white dark:bg-gray-900">
    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase">
      <tr>
        <th class="px-4 py-3 text-left w-12">
          <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        </th>
        <th class="px-4 py-3 text-left">ID</th>
        <th class="px-4 py-3 text-left">Название</th>

        {{-- ВИЗУАЛЬНОЕ ВЫДЕЛЕНИЕ колонки ПОЗИЦИЯ --}}
        <th class="px-4 py-3 text-left relative">
          <span class="font-semibold">Позиция</span>
        </th>

        <th class="px-4 py-3 text-left">Слайдов</th>
        <th class="px-4 py-3 text-left">Статус</th>
        <th class="px-4 py-3 text-left">Шорткод</th>
        <th class="px-4 py-3 text-center w-56">Действия</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
      @forelse($slideshows as $s)
        @php
          $c = $posColor($s->position);
          $modelPath = 'Modules\\Slideshow\\Models\\Slideshow';
          $slideshowId = $s->id;
          $bladeShortcode = '@include("Slideshow::public.slideshow", ["slideshow" => ' . $modelPath . '::find(' . $slideshowId . ')])';
          $publicUrl = Route::has('slideshow.show') && !empty($s->slug) ? route('slideshow.show', $s->slug) : null;
        @endphp

        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
          <td class="px-4 py-3">
            <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="slide-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
          </td>
          {{-- цветная вертикальная метка слева от строки --}}
          <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300 relative">
            <span class="absolute left-0 top-0 h-full w-1 {{ $c['bar'] }}"></span>
            {{ $s->id }}
          </td>

          <td class="px-4 py-3">
            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $s->title }}</div>
            @if(!empty($s->description))
              <div class="text-xs text-gray-500 line-clamp-1">{{ $s->description }}</div>
            @endif
          </td>

          <td class="px-4 py-3">
            <span class="inline-block px-2 py-0.5 rounded {{ $c['badge'] }} text-xs font-semibold ring-1 ring-black/5">
              {{ $c['label'] }}
            </span>
          </td>

          <td class="px-4 py-3">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800">
              <i class="fa-regular fa-images"></i> {{ $s->items->count() }}
            </span>
          </td>

          <td class="px-4 py-3">
            <form action="{{ route('admin.slideshow.toggle-published', $s->id) }}" method="POST" class="inline">
              @csrf
              @method('PATCH')
              <button type="submit" 
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold transition
                             {{ $s->published 
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50' 
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                <i class="fa-regular {{ $s->published ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                {{ $s->published ? 'Опубликовано' : 'Скрыто' }}
              </button>
            </form>
          </td>

          <td class="px-4 py-3">
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded px-2 py-1 max-w-[520px]">
              <span class="truncate text-xs">{{ $bladeShortcode }}</span>
              <button type="button" class="ml-1 text-gray-500 hover:text-black dark:hover:text-white"
                      title="Скопировать шорткод"
                      onclick="navigator.clipboard.writeText(@js($bladeShortcode)).then(()=>toast('Скопировано'));">
                <i class="fa-regular fa-copy"></i>
              </button>
            </div>
          </td>

          <td class="px-4 py-3 text-center">
            <div class="flex items-center justify-center gap-2 flex-wrap">
              <a href="{{ route('admin.slideshow.edit', $s->id) }}" class="text-blue-600 hover:text-blue-800" title="Редактировать">
                <i class="fa-regular fa-pen-to-square"></i>
              </a>
              <form action="{{ route('admin.slideshow.destroy', $s->id) }}" method="POST"
                    onsubmit="return confirm('Удалить это слайдшоу?');" class="inline">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:text-red-800" title="Удалить">
                  <i class="fa-regular fa-trash-can"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="px-4 py-8 text-center text-gray-500">
            Слайдшоу пока нет. Нажмите «Создать слайдшоу», чтобы добавить первое.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- Пагинация с сохранением фильтров/поиска --}}
<div class="mt-4">
  {{ $slideshows->appends(['q'=>$q,'position'=>$position,'per_page'=>$perPage])->links('vendor.pagination.tailwind') }}
</div>

{{-- Мини-тост для статусов копирования --}}
<div id="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 hidden px-3 py-2 rounded bg-black text-white text-sm shadow-lg">
  Скопировано
</div>
<script>
  function toast(text){ const t=document.getElementById('toast'); t.textContent=text||'Готово'; t.classList.remove('hidden');
    clearTimeout(window.__toastTimer); window.__toastTimer=setTimeout(()=>t.classList.add('hidden'),1200); }

  // Массовое удаление
  (function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.slide-checkbox');
    const bulkForm = document.getElementById('bulk-delete-form');
    const selectedCount = document.getElementById('selected-count');

    function updateSelection() {
      const checked = document.querySelectorAll('.slide-checkbox:checked');
      const count = checked.length;
      selectedCount.textContent = count;
      
      if (count > 0) {
        bulkForm.classList.remove('hidden');
        // Добавляем скрытые поля с ID
        bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        checked.forEach(cb => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'ids[]';
          input.value = cb.value;
          bulkForm.appendChild(input);
        });
      } else {
        bulkForm.classList.add('hidden');
      }
    }

    selectAll?.addEventListener('change', function() {
      checkboxes.forEach(cb => cb.checked = this.checked);
      updateSelection();
    });

    checkboxes.forEach(cb => {
      cb.addEventListener('change', updateSelection);
    });

    window.clearSelection = function() {
      checkboxes.forEach(cb => cb.checked = false);
      selectAll.checked = false;
      updateSelection();
    };
  })();
</script>
@endsection
