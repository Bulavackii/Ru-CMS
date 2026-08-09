@extends('layouts.admin')
@section('title', $fragment->exists ? 'Редактировать фрагмент' : 'Создать фрагмент')

@section('content')

@push('styles')
<style>
    /* ── Редактор фрагментов ─────────────────────────────────────────────
       Литеральный CSS, а не Tailwind-утилиты: в собранном tailwind.min.css
       этого проекта нет ни прозрачности через дробь, ни произвольных
       значений, ни варианта peer-checked — см. памятку проекта. Поля здесь
       раньше были набраны как `border rounded px-3 py-2`, то есть без
       подсветки при фокусе и с скруглением, которое всё равно снимает
       глобальное правило admin-sharp. */

    /* Две колонки — литеральным CSS.

       Сначала я задал их Tailwind-классом с произвольным значением
       (xl:grid-cols-[20rem_minmax(0,1fr)]) и наступил на мину, описанную в
       памятке этого же проекта: в собранном tailwind.min.css произвольных
       значений НЕТ ВООБЩЕ. Класс молча не применился, и форма осталась в
       одну колонку — ровно та жалоба, с которой всё началось.
       Правило: любая утилита сложнее базовой проверяется грепом по сборке. */
    .frg-grid{ display:grid; grid-template-columns:1fr; gap:1.5rem; align-items:start }

    @media (min-width:1280px){
        .frg-grid{ grid-template-columns:20rem minmax(0, 1fr) }
        /* Липкой колонка становится только когда она РЯДОМ с содержимым:
           в одну колонку прилипание мешало бы прокрутке.

           Высоту обязательно ограничиваем окном. Прилипающий блок ВЫШЕ окна
           ведёт себя противно: он упирается в верх и дальше просто не
           двигается, а его нижняя часть остаётся недостижимой (здесь было
           825px при окне 720px). Поэтому лишнее прокручивается внутри самой
           колонки. */
        .frg-aside{ position:sticky; top:calc(var(--admin-header-h, 60px) + 1.25rem);
            max-height:calc(100vh - var(--admin-header-h, 60px) - 2.5rem);
            overflow-y:auto; scrollbar-gutter:stable }
    }

    /* h-[520px] — тоже произвольное значение, тоже не существует в сборке. */
    .frg-preview{ height:520px }

    .frg-h{ font-size:.7rem; font-weight:700; letter-spacing:.06em;
        text-transform:uppercase; color:#6b7280 }
    .dark .frg-h{ color:#9ca3af }

    .frg-label{ display:block; margin-bottom:.25rem; font-size:.875rem;
        font-weight:500; color:#374151 }
    .dark .frg-label{ color:#d1d5db }

    /* Поля ввода */
    .frg-input{ display:block; width:100%; padding:.5rem .75rem; font-size:.875rem;
        color:#111827; background:#fff; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s }
    .frg-input:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .frg-input[readonly], .frg-input:disabled{ background:#f3f4f6; color:#6b7280; cursor:not-allowed }
    .frg-input--sm{ width:auto; padding:.3rem .5rem; font-size:.75rem }
    .dark .frg-input{ color:#f3f4f6; background:#111827; border-color:#374151 }
    .dark .frg-input[readonly], .dark .frg-input:disabled{ background:#1f2937; color:#9ca3af }

    /* Поля с кодом */
    .frg-code{ display:block; width:100%; padding:.6rem .75rem; font-size:.8rem; line-height:1.6;
        font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        color:#111827; background:#f9fafb; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s; resize:vertical }
    .frg-code:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .dark .frg-code{ color:#e5e7eb; background:#0f172a; border-color:#374151 }

    /* Мелкие кнопки-инструменты */
    .frg-mini{ display:inline-flex; align-items:center; justify-content:center; gap:.35rem;
        padding:.35rem .6rem; font-size:.75rem; font-weight:600; white-space:nowrap;
        color:#374151; background:#fff; border:1px solid #d1d5db; cursor:pointer;
        text-decoration:none; transition:background-color .15s, border-color .15s, color .15s }
    .frg-mini:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
    .dark .frg-mini{ color:#d1d5db; background:#1f2937; border-color:#374151 }
    .dark .frg-mini:hover{ background:#374151 }

    /* Кнопки в шапке страницы */
    .frg-btn{ display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .8rem;
        font-size:.875rem; font-weight:600; white-space:nowrap;
        color:#374151; background:transparent; border:1px solid #d1d5db; cursor:pointer;
        text-decoration:none; transition:background-color .15s, border-color .15s, color .15s }
    .frg-btn:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
    .dark .frg-btn{ color:#e5e7eb; border-color:#4b5563 }
    .dark .frg-btn:hover{ background:#374151 }

    /* Надпись на акценте берёт цвет через readable_ink: акценты тем очень
       разной яркости, и белым по светло-мятному не прочитать. */
    .frg-btn--primary{ color:var(--admin-on-primary, #fff);
        background:var(--admin-primary); border-color:var(--admin-primary) }
    .frg-btn--primary:hover{ color:var(--admin-on-primary, #fff); filter:brightness(1.08);
        background:var(--admin-primary); border-color:var(--admin-primary) }
    .dark .frg-btn--primary{ color:var(--admin-on-primary, #fff) }

    /* Полоса-заголовок карточки с инструментами */
    .frg-bar{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem;
        padding:.7rem 1rem; border-bottom:1px solid #e5e7eb; background:#f9fafb }
    .dark .frg-bar{ border-bottom-color:#374151; background:#111827 }

    /* Токены темы */
    .frg-token{ display:flex; align-items:center; gap:.5rem; min-width:0;
        padding:.35rem .5rem; font-size:.7rem; font-family:ui-monospace, Menlo, Consolas, monospace;
        color:#374151; background:#fff; border:1px solid #e5e7eb; cursor:pointer;
        transition:border-color .15s, background-color .15s }
    .frg-token:hover{ border-color:var(--admin-primary); background:#f9fafb }
    .dark .frg-token{ color:#d1d5db; background:#1f2937; border-color:#374151 }
    .dark .frg-token:hover{ background:#374151 }

    .frg-swatch{ width:1rem; height:1rem; flex:none; border:1px solid rgba(17,24,39,.15) }
    .frg-swatch--plain{ background:#e5e7eb }
    .dark .frg-swatch{ border-color:rgba(255,255,255,.2) }

    /* Раскрывающиеся блоки: значок-стрелка у сводки */
    details > summary{ list-style:none }
    details > summary::-webkit-details-marker{ display:none }
    details > summary::before{ content:'\25B8'; display:inline-block; margin-right:.4rem;
        transition:transform .15s }
    details[open] > summary::before{ transform:rotate(90deg) }
</style>
@endpush


@php
    $isSystem = in_array($fragment->slug, ['site-header','site-footer'], true);
    $themeCfg = ($activeTheme->config ?? []);
    $iconMode = data_get($themeCfg, 'icon_mode', 'lucide');             // fa | bootstrap | remix | tabler | lucide | svg
    $iconsPath = rtrim((string) data_get($themeCfg, 'icons_path', ''), '/'); // /storage/themes/{id}/icons
    $tokens = $activeTheme->tokens ?? [];

    $fontBase = data_get($tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
    $radiusMd = data_get($tokens, 'radius.md', '12px');
    $cBg      = data_get($tokens, 'colors.bg',      '#ffffff');
    $cText    = data_get($tokens, 'colors.text',    '#111827');
    $cPrimary = data_get($tokens, 'colors.primary', '#2563eb');
    $cAccent  = data_get($tokens, 'colors.accent',  '#10b981');
    $cHeader  = data_get($tokens, 'colors.header',  '#ffffff');
    $cFooter  = data_get($tokens, 'colors.footer',  '#ffffff');

    $draftKey = 'fragment_draft_' . ($fragment->id ?: 'new');
@endphp

<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div class="flex items-center gap-3 min-w-0">
    <span class="admin-icon-badge"><i class="fas fa-puzzle-piece"></i></span>
    <div class="min-w-0">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">
        {{ $fragment->exists ? 'Редактировать фрагмент' : 'Новый фрагмент' }}
      </h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
        {{ $fragment->exists ? $fragment->title . ' · ' . $fragment->zoneLabel() : 'Блок, который выводится в выбранной зоне страницы' }}
      </p>
    </div>
  </div>

  {{-- Кнопка сохранения переехала сюда из самого низа страницы. Там она
       стояла ПОСЛЕ предпросмотра и полей JSON, то есть чтобы сохранить
       правку, надо было проматывать всю страницу вниз — а выше по дороге
       попадалась кнопка «Сохранить» у черновика, которая сохраняет совсем
       другое. Атрибут form= позволяет держать её вне тега формы. --}}
  <div class="flex items-center gap-2 flex-shrink-0">
    @if ($fragment->exists)
      <a href="{{ route('admin.visual.fragments.history', $fragment) }}" class="frg-btn" title="История версий">
        <i class="fas fa-clock-rotate-left"></i>
        <span class="hidden sm:inline">История</span>
      </a>
      <form action="{{ route('admin.visual.fragments.rebuild', $fragment) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="frg-btn" title="Собрать HTML заново из данных фрагмента">
          <i class="fas fa-arrows-rotate"></i>
          <span class="hidden lg:inline">Пересобрать</span>
        </button>
      </form>
    @endif
    <a href="{{ route('admin.visual.fragments.index') }}" class="frg-btn">
      <i class="fas fa-arrow-left"></i>
      <span class="hidden sm:inline">К списку</span>
    </a>
    <button type="submit" form="fragmentForm" class="frg-btn frg-btn--primary">
      <i class="fas fa-floppy-disk"></i>
      {{ $fragment->exists ? 'Сохранить' : 'Создать' }}
    </button>
  </div>
</div>

@if ($errors->any())
  <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
    <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form id="fragmentForm" method="POST"
      action="{{ $fragment->exists ? route('admin.visual.fragments.update', $fragment) : route('admin.visual.fragments.store') }}"
      class="frg-grid">
  @csrf
  @if ($fragment->exists) @method('PUT') @endif
  <input type="hidden" name="type" value="{{ old('type', $fragment->type ?: 'html') }}">

  {{-- ── Левая колонка: свойства фрагмента ──────────────────────────────
       Липкая: содержимое справа длинное, и при прокрутке к предпросмотру
       поля названия и зоны уезжали из виду. --}}
  <aside class="frg-aside space-y-4">

    <section class="admin-card p-4 space-y-4">
      <h2 class="frg-h">Свойства</h2>

      <div>
        <label class="frg-label">Название</label>
        <input type="text" name="title" class="frg-input" required
               value="{{ old('title', $fragment->title) }}">
      </div>

      <div>
        <label class="frg-label">Slug</label>
        <input type="text" name="slug" class="frg-input font-mono text-sm" required
               value="{{ old('slug', $fragment->slug) }}" {{ $isSystem ? 'readonly' : '' }}>
        @if ($isSystem)
          <p class="admin-hint mt-1"><i class="fas fa-lock mr-1"></i>Системный фрагмент — slug изменять нельзя.</p>
        @endif
      </div>

      <div>
        <label class="frg-label">Зона</label>
        <select name="zone" class="frg-input" {{ $isSystem ? 'disabled' : '' }}>
          <option value="">— не выбрана —</option>
          @foreach(\Modules\Visual\Models\Fragment::ZONE_LABELS as $zoneValue => $zoneLabel)
            <option value="{{ $zoneValue }}" @selected(old('zone', $fragment->zone) === $zoneValue)>{{ $zoneLabel }}</option>
          @endforeach
        </select>
        @if ($isSystem)
          <input type="hidden" name="zone" value="{{ $fragment->slug === 'site-header' ? 'header':'footer' }}">
        @endif
        <p class="admin-hint mt-1">Место на странице, куда выводится блок.</p>
      </div>

      {{-- Тумблер вместо голой галочки: в этой сборке Tailwind нет варианта
           peer-checked:, поэтому по всей панели используется .admin-toggle. --}}
      <div class="flex items-center gap-3 pt-1">
        <label class="admin-toggle">
          <input type="checkbox" name="is_active" value="1"
                 @checked(old('is_active', $fragment->is_active ?? true))>
          <span class="track"></span>
          <span class="knob"></span>
        </label>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Активен</span>
      </div>
    </section>

    {{-- ── Черновик ───────────────────────────────────────────────────
         Отдельной карточкой и с другими подписями по делу: кнопки назывались
         «Сохранить», «Восстановить», «Очистить» и стояли ВЫШЕ настоящей
         кнопки сохранения формы. «Сохранить» здесь НЕ сохраняет фрагмент —
         она кладёт копию в память браузера, и перепутать их было проще
         простого. --}}
    <section class="admin-card p-4 space-y-3">
      <h2 class="frg-h">Черновик в браузере</h2>
      <p class="admin-hint">
        Копия формы в этом браузере. На сервер не отправляется — фрагмент
        сохраняет кнопка вверху страницы.
      </p>
      <div class="grid grid-cols-3 gap-2">
        <button type="button" id="saveDraft" class="frg-mini"><i class="fas fa-bookmark"></i> Запомнить</button>
        <button type="button" id="loadDraft" class="frg-mini"><i class="fas fa-rotate-left"></i> Вернуть</button>
        <button type="button" id="clearDraft" class="frg-mini"><i class="fas fa-eraser"></i> Забыть</button>
      </div>
      <span id="autosaveBadge" class="block text-xs text-gray-500 dark:text-gray-400">Автосохранение: выкл.</span>
    </section>

    @if (!$fragment->exists)
      <section class="admin-card p-4 space-y-2">
        <h2 class="frg-h">Быстро создать</h2>
        <div class="grid grid-cols-2 gap-2">
          <a class="frg-mini" href="{{ route('admin.visual.fragments.create',['preset'=>'header']) }}">
            <i class="fas fa-window-maximize"></i> Шапка
          </a>
          <a class="frg-mini" href="{{ route('admin.visual.fragments.create',['preset'=>'footer']) }}">
            <i class="fas fa-window-minimize"></i> Подвал
          </a>
        </div>
      </section>
    @endif

    <section class="admin-card p-4 space-y-3">
      <h2 class="frg-h">Токены темы</h2>
      <div class="grid grid-cols-2 gap-2">
        <button type="button" class="copy-var frg-token" data-var="--color-primary">
          <span class="frg-swatch" style="background: {{ $cPrimary }}"></span>
          <span class="truncate">--color-primary</span>
        </button>
        <button type="button" class="copy-var frg-token" data-var="--radius-md">
          <span class="frg-swatch frg-swatch--plain"></span>
          <span class="truncate">--radius-md</span>
        </button>
        <button type="button" class="copy-var frg-token" data-var="--color-text">
          <span class="frg-swatch" style="background: {{ $cText }}"></span>
          <span class="truncate">--color-text</span>
        </button>
        <button type="button" class="copy-var frg-token" data-var="--color-bg">
          <span class="frg-swatch" style="background: {{ $cBg }}"></span>
          <span class="truncate">--color-bg</span>
        </button>
      </div>
      <p class="admin-hint">Клик по токену копирует имя переменной в буфер.</p>
    </section>

    {{-- Подсказка про значки нужна раз в жизни, а места занимала как поле
         ввода — убрана под раскрытие. --}}
    <details class="admin-card p-4">
      <summary class="frg-h cursor-pointer">Значки в HTML</summary>
      <p class="admin-hint mt-3">
        Классами набора (FA/BI/RI/TI) либо Lucide:
        <code class="font-mono">&lt;i data-lucide="heart"&gt;</code>.
        В Blade — <code class="font-mono">@@themeIcon('heart','w-5')</code>.
      </p>
    </details>
  </aside>

  {{-- ── Правая колонка: содержимое ──────────────────────────────────── --}}
  <div class="space-y-5 min-w-0">

    {{-- Содержимое и панель вставок — ОДНОЙ карточкой. Раньше «Быстрые
         вставки» висели отдельной строкой над редактором и читались как
         часть шапки страницы, а не как его инструменты. --}}
    {{-- Без overflow:hidden — намеренно.

         Он превращает карточку в контейнер прокрутки, и прилипание панели
         инструментов редактора внутри неё перестаёт работать: панель просто
         уезжает вверх вместе со страницей. Скруглять тут всё равно нечего —
         глобальное правило admin-sharp снимает радиусы по всей панели. --}}
    <section class="admin-card">
      <header class="frg-bar">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 mr-1">Содержимое</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">вставить:</span>
        <button type="button" id="btnIcon"  class="frg-mini">Иконка</button>
        <button type="button" id="btnBtn"   class="frg-mini">Кнопка</button>
        <button type="button" id="btnWrap"  class="frg-mini">Карточка</button>
        <button type="button" id="btnHero"  class="frg-mini">Hero</button>
        <button type="button" id="btnAlert" class="frg-mini">Алерт</button>
        <button type="button" id="btnGrid"  class="frg-mini">Grid 3</button>
      </header>

      <div class="p-4">
        <x-ru-editor name="html_cached" id="fragment-editor" preset="page" :height="520"
                     :value="$fragment->html_cached" :body-class="''" :content-css="false" />
        <p class="admin-hint mt-2">
          Ctrl/Cmd+S — сохранить фрагмент, Ctrl/Cmd+Enter — обновить предпросмотр.
        </p>
      </div>
    </section>

    {{-- ── Предпросмотр: сразу под содержимым ────────────────────────────
         Стоял в самом низу, после полей JSON: чтобы увидеть результат
         правки, приходилось прокручивать мимо всего остального. --}}
    <section class="admin-card overflow-hidden">
      <header class="frg-bar justify-between">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Предпросмотр</span>
        <div class="flex flex-wrap items-center gap-2">
          <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
            <input id="pvDark" type="checkbox" class="border-gray-400"> тёмный фон
          </label>
          <select id="pvWidth" class="frg-input frg-input--sm">
            <option value="375">Телефон · 375</option>
            <option value="768">Планшет · 768</option>
            <option value="1024" selected>Экран · 1024</option>
            <option value="full">Во всю ширину</option>
          </select>
          <button type="button" id="pvRefresh" class="frg-mini"><i class="fas fa-rotate"></i> Обновить</button>
        </div>
      </header>

      <div class="p-3 bg-gray-100 dark:bg-gray-900">
        <div id="pvWrap" class="mx-auto" style="width:1024px; max-width:100%;">
          <iframe id="preview" class="frg-preview w-full border border-gray-300 dark:border-gray-700 bg-white"></iframe>
        </div>
      </div>
    </section>

    <section class="admin-card p-4 space-y-2">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Стили фрагмента (CSS)</label>
      <textarea name="css_inline" rows="8" class="frg-code"
                placeholder=".my-block{ padding: 1rem; }">{{ old('css_inline', $fragment->css_inline) }}</textarea>
      <p class="admin-hint">
        Оформление общее для всех языков — переводится только содержимое.
        Тег <span class="font-mono">&lt;style&gt;</span> писать не нужно.
      </p>
    </section>

    {{-- ── Данные фрагмента ──────────────────────────────────────────────
         Схема и данные нужны единицам фрагментов, а занимали два поля по
         восемь строк наравне с содержимым. Убраны под раскрытие. --}}
    <details class="admin-card">
      <summary class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
        Данные фрагмента (JSON)
        <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">— для параметризованных блоков</span>
      </summary>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 pt-0 border-t border-gray-200 dark:border-gray-700">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Schema</label>
            <span id="schemaState" class="text-xs text-gray-500">—</span>
          </div>
          <textarea id="schemaField" name="schema" rows="8" class="frg-code" placeholder="{}">{{ old('schema', json_encode($fragment->schema ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
          <div class="mt-2 flex gap-2">
            <button type="button" id="fmtSchema" class="frg-mini">Форматировать</button>
            <button type="button" id="clearSchema" class="frg-mini">Очистить</button>
          </div>
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Data</label>
            <span id="dataState" class="text-xs text-gray-500">—</span>
          </div>
          <textarea id="dataField" name="data" rows="8" class="frg-code" placeholder="{}">{{ old('data', json_encode($fragment->data ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
          <div class="mt-2 flex gap-2">
            <button type="button" id="fmtData" class="frg-mini">Форматировать</button>
            <button type="button" id="clearData" class="frg-mini">Очистить</button>
          </div>
        </div>
      </div>
    </details>

    {{-- Переводы контента на другие языки (content_translations) --}}
    <x-admin.translations :model="$fragment" :fields="['title' => 'Название', 'html_cached' => ['label' => 'Содержимое', 'type' => 'textarea']]" />
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
        <button id="iconInsert" type="button" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">Вставить</button>
      </div>
      @if($iconsPath)
        <p class="text-xs text-gray-500">SVG берутся из: {{ $iconsPath }}/<em>name</em>.svg</p>
      @endif
    </div>
  </div>
</div>
@endsection

{{-- Стек, а НЕ секция.

     Раньше этот блок объявлялся секцией с именем scripts. Макет панели
     собирает скрипты стеком того же имени, а секцию с ним не выводит вовсе —
     и блок не попадал на страницу НИ РАЗУ. Не работало ничего из
     интерактивного: предпросмотр, черновики, автосохранение, быстрые
     вставки, окно выбора значка, проверка JSON, сохранение по Ctrl+S.
     Со стороны это выглядело как «предпросмотр пустой».

     Проверено грепом: во всём проекте это была единственная вьюха, которая
     объявляла скрипты секцией, а не стеком.

     NB: названия директив здесь намеренно записаны словами. Blade
     компилирует файл построчно и про комментарии не знает — упомянутая
     буквально директива срабатывает по-настоящему и открывает лишний
     непарный блок. Эта грабля описана в памятке проекта, и я на неё
     только что наступил, набирая это самое пояснение. --}}
@push('scripts')

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
    function insert(html){ window.RuEditor?.active()?.insertHtml(html); }
    $('#btnIcon').addEventListener('click', ()=>openIconModal());
    $('#btnBtn').addEventListener('click', ()=>{
      insert(`<a href="#" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded" style="background:var(--color-primary)">Кнопка</a>`);
    });
    $('#btnWrap').addEventListener('click', ()=>{
      const sel = window.RuEditor?.active()?.getSelection().toString() || 'Карточка';
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
        // Закрывающий тег экранирован: без этого он обрывал бы <script>,
        // внутри которого лежит, и остаток скрипта уезжал бы на страницу
        // видимым текстом.
        case 'lucide':    return `<script src="${LOCAL_ASSETS.icons.lucide}"><\/script>`;
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
<script> if(window.lucide){ try{ window.lucide.createIcons(); }catch(e){} } <\/script>
</body></html>`;
    }

    function updatePreview(){
      const html = window.RuEditor?.get('fragment-editor')?.getContent() || '';
      preview.srcdoc = buildSrcDoc(html, pvDark.checked);
    }
    pvRefresh.addEventListener('click', updatePreview);
    pvDark.addEventListener('change', updatePreview);
    pvWidth.addEventListener('change', ()=>{
      const v = pvWidth.value;
      pvWrap.style.width = (v==='full') ? '100%' : (v+'px');
      updatePreview();
    });

    // Первичная отрисовка.
    //
    // Здесь стояло document.addEventListener('DOMContentLoaded', ...) — и
    // предпросмотр НИКОГДА не рисовался при открытии страницы: скрипт лежит в
    // конце тела, к моменту его выполнения документ уже complete, событие
    // давно прошло, обработчик не вызывался ни разу. В рамке не было даже
    // надписи «Пока пусто…» — атрибут srcdoc просто не задавался.
    //
    // Плюс редактор поднимается своим скриптом и на этот момент может быть
    // ещё не готов: рисуем и сразу, и повторно по его сигналу ready.
    function firstPreview(){
      updatePreview();

      const ed = window.RuEditor && window.RuEditor.get
        ? window.RuEditor.get('fragment-editor') : null;

      if (ed && typeof ed.on === 'function') {
        ed.on('ready', updatePreview);
        return;
      }

      // Редактора ещё нет — ждём его недолго, но не бесконечно.
      let tries = 0;
      const timer = setInterval(() => {
        const late = window.RuEditor && window.RuEditor.get
          ? window.RuEditor.get('fragment-editor') : null;

        if (late || ++tries > 40) {
          clearInterval(timer);
          if (late) { updatePreview(); }
        }
      }, 100);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', firstPreview);
    } else {
      firstPreview();
    }

    // ====== черновик (ручной + автосейв) ======
    const form = $('#fragmentForm');
    function getFormData(){
      return {
        title: form.title.value,
        slug:  form.slug.value,
        zone:  form.zone ? form.zone.value : '',
        is_active: form.is_active.checked ? 1 : 0,
        html_cached: window.RuEditor?.get('fragment-editor')?.getContent() || '',
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
        window.RuEditor?.get('fragment-editor')?.setContent(d.html_cached||'');
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
@endpush
