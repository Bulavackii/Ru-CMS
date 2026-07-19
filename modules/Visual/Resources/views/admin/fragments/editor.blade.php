@extends('layouts.admin')
@section('title', $fragment->exists ? 'Редактировать фрагмент' : 'Создать фрагмент')

@section('content')
@php
    $isSystem = in_array($fragment->slug, ['site-header','site-footer'], true);
    $themeCfg = ($activeTheme->config ?? []);
    $iconMode = data_get($themeCfg, 'icon_mode', 'fa');                 // fa | bootstrap | remix | tabler | lucide | svg
    $iconsPath = rtrim((string) data_get($themeCfg, 'icons_path', ''), '/'); // /storage/themes/{id}/icons
    $tokens = $activeTheme->tokens ?? [];

    $fontBase = data_get($tokens, 'font.base', 'Inter, system-ui, sans-serif');
    $radiusMd = data_get($tokens, 'radius.md', '12px');
    $cBg      = data_get($tokens, 'colors.bg',      '#ffffff');
    $cText    = data_get($tokens, 'colors.text',    '#111827');
    $cPrimary = data_get($tokens, 'colors.primary', '#2563eb');
    $cAccent  = data_get($tokens, 'colors.accent',  '#10b981');
    $cHeader  = data_get($tokens, 'colors.header',  '#ffffff');
    $cFooter  = data_get($tokens, 'colors.footer',  '#ffffff');

    $draftKey = 'fragment_draft_' . ($fragment->id ?: 'new');
@endphp

<h1 class="text-2xl font-bold mb-6">
  {{ $fragment->exists ? '🧩 Редактировать фрагмент' : '🧩 Создать фрагмент' }}
</h1>

