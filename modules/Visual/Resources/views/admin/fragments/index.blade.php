@extends('layouts.admin')
@section('title','Фрагменты')

@section('content')
@php
  use Modules\Visual\Models\Fragment;

  $existsHeader = Fragment::where('slug','site-header')->exists();
  $existsFooter = Fragment::where('slug','site-footer')->exists();

  $btnBase      = 'inline-flex items-center justify-center px-3 py-2 rounded font-medium shadow transition';
  $btnPrimary   = 'text-white bg-blue-600 hover:bg-blue-700';
  $btnSecondary = 'text-white bg-indigo-600 hover:bg-indigo-700';
  $btnBorder    = 'border bg-white text-gray-800 hover:bg-gray-50';
  $btnDisabled  = 'cursor-not-allowed bg-gray-300 text-gray-600';
@endphp

<h1 class="text-2xl font-bold mb-4">🧩 Фрагменты</h1>

{{-- Быстрые действия --}}
<div class="mb-4 rounded border bg-white dark:bg-gray-900 p-4 flex flex-wrap gap-3 items-center">
  @if(!$existsHeader)
    <a href="{{ route('admin.visual.fragments.create',['preset'=>'header']) }}" class="{{ $btnBase }} {{ $btnPrimary }}">Создать шапку (site-header)</a>
  @else
    <span class="{{ $btnBase }} {{ $btnDisabled }}">Создать шапку (site-header)</span>
  @endif

  @if(!$existsFooter)
    <a href="{{ route('admin.visual.fragments.create',['preset'=>'footer']) }}" class="{{ $btnBase }} {{ $btnSecondary }}">Создать подвал (site-footer)</a>
  @else
    <span class="{{ $btnBase }} {{ $btnDisabled }}">Создать подвал (site-footer)</span>
  @endif

  <a href="{{ route('admin.visual.fragments.create') }}" class="{{ $btnBase }} {{ $btnBorder }}">Новый фрагмент</a>

  <div class="ml-auto flex flex-wrap gap-2 items-center">
    <input id="search" type="text" placeholder="Поиск…" class="border rounded px-3 py-2 text-sm w-56">
    <select id="zoneFilter" class="border rounded px-2 py-2 text-sm">
      <option value="">Зона: все</option>
      <option value="header">Header</option>
      <option value="footer">Footer</option>
      <option value="custom">Custom</option>
    </select>
    <select id="statusFilter" class="border rounded px-2 py-2 text-sm">
      <option value="">Статус: все</option>
      <option value="1">Активен</option>
      <option value="0">Выключен</option>
    </select>
    <button id="resetFilters" class="border rounded px-3 py-2 text-sm">Сбросить</button>
  </div>
</div>

