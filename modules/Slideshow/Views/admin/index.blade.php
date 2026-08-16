{{-- modules/Slideshow/Views/admin/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('admin.sections.slideshow'))
@section('header', __('admin.slideshow.manage'))

@section('content')
@php
    $q        = trim((string)request('q', ''));
    $position = trim((string)request('position', '')); // top|bottom|''
    $perPage  = (int)request()->integer('per_page', 25);

    $knownPositions = collect(['top','bottom'])
        ->merge($slideshows->pluck('position')->filter()->unique()->values())
        ->unique()->values()->all();

    // Цвета позиций. ВАЖНО: emerald/sky в собранном tailwind.min.css отсутствуют
    // (см. CLAUDE.md) — раньше бейджи позиций были бесцветными. Берём палитру,
    // которая реально есть: indigo / green / gray.
    $posColor = function($pos){
        return match($pos){
            'top'    => ['badge'=>'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300','bar'=>'bg-indigo-500','label'=>__('admin.slideshow.pos_top'),'icon'=>'fa-arrow-up'],
            'bottom' => ['badge'=>'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',   'bar'=>'bg-green-500', 'label'=>__('admin.slideshow.pos_bottom'), 'icon'=>'fa-arrow-down'],
            default  => ['badge'=>'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',       'bar'=>'bg-gray-400',  'label'=>$pos ?: '—','icon'=>'fa-location-dot'],
        };
    };
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div class="flex items-center gap-3 min-w-0">
    <span class="admin-icon-badge"><i class="fas fa-images"></i></span>
    <div class="min-w-0">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.sections.slideshow') }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('admin.slideshow.index_hint') }}
      </p>
    </div>
  </div>

  @if(Route::has('admin.slideshow.create'))
    <a href="{{ route('admin.slideshow.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0">
      <i class="fa-solid fa-plus"></i> {{ __('admin.slideshow.create') }}
    </a>
  @endif
</div>

{{-- ── Фильтры ── --}}
<form method="get" action="{{ route('admin.slideshow.index') }}" class="admin-card p-5 mb-5">
  <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
    <i class="fas fa-filter text-indigo-500"></i> {{ __('admin.common.filters') }}
  </h2>

  {{-- Быстрые чипы позиций --}}
  <div class="flex flex-wrap items-center gap-2 mb-4">
    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mr-1">{{ __('admin.slideshow.position_short') }}</span>

    <a href="{{ route('admin.slideshow.index', array_filter(['q'=>$q, 'per_page'=>$perPage])) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition
              {{ $position==='' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700' }}">
      <i class="fas fa-layer-group"></i> {{ __('admin.common.all') }}
    </a>

    @foreach($knownPositions as $pos)
      @php $c = $posColor($pos); @endphp
      <a href="{{ route('admin.slideshow.index', array_filter(['q'=>$q, 'position'=>$pos, 'per_page'=>$perPage])) }}"
         class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition
                {{ $position===$pos ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700' }}">
        <i class="fas {{ $c['icon'] }}"></i> {{ $c['label'] }}
      </a>
    @endforeach
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.slideshow.position_short') }}</label>
      <select name="position" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <option value="">{{ __('admin.slideshow.all_positions') }}</option>
        @foreach($knownPositions as $pos)
          @php $c = $posColor($pos); @endphp
          <option value="{{ $pos }}" @selected($position===$pos)>{{ $c['label'] }}</option>
        @endforeach
      </select>
    </div>

    <div class="lg:col-span-2">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.header.search') }}</label>
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
             width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
        </svg>
        <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('admin.slideshow.search_ph') }}"
               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.slideshow.per_page') }}</label>
      <select name="per_page" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        @foreach([10,25,50,100] as $pp)
          <option value="{{ $pp }}" @selected($perPage===$pp)>{{ $pp }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="flex items-center gap-2 mt-4">
    <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
      <i class="fas fa-magnifying-glass"></i> {{ __('admin.slideshow.search_do') }}
    </button>
    @if($q || $position || $perPage!==25)
      <a href="{{ route('admin.slideshow.index') }}"
         class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
        <i class="fas fa-xmark"></i> {{ __('admin.users.reset') }}
      </a>
    @endif
  </div>
</form>

{{-- ── Массовые действия ── --}}
<form id="bulk-delete-form" method="POST" action="{{ route('admin.slideshow.bulk-delete') }}" class="admin-card p-4 mb-5 hidden"
      style="border-left:3px solid #6366f1">
  @csrf
  <div class="flex flex-wrap items-center gap-3">
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold
                 bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
      <i class="fas fa-check-double"></i> {{ __('admin.slideshow.selected') }} <strong id="selected-count">0</strong>
    </span>
    <button type="submit"
            onclick="return confirm(@js(__('admin.slideshow.delete_selected_confirm')))"
            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 text-sm font-semibold shadow-sm transition">
      <i class="fa-regular fa-trash-can"></i> {{ __('admin.slideshow.delete_selected') }}
    </button>
    <button type="button" onclick="clearSelection()"
            class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium
                   text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
      {{ __('admin.slideshow.cancel_sel') }}
    </button>
  </div>
</form>

{{-- ── Подсказка ── --}}
<div class="admin-note px-4 py-3 mb-5 text-sm">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div class="flex items-center gap-2 font-medium">
      <i class="fas fa-lightbulb"></i>
      <span>{{ __('admin.slideshow.note') }}</span>
    </div>
    <div class="flex items-center gap-2 text-xs shrink-0">
      <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
        {{ __('admin.common.total') }} {{ number_format($slideshows->total(), 0, ',', ' ') }}
      </span>
    </div>
  </div>
</div>

{{-- ── Таблица ── --}}
<div class="admin-card overflow-hidden">
 <div class="overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
      <tr>
        <th class="px-4 py-3 text-left w-10">
          <input type="checkbox" id="select-all" class="h-4 w-4" title="{{ __('admin.slideshow.select_all_title') }}">
        </th>
        <th class="px-4 py-3 text-left font-semibold">{{ __('admin.slideshow.name_short') }}</th>
        <th class="px-4 py-3 text-left font-semibold col-extra">{{ __('admin.slideshow.position_short') }}</th>
        <th class="px-4 py-3 text-center font-semibold col-narrow">{{ __('admin.slideshow.slides_count') }}</th>
        <th class="px-4 py-3 text-center font-semibold">{{ __('admin.common.status') }}</th>
        <th class="px-4 py-3 text-left font-semibold hidden xl:table-cell">{{ __('admin.slideshow.shortcode') }}</th>
        <th class="px-4 py-3 text-center font-semibold w-24">{{ __('admin.common.actions') }}</th>
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

        <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-800 transition">
          <td class="px-4 py-3 align-top relative">
            {{-- цветная вертикальная метка позиции слева от строки --}}
            <span class="absolute left-0 top-0 h-full w-1 {{ $c['bar'] }}"></span>
            <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="slide-checkbox h-4 w-4">
          </td>

          <td class="px-4 py-3 align-top">
            <a href="{{ route('admin.slideshow.edit', $s->id) }}"
               class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition break-words">
              {{ $s->title }}
            </a>
            @if(!empty($s->description))
              <div class="only-wide text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mt-0.5">{{ $s->description }}</div>
            @endif
            <div class="mt-1 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
              <span class="font-mono">ID {{ $s->id }}</span>
              @if(!empty($s->slug))<span class="font-mono">/{{ $s->slug }}</span>@endif
            </div>
          </td>

          <td class="px-4 py-3 align-top col-extra">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 {{ $c['badge'] }} text-xs font-semibold whitespace-nowrap">
              <i class="fas {{ $c['icon'] }}"></i> {{ $c['label'] }}
            </span>
          </td>

          <td class="px-4 py-3 align-top text-center col-narrow">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium
                         bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
              <i class="fa-regular fa-images"></i> {{ $s->items->count() }}
            </span>
          </td>

          <td class="px-4 py-3 align-top text-center">
            <form action="{{ route('admin.slideshow.toggle-published', $s->id) }}" method="POST" class="inline">
              @csrf
              @method('PATCH')
              <button type="submit"
                      title="{{ $s->published ? __('admin.slideshow.hide_it') : __('admin.slideshow.publish_it') }}"
                      class="st-chip {{ $s->published ? 'st-on' : 'st-off' }} inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap transition
                             {{ $s->published
                                ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 hover:brightness-95'
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 hover:brightness-95' }}">
                <i class="fa-regular {{ $s->published ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                <span class="st-text">{{ $s->published ? __('admin.slideshow.published') : __('admin.slideshow.hidden') }}</span>
              </button>
            </form>
          </td>

          <td class="px-4 py-3 align-top hidden xl:table-cell">
            <div class="flex items-center gap-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-2 py-1 max-w-md">
              <span class="truncate text-xs font-mono text-gray-600 dark:text-gray-300">{{ $bladeShortcode }}</span>
              <button type="button" class="ml-auto text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                      title="{{ __('admin.slideshow.copy_shortcode') }}"
                      onclick="navigator.clipboard.writeText(@js($bladeShortcode)).then(()=>toast(@js(__('admin.slideshow.copied'))));">
                <i class="fa-regular fa-copy"></i>
              </button>
            </div>
          </td>

          <td class="px-4 py-3 align-top text-center">
            <div class="inline-flex items-center gap-1.5">
              <a href="{{ route('admin.slideshow.edit', $s->id) }}"
                 class="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                 title="{{ __('admin.edit') }}">
                <i class="fa-solid fa-pen"></i>
              </a>
              <form action="{{ route('admin.slideshow.destroy', $s->id) }}" method="POST"
                    onsubmit="return confirm(@js(__('admin.slideshow.delete_confirm')));" class="inline">
                @csrf @method('DELETE')
                <button class="inline-flex items-center justify-center w-8 h-8 bg-red-600 hover:bg-red-700 text-white shadow-sm transition"
                        title="{{ __('admin.delete') }}">
                  <i class="fa-regular fa-trash-can"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="px-4 py-12 text-center">
            <span class="admin-icon-badge mx-auto mb-3"><i class="fas fa-images"></i></span>
            <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('admin.slideshow.empty') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Нажмите
              <a href="{{ route('admin.slideshow.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('admin.slideshow.create_link') }}</a>,
              чтобы добавить первое.
            </p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
 </div>
</div>

{{-- Пагинация с сохранением фильтров/поиска --}}
<div class="mt-4">
  {{ $slideshows->appends(['q'=>$q,'position'=>$position,'per_page'=>$perPage])->links('vendor.pagination.tailwind') }}
</div>

{{-- Мини-тост для статусов копирования --}}
<div id="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 hidden px-3 py-2 rounded bg-black text-white text-sm shadow-lg">
  {{ __('admin.slideshow.copied') }}
</div>
<script>
  function toast(text){ const t=document.getElementById('toast'); t.textContent=text||@js(__('admin.slideshow.done')); t.classList.remove('hidden');
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