@if ($errors->any())
  <div class="mb-4 p-3 bg-red-50 text-red-700 rounded">
    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form id="fragmentForm" method="POST"
      action="{{ $fragment->exists ? route('admin.visual.fragments.update', $fragment) : route('admin.visual.fragments.store') }}"
      class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
  @csrf
  @if ($fragment->exists) @method('PUT') @endif
  <input type="hidden" name="type" value="{{ old('type', $fragment->type ?: 'html') }}">

  {{-- Левая колонка: мета --}}
  <div class="space-y-4">
    <div>
      <label class="block text-sm mb-1">Название</label>
      <input type="text" name="title" class="border rounded px-3 py-2 w-full"
             value="{{ old('title', $fragment->title) }}" required>
    </div>

    <div>
      <label class="block text-sm mb-1">Slug</label>
      <input type="text" name="slug" class="border rounded px-3 py-2 w-full"
             value="{{ old('slug', $fragment->slug) }}" {{ $isSystem ? 'readonly' : '' }} required>
      @if ($isSystem)
        <p class="text-xs text-gray-500 mt-1">Системный фрагмент — slug изменять нельзя.</p>
      @endif
    </div>

    <div>
      <label class="block text-sm mb-1">Зона</label>
      <select name="zone" class="border rounded px-3 py-2 w-full" {{ $isSystem ? 'disabled' : '' }}>
        <option value="">—</option>
        <option value="header" @selected(old('zone', $fragment->zone)==='header')>Header</option>
        <option value="footer" @selected(old('zone', $fragment->zone)==='footer')>Footer</option>
        <option value="custom" @selected(old('zone', $fragment->zone)==='custom')>Custom</option>
      </select>
      @if ($isSystem)
        <input type="hidden" name="zone" value="{{ $fragment->slug === 'site-header' ? 'header':'footer' }}">
      @endif
    </div>

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_active" value="1" class="rounded border-gray-400"
             @checked(old('is_active', $fragment->is_active ?? true))>
      <span class="text-sm">Активен</span>
    </label>

    {{-- Черновик / автосейв --}}
    <div class="flex flex-wrap gap-2 items-center">
      <button type="button" id="saveDraft" class="px-3 py-1.5 rounded border">Сохранить</button>
      <button type="button" id="loadDraft" class="px-3 py-1.5 rounded border">Восстановить</button>
      <button type="button" id="clearDraft" class="px-3 py-1.5 rounded border">Очистить</button>
      <span id="autosaveBadge" class="text-xs text-gray-500 ml-1">Автосохранение: выкл.</span>
    </div>

    {{-- Быстрые пресеты --}}
    @if (!$fragment->exists)
      <div class="text-sm text-gray-600 space-x-2">
        Быстро создать:
        <a class="text-blue-600 underline" href="{{ route('admin.visual.fragments.create',['preset'=>'header']) }}">Шапка</a>
        <a class="text-blue-600 underline" href="{{ route('admin.visual.fragments.create',['preset'=>'footer']) }}">Подвал</a>
      </div>
    @endif

    {{-- Памятка по токенам темы --}}
    <div class="text-xs">
      <div class="font-semibold mb-1">Токены темы</div>
      <div class="grid grid-cols-2 gap-2">
        <button type="button" class="copy-var flex items-center gap-2 px-2 py-1 border rounded" data-var="--color-primary">
          <span class="w-4 h-4 rounded" style="background: {{ $cPrimary }}"></span> --color-primary
        </button>
        <button type="button" class="copy-var flex items-center gap-2 px-2 py-1 border rounded" data-var="--radius-md">
          <span class="w-4 h-4 rounded bg-gray-200"></span> --radius-md
        </button>
        <button type="button" class="copy-var flex items-center gap-2 px-2 py-1 border rounded" data-var="--color-text">
          <span class="w-4 h-4 rounded" style="background: {{ $cText }}"></span> --color-text
        </button>
        <button type="button" class="copy-var flex items-center gap-2 px-2 py-1 border rounded" data-var="--color-bg">
          <span class="w-4 h-4 rounded" style="background: {{ $cBg }}"></span> --color-bg
        </button>
      </div>
      <p class="text-gray-500 mt-2">Клик по карточке — скопирует имя переменной в буфер.</p>
    </div>

    {{-- Подсказка (экранируем директиву) --}}
    <div class="text-xs text-gray-500">
      В HTML можно использовать иконки классами (FA/BI/RI/TI) или Lucide
      (<code>&lt;i data-lucide="heart"&gt;</code>). Для Blade используйте
      <code>@@themeIcon('heart','w-5')</code>.
    </div>
  </div>

  {{-- Центральная/правая колонка --}}
  <div class="2xl:col-span-2 space-y-4">

    {{-- Вставки/сниппеты --}}
    <div class="flex flex-wrap gap-2 items-center">
      <div class="font-semibold mr-2">Быстрые вставки:</div>
      <button type="button" id="btnIcon"  class="px-3 py-1.5 rounded border">Иконка</button>
      <button type="button" id="btnBtn"   class="px-3 py-1.5 rounded border">Кнопка</button>
      <button type="button" id="btnWrap"  class="px-3 py-1.5 rounded border">Карточка</button>
      <button type="button" id="btnHero"  class="px-3 py-1.5 rounded border">Hero</button>
      <button type="button" id="btnAlert" class="px-3 py-1.5 rounded border">Алерт</button>
      <button type="button" id="btnGrid"  class="px-3 py-1.5 rounded border">Grid 3</button>

      <span class="inline-block w-px h-5 bg-gray-300 mx-1"></span>
      @if ($fragment->exists)
        <form action="{{ route('admin.visual.fragments.rebuild',$fragment) }}" method="POST" class="inline">
          @csrf
          <button type="submit" class="px-3 py-1.5 rounded border">Пересобрать HTML</button>
        </form>
      @endif
    </div>

    {{-- TinyMCE --}}
    <div>
      <label class="block text-sm mb-1">Содержимое фрагмента</label>
      <textarea id="fragment-editor" name="html_cached" rows="20" class="border rounded w-full">
{{ old('html_cached', $fragment->html_cached) }}
      </textarea>
      <div class="text-xs text-gray-500 mt-1">Ctrl/Cmd+S — сохранить форму, Ctrl/Cmd+Enter — обновить предпросмотр.</div>
    </div>

    {{-- JSON поля --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <div class="flex items-center justify-between">
          <label class="block text-sm mb-1">Schema (JSON)</label>
          <span id="schemaState" class="text-xs text-gray-500">—</span>
        </div>
        <textarea id="schemaField" name="schema" rows="8" class="border rounded px-3 py-2 w-full font-mono" placeholder="{}">{{ old('schema', json_encode($fragment->schema ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
        <div class="mt-1 flex gap-2">
          <button type="button" id="fmtSchema" class="px-2 py-1 rounded border text-xs">Форматировать</button>
          <button type="button" id="clearSchema" class="px-2 py-1 rounded border text-xs">Очистить</button>
        </div>
      </div>
      <div>
        <div class="flex items-center justify-between">
          <label class="block text-sm mb-1">Data (JSON)</label>
          <span id="dataState" class="text-xs text-gray-500">—</span>
        </div>
        <textarea id="dataField" name="data" rows="8" class="border rounded px-3 py-2 w-full font-mono" placeholder="{}">{{ old('data', json_encode($fragment->data ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
        <div class="mt-1 flex gap-2">
          <button type="button" id="fmtData" class="px-2 py-1 rounded border text-xs">Форматировать</button>
          <button type="button" id="clearData" class="px-2 py-1 rounded border text-xs">Очистить</button>
        </div>
      </div>
    </div>

    {{-- Предпросмотр --}}
    <div class="space-y-2">
      <div class="flex flex-wrap items-center gap-2 justify-between">
        <div class="font-semibold">Предпросмотр</div>
        <div class="flex flex-wrap items-center gap-2">
          <label class="inline-flex items-center gap-2 text-sm">
            <input id="pvDark" type="checkbox" class="rounded border-gray-400">
            Dark
          </label>
          <select id="pvWidth" class="border rounded px-2 py-1 text-sm">
            <option value="375">Phone (375px)</option>
            <option value="768">Tablet (768px)</option>
            <option value="1024" selected>Desktop (1024px)</option>
            <option value="full">Full width</option>
          </select>
          <button type="button" id="pvRefresh" class="px-3 py-1.5 rounded border text-sm">Обновить</button>
        </div>
      </div>

      <div class="border rounded bg-white p-2">
        <div id="pvWrap" class="mx-auto" style="width:1024px; max-width:100%;">
          <iframe id="preview" class="w-full h-[520px] border rounded bg-white"></iframe>
        </div>
      </div>
    </div>

    <div class="flex gap-3 pt-1">
      <button class="bg-blue-600 text-white px-4 py-2 rounded">
        {{ $fragment->exists ? 'Сохранить' : 'Создать' }}
      </button>
    </div>
  </div>
</form>

{{-- Модальное окно: иконка --}}
<div id="iconModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-4">
    <h3 class="font-semibold mb-3">Вставить иконку</h3>
    <div class="grid grid-cols-1 gap-3">
      <div>
        <label class="text-sm mb-1 block">Набор</label>
        <select id="iconSet" class="border rounded px-3 py-2 w-full">
          @foreach(['fa'=>'Font Awesome','bootstrap'=>'Bootstrap Icons','tabler'=>'Tabler Icons','remix'=>'Remix Icons','lucide'=>'Lucide','svg'=>'Локальные SVG (ZIP)'] as $k=>$v)
            <option value="{{ $k }}" @selected($iconMode===$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-sm mb-1 block">Имя иконки (напр. heart)</label>
        <input id="iconName" type="text" class="border rounded px-3 py-2 w-full" placeholder="heart">
      </div>
      <div>
        <label class="text-sm mb-1 block">Классы (опционально)</label>
        <input id="iconClass" type="text" class="border rounded px-3 py-2 w-full" placeholder="w-5 h-5 align-text-bottom">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button id="iconCancel" type="button" class="px-3 py-1.5 rounded border">Отмена</button>
        <button id="iconInsert" type="button" class="px-3 py-1.5 rounded bg-blue-600 text-white">Вставить</button>
      </div>
      @if($iconsPath)
        <p class="text-xs text-gray-500">SVG берутся из: {{ $iconsPath }}/<em>name</em>.svg</p>
      @endif
    </div>
  </div>
</div>
@endsection

@section('scripts')
  {{-- TinyMCE 8 (локально) --}}
  <script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>

  <script>
    // ====== тема и набор иконок из PHP ======
    const THEME_VARS = {
      fontBase:  @json($fontBase), radiusMd: @json($radiusMd),
      cBg: @json($cBg), cText: @json($cText), cPrimary:@json($cPrimary),
      cAccent:@json($cAccent), cHeader:@json($cHeader), cFooter:@json($cFooter)
    };
    const ICON_MODE  = @json($iconMode);
    const ICONS_PATH = @json($iconsPath);
    const DRAFT_KEY  = @json($draftKey);
    
    // ====== локальные пути к ресурсам ======
    const LOCAL_ASSETS = {
      tailwind: @json(local_css('tailwind.min.css')),
      icons: {
        bootstrap: @json(local_css('bootstrap-icons.css')),
        remix: @json(local_css('remixicon.css')),
        tabler: @json(local_css('tabler-icons.min.css')),
        lucide: @json(local_js('lucide.min.js')),
        fa: @json(local_css('font-awesome/all.min.css')),
      }
    };

    // ====== TinyMCE ======
    tinymce.init({
      selector: '#fragment-editor',
      height: 520,
      plugins: 'code link image media table lists advlist fullscreen preview anchor charmap emoticons visualblocks',
      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | ' +
               'alignleft aligncenter alignright | bullist numlist | link image media table | code fullscreen preview',
      menubar: 'file edit view insert format tools table help',
      branding: false,
      license_key: 'gpl',
      relative_urls: false,
      convert_urls: false,
      images_upload_url: '{{ route('admin.visual.upload.image') }}',
      automatic_uploads: true,
      file_picker_types: 'image media',
      setup: (ed)=>{
        const sync = ()=> updatePreview();
        ed.on('init change keyup Undo Redo', sync);

        // hotkeys
        ed.addShortcut('meta+s', 'Save form', ()=>{ document.getElementById('fragmentForm').requestSubmit(); });
        ed.addShortcut('meta+enter','Refresh preview', ()=> updatePreview());
      }
    });

    // ====== utils ======
    const $ = (sel,root=document)=>root.querySelector(sel);
    const $$ = (sel,root=document)=>[...root.querySelectorAll(sel)];
    const debounce = (fn,ms=300)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); } };
    const copy = async (text)=>{ try{ await navigator.clipboard.writeText(text); }catch{} };

    // ====== иконки ======
    function iconHTML(set, name, cls=''){
      name = (name||'').trim();
      cls  = (cls||'').trim();
      if(!name) return '';
      switch(set){
        case 'fa':        return `<i class="fa-solid fa-${name} ${cls}"></i>`;
        case 'bootstrap': return `<i class="bi bi-${name} ${cls}"></i>`;
        case 'remix':     return `<i class="ri-${name}-line ${cls}"></i>`;
        case 'tabler':    return `<i class="ti ti-${name} ${cls}"></i>`;
        case 'lucide':    return `<i data-lucide="${name}" class="${cls}"></i>`;
        case 'svg':       return ICONS_PATH ? `<img src="${ICONS_PATH.replace(/\/$/,'')}/${name}.svg" class="${cls}" alt="">` : `<span>[svg:${name}]</span>`;
      }
      return '';
    }

    // ====== сниппеты ======
    function insert(html){ tinymce.activeEditor?.execCommand('mceInsertContent', false, html); }
    $('#btnIcon').addEventListener('click', ()=>openIconModal());
    $('#btnBtn').addEventListener('click', ()=>{
      insert(`<a href="#" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded" style="background:var(--color-primary)">Кнопка</a>`);
    });
    $('#btnWrap').addEventListener('click', ()=>{
      const sel = tinymce.activeEditor?.selection.getContent() || 'Карточка';
      insert(`<div class="rounded-md p-4 shadow border bg-white/90 dark:bg-gray-900/90" style="border-radius:var(--radius-md)">${sel}</div>`);
    });
    $('#btnHero').addEventListener('click', ()=>{
      insert(`<section class="hero text-white text-center p-16 rounded-md" style="background:linear-gradient(45deg,var(--color-primary),var(--color-accent)); border-radius:var(--radius-md)">
  <h1 class="text-3xl font-bold mb-2">Заголовок</h1>
  <p class="opacity-90 mb-4">Описание секции</p>
  <a href="#" class="bg-white text-gray-800 rounded px-4 py-2 inline-block">Действие</a>
</section>`);
    });
    $('#btnAlert').addEventListener('click', ()=>{
      insert(`<div class="rounded-md p-3 border" style="background:#ecfdf5; border-color:#a7f3d0; color:#065f46">Успех: всё прошло отлично!</div>`);
    });
    $('#btnGrid').addEventListener('click', ()=>{
      insert(`<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="p-4 border rounded-md">Элемент 1</div>
  <div class="p-4 border rounded-md">Элемент 2</div>
  <div class="p-4 border rounded-md">Элемент 3</div>
</div>`);
    });

    // ====== модалка иконок ======
    const modal = $('#iconModal');
    function openIconModal(){ modal.classList.remove('hidden'); modal.classList.add('flex'); $('#iconName').focus(); $('#iconSet').value = ICON_MODE; }
    function closeIconModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
    $('#iconCancel').addEventListener('click', closeIconModal);
    $('#iconInsert').addEventListener('click', ()=>{
      const set = $('#iconSet').value || ICON_MODE, name = $('#iconName').value || 'heart', cls = $('#iconClass').value || 'w-5 h-5';
      insert(iconHTML(set,name,cls));
      closeIconModal(); updatePreview();
    });

    // ====== JSON format/validate ======
    const schemaField = $('#schemaField'), dataField = $('#dataField');
    const schemaState = $('#schemaState'), dataState = $('#dataState');

    function validateJSON(el, labelEl){
      const t = el.value.trim();
      if(!t){ labelEl.textContent='пусто'; labelEl.className='text-xs text-gray-400'; return true; }
      try{ JSON.parse(t); labelEl.textContent='ok'; labelEl.className='text-xs text-green-600'; return true; }
      catch(e){ labelEl.textContent='ошибка'; labelEl.className='text-xs text-red-600'; return false; }
    }
    const fmt = (el)=>{ try{ el.value = JSON.stringify(el.value.trim()?JSON.parse(el.value):{}, null, 2); }catch{} };

    $('#fmtSchema').addEventListener('click', ()=>fmt(schemaField));
    $('#fmtData').addEventListener('click', ()=>fmt(dataField));
    $('#clearSchema').addEventListener('click', ()=>{ schemaField.value=''; validateJSON(schemaField, schemaState); });
    $('#clearData').addEventListener('click', ()=>{ dataField.value=''; validateJSON(dataField, dataState); });

    schemaField.addEventListener('input', ()=>validateJSON(schemaField, schemaState));
    dataField.addEventListener('input',  ()=>validateJSON(dataField,  dataState));
    validateJSON(schemaField, schemaState); validateJSON(dataField, dataState);

    // ====== предпросмотр (iframe srcdoc) ======
    const preview = $('#preview'), pvWrap = $('#pvWrap');
    const pvDark  = $('#pvDark'), pvWidth = $('#pvWidth'), pvRefresh = $('#pvRefresh');

    function iconCdn(mode){
      switch(mode){
        case 'bootstrap': return `<link rel="stylesheet" href="${LOCAL_ASSETS.icons.bootstrap}">`;
        case 'remix':     return `<link rel="stylesheet" href="${LOCAL_ASSETS.icons.remix}">`;
        case 'tabler':    return `<link rel="stylesheet" href="${LOCAL_ASSETS.icons.tabler}">`;
        case 'lucide':    return `<script src="${LOCAL_ASSETS.icons.lucide}"></script>`;
        case 'fa':        return `<link rel="stylesheet" href="${LOCAL_ASSETS.icons.fa}">`;
        default:          return ''; // svg — не нужен CDN
      }
    }

    function buildSrcDoc(content, dark=false){
      const vars = `--font-base:${THEME_VARS.fontBase};--radius-md:${THEME_VARS.radiusMd};--color-bg:${THEME_VARS.cBg};--color-text:${THEME_VARS.cText};--color-primary:${THEME_VARS.cPrimary};--color-accent:${THEME_VARS.cAccent};--color-header:${THEME_VARS.cHeader};--color-footer:${THEME_VARS.cFooter};`;
      const safe = (content||'').replace(/<script[\s\S]*?<\/script>/gi,'');
      return `<!DOCTYPE html><html class="${dark?'dark':''}"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1" />
<link href="${LOCAL_ASSETS.tailwind}" rel="stylesheet">
${iconCdn(ICON_MODE) || iconCdn('fa')}
<style>
:root{ ${vars} }
body{ font-family:var(--font-base); color:var(--color-text); }
.rounded,.rounded-md,.rounded-lg,.rounded-xl,.rounded-2xl{ border-radius: var(--radius-md) !important; }
.dark .bg-white\\/90{ background-color: rgba(17,24,39,0.9) !important; }
</style>
</head><body>
<div class="p-4">
${safe || '<div class="text-gray-400">Пока пусто…</div>'}
</div>
<script> if(window.lucide){ try{ window.lucide.createIcons(); }catch(e){} } </script>
</body></html>`;
    }

    function updatePreview(){
      const html = tinymce.get('fragment-editor')?.getContent() || '';
      preview.srcdoc = buildSrcDoc(html, pvDark.checked);
    }
    pvRefresh.addEventListener('click', updatePreview);
    pvDark.addEventListener('change', updatePreview);
    pvWidth.addEventListener('change', ()=>{
      const v = pvWidth.value;
      pvWrap.style.width = (v==='full') ? '100%' : (v+'px');
      updatePreview();
    });

    // первичная отрисовка
    document.addEventListener('DOMContentLoaded', ()=>{ updatePreview(); });

    // ====== черновик (ручной + автосейв) ======
    const form = $('#fragmentForm');
    function getFormData(){
      return {
        title: form.title.value,
        slug:  form.slug.value,
        zone:  form.zone ? form.zone.value : '',
        is_active: form.is_active.checked ? 1 : 0,
        html_cached: tinymce.get('fragment-editor')?.getContent() || '',
        schema: schemaField.value,
        data:   dataField.value,
      };
    }
    $('#saveDraft').addEventListener('click', ()=>{
      localStorage.setItem(DRAFT_KEY, JSON.stringify(getFormData()));
      $('#autosaveBadge').textContent = 'Сохранено (вручную)';
    });
    $('#loadDraft').addEventListener('click', ()=>{
      const raw = localStorage.getItem(DRAFT_KEY);
      if(!raw) return alert('Черновик не найден.');
      try{
        const d = JSON.parse(raw);
        form.title.value = d.title||''; form.slug.value = d.slug||'';
        if(form.zone) form.zone.value = d.zone||'';
        form.is_active.checked = !!(+d.is_active);
        tinymce.get('fragment-editor')?.setContent(d.html_cached||'');
        schemaField.value = d.schema||''; dataField.value = d.data||'';
        validateJSON(schemaField, schemaState); validateJSON(dataField, dataState);
        updatePreview();
      }catch(e){ alert('Не удалось прочитать черновик.'); }
    });
    $('#clearDraft').addEventListener('click', ()=>{ localStorage.removeItem(DRAFT_KEY); $('#autosaveBadge').textContent = 'Черновик удалён'; });

    // автосейв
    const autosave = debounce(()=>{
      localStorage.setItem(DRAFT_KEY, JSON.stringify(getFormData()));
      const dt = new Date(); $('#autosaveBadge').textContent = 'Автосохранено: ' + dt.toLocaleTimeString();
    }, 3000);
    ['input','change','keyup'].forEach(ev=>{
      document.addEventListener(ev, autosave, {capture:true});
    });

    // уход со страницы
    let pristine = JSON.stringify(getFormData());
    window.addEventListener('beforeunload', (e)=>{
      const now = JSON.stringify(getFormData());
      if(now !== pristine){ e.preventDefault(); e.returnValue = ''; }
    });
    form.addEventListener('submit', ()=>{ pristine = JSON.stringify(getFormData()); });

    // копирование токена
    $$('.copy-var').forEach(btn=>{
      btn.addEventListener('click', async ()=>{ await copy(btn.dataset.var); btn.classList.add('ring','ring-blue-300'); setTimeout(()=>btn.classList.remove('ring','ring-blue-300'),500); });
    });
  </script>
@endsection