{{-- Список --}}
@if($fragments->count())
  <div class="overflow-x-auto border rounded">
    <table id="fragmentsTable" class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr class="border-b">
          <th data-col="0" data-type="text" class="sortable text-left py-2 px-3 select-none cursor-pointer">Заголовок</th>
          <th data-col="1" data-type="text" class="sortable text-left py-2 px-3 select-none cursor-pointer">Slug</th>
          <th data-col="2" data-type="text" class="sortable text-left py-2 px-3 select-none cursor-pointer">Зона</th>
          <th data-col="3" data-type="num"  class="sortable text-left py-2 px-3 select-none cursor-pointer">Статус</th>
          <th data-col="4" data-type="date" class="sortable text-left py-2 px-3 select-none cursor-pointer">Обновлён</th>
          <th class="py-2 px-3 text-right">Действия</th>
        </tr>
      </thead>
      <tbody>
        @foreach($fragments as $f)
          @php
            $isSystem = in_array($f->slug, ['site-header','site-footer'], true);
            $updated  = optional($f->updated_at)->format('d.m.Y H:i');
          @endphp
          <tr class="border-b hover:bg-gray-50"
              data-zone="{{ $f->zone ?? '' }}"
              data-status="{{ (int)$f->is_active }}">
            {{-- Заголовок --}}
            <td class="py-2 px-3">
              <div class="flex items-center gap-2">
                <span>{{ $f->title }}</span>
                @if($isSystem)
                  <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-800 text-white">system</span>
                @endif
              </div>
            </td>

            {{-- Slug --}}
            <td class="py-2 px-3 font-mono text-xs">
              <div class="flex items-center gap-2">
                <span>{{ $f->slug }}</span>
                <button type="button" class="copy-slug text-gray-500 hover:text-gray-800" data-slug="{{ $f->slug }}" title="Скопировать slug">📋</button>
              </div>
            </td>

            {{-- Зона --}}
            <td class="py-2 px-3">{{ $f->zone ?: '—' }}</td>

            {{-- Статус (для сортировки используем data-status на строке) --}}
            <td class="py-2 px-3">
              @if($f->is_active)
                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Активен</span>
              @else
                <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-600">Выключен</span>
              @endif
            </td>

            {{-- Обновлён --}}
            <td class="py-2 px-3 text-gray-500">{{ $updated }}</td>

            {{-- Действия --}}
            <td class="py-2 px-3 text-right space-x-2 whitespace-nowrap">
              <button type="button"
                      class="preview-btn text-gray-700 hover:text-black"
                      title="Предпросмотр"
                      data-frag="frag-tpl-{{ $f->id }}">👁</button>

              <a href="{{ route('admin.visual.fragments.edit',$f) }}" class="text-blue-600 hover:underline">Редактировать</a>

              <form action="{{ route('admin.visual.fragments.rebuild',$f) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-gray-600 hover:text-black" title="Пересобрать HTML"
                        onclick="return confirm('Пересобрать HTML для {{ $f->title }}?')">🔄</button>
              </form>

              <form action="{{ route('admin.visual.fragments.destroy',$f) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 hover:underline"
                        onclick="return confirm('Удалить фрагмент {{ $f->title }}?')">Удалить</button>
              </form>
            </td>
          </tr>

          {{-- Безопасно храним HTML для предпросмотра в <template> --}}
          <template id="frag-tpl-{{ $f->id }}">{!! $f->html_cached !!}</template>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $fragments->links() }}</div>
@else
  <p class="text-gray-600">Фрагментов пока нет.</p>
@endif

{{-- Модалка предпросмотра --}}
<div id="pvModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-5xl p-3">
    <div class="flex items-center justify-between mb-2">
      <div class="font-semibold">Предпросмотр</div>
      <div class="flex items-center gap-2">
        <label class="inline-flex items-center gap-2 text-sm"><input id="pvDark" type="checkbox" class="rounded">Dark</label>
        <select id="pvWidth" class="border rounded px-2 py-1 text-sm">
          <option value="375">Phone (375px)</option>
          <option value="768">Tablet (768px)</option>
          <option value="1024" selected>Desktop (1024px)</option>
          <option value="full">Full</option>
        </select>
        <button id="pvClose" class="border rounded px-3 py-1.5">Закрыть</button>
      </div>
    </div>
    <div class="border rounded bg-white p-2">
      <div id="pvWrap" class="mx-auto" style="width:1024px;max-width:100%;">
        <iframe id="pvFrame" class="w-full h-[520px] border rounded bg-white"></iframe>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // ===== helpers =====
  const $  = (s, r=document)=>r.querySelector(s);
  const $$ = (s, r=document)=>[...r.querySelectorAll(s)];
  const save = (k,v)=>localStorage.setItem(k, v);
  const load = (k)=>localStorage.getItem(k);

  // ===== поиск / фильтры =====
  const q = $('#search');
  const zone = $('#zoneFilter');
  const status = $('#statusFilter');
  const resetBtn = $('#resetFilters');
  const rows = $$('#fragmentsTable tbody tr');

  function applyFilters(){
    const term = (q.value || '').toLowerCase();
    const z = zone.value;
    const s = status.value;

    rows.forEach(tr=>{
      const text = tr.textContent.toLowerCase();
      const okText = !term || text.includes(term);
      const okZone = !z || tr.dataset.zone === z;
      const okStatus = !s || tr.dataset.status === s;
      tr.style.display = (okText && okZone && okStatus) ? '' : 'none';
    });

    // persist
    save('frag_f_q', q.value||'');
    save('frag_f_z', z||'');
    save('frag_f_s', s||'');
  }

  // восстановить
  q.value = load('frag_f_q') || '';
  zone.value = load('frag_f_z') || '';
  status.value = load('frag_f_s') || '';
  [q, zone, status].forEach(el=> el?.addEventListener('input', applyFilters));
  resetBtn.addEventListener('click', ()=>{
    q.value=''; zone.value=''; status.value='';
    applyFilters();
  });
  applyFilters();

  // ===== сортировка =====
  const headCells = $$('#fragmentsTable thead th.sortable');
  let sortState = JSON.parse(load('frag_sort') || '{"col":4,"dir":"desc"}');

  function parseDate(d){ // d.m.Y H:i
    if(!d) return 0;
    const m = d.match(/(\d{2})\.(\d{2})\.(\d{4})\s+(\d{2}):(\d{2})/);
    if(!m) return 0;
    return new Date(+m[3], +m[2]-1, +m[1], +m[4], +m[5]).getTime();
    }
  function cmp(a,b,type){
    if(type==='num') return (+a) - (+b);
    if(type==='date') return parseDate(a) - parseDate(b);
    return a.localeCompare(b, 'ru', {numeric:true, sensitivity:'base'});
  }
  function doSort(col, type, dir){
    const tbody = $('#fragmentsTable tbody');
    const trs = $$('#fragmentsTable tbody tr').filter(tr=>tr.style.display!=='none');
    trs.sort((r1,r2)=>{
      const a = r1.children[col].innerText.trim();
      const b = r2.children[col].innerText.trim();
      const res = cmp(a,b,type);
      return dir==='asc' ? res : -res;
    });
    trs.forEach(tr=>tbody.appendChild(tr));
    headCells.forEach(th=>th.classList.remove('bg-gray-100'));
    headCells.find(th=>+th.dataset.col===col)?.classList.add('bg-gray-100');

    sortState = {col, type, dir};
    save('frag_sort', JSON.stringify(sortState));
  }
  headCells.forEach(th=>{
    th.addEventListener('click', ()=>{
      const col = +th.dataset.col;
      const type = th.dataset.type;
      const dir = (sortState.col===col && sortState.dir==='asc') ? 'desc' : 'asc';
      doSort(col,type,dir);
    });
  });
  // инициализация сортировки
  doSort(sortState.col, sortState.type|| (headCells.find(h=>+h.dataset.col===sortState.col)?.dataset.type || 'text'), sortState.dir || 'desc');

  // ===== копирование slug =====
  $$('.copy-slug').forEach(b=>{
    b.addEventListener('click', async ()=>{
      try{ await navigator.clipboard.writeText(b.dataset.slug||''); b.textContent='✅'; setTimeout(()=>b.textContent='📋',800);}catch{}
    });
  });

  // ===== предпросмотр =====
  const pvModal = $('#pvModal'), pvFrame = $('#pvFrame'), pvWrap = $('#pvWrap');
  const pvDark  = $('#pvDark'), pvWidth = $('#pvWidth'), pvClose = $('#pvClose');

  const LOCAL_ASSETS = {
    tailwind: @json(local_css('tailwind.min.css')),
    fa: @json(local_css('font-awesome/all.min.css'))
  };
  function iconCdn(){ return `<link rel="stylesheet" href="${LOCAL_ASSETS.fa}">`; }
  function buildSrcDoc(html, dark=false){
    const safe = (html||'').replace(/<script[\s\S]*?<\/script>/gi,'');
    return `<!doctype html><html class="${dark?'dark':''}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1" />
<link href="${LOCAL_ASSETS.tailwind}" rel="stylesheet">
${iconCdn()}
</head><body class="p-4">${safe}</body></html>`;
  }
  function openPreview(templateId){
    const tpl = document.getElementById(templateId);
    if(!tpl) return;
    pvFrame.srcdoc = buildSrcDoc(tpl.innerHTML, pvDark.checked);
    pvModal.classList.remove('hidden'); pvModal.classList.add('flex');
  }
  function closePreview(){ pvModal.classList.add('hidden'); pvModal.classList.remove('flex'); }

  $$('.preview-btn').forEach(btn=>{
    btn.addEventListener('click', ()=> openPreview(btn.dataset.frag));
  });
  pvClose.addEventListener('click', closePreview);
  pvDark.addEventListener('change', ()=>{
    const doc = pvFrame.contentDocument; if(!doc) return;
    pvFrame.srcdoc = buildSrcDoc(doc.body.innerHTML, pvDark.checked);
  });
  pvWidth.addEventListener('change', ()=>{
    const v = pvWidth.value;
    pvWrap.style.width = (v==='full') ? '100%' : (v+'px');
  });
</script>
@endsection
