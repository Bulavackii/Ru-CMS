@extends('layouts.admin')
@section('title','Фрагменты')

@section('content')
@php
  use Modules\Visual\Models\Fragment;

  $zoneLabels = Fragment::ZONE_LABELS;
  $hasFilters = request()->hasAny(['search', 'zone', 'status']);

  // Куда именно попадает фрагмент — для мини-макета на карточке.
  // Раньше зона была просто строкой в колонке таблицы: прочитать «Сайт ·
  // под содержимым» можно, а представить — нет. Здесь тот же приём, что у
  // тем: маленький макет страницы с подсвеченной полосой.
  $zonePlan = [
      'frontend.topbar'         => ['area' => 'site',  'slot' => 'topbar'],
      'frontend.header'         => ['area' => 'site',  'slot' => 'header'],
      'frontend.content.bottom' => ['area' => 'site',  'slot' => 'content'],
      'frontend.footer'         => ['area' => 'site',  'slot' => 'footer'],
      'admin.header'            => ['area' => 'panel', 'slot' => 'header'],
      'admin.footer'            => ['area' => 'panel', 'slot' => 'footer'],
      'header'                  => ['area' => 'site',  'slot' => 'header'],
      'footer'                  => ['area' => 'site',  'slot' => 'footer'],
  ];
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

{{-- ── Сводка ──────────────────────────────────────────────────────────
     Все шесть заготовок создаются выключенными, и по списку это было видно
     только если пересчитать плашки глазами. Строка отвечает на главный
     вопрос раздела сразу: показывается ли хоть что-нибудь. --}}
@if($fragments->total() > 0)
  @php
    $shownOn = $fragments->getCollection()->where('is_active', true)->count();
    $emptyOnPage = $fragments->getCollection()
        ->filter(fn ($f) => mb_strlen(trim(strip_tags((string) $f->html_cached))) === 0)->count();
  @endphp
  <div class="frg-summary mb-4">
    <span class="frg-summary__item">
      <i class="fas fa-puzzle-piece"></i> всего: <b>{{ $fragments->total() }}</b>
    </span>
    <span class="frg-summary__item {{ $shownOn ? 'is-on' : 'is-off' }}">
      <i class="fas fa-eye"></i> показывается: <b>{{ $shownOn }}</b>
    </span>
    @if($emptyOnPage)
      <span class="frg-summary__item is-warn">
        <i class="fas fa-triangle-exclamation"></i> без содержимого: <b>{{ $emptyOnPage }}</b>
      </span>
    @endif
    @unless($shownOn)
      <span class="frg-summary__note">Ни один фрагмент не выводится — страницы выглядят как обычно.</span>
    @endunless
  </div>
@endif

{{-- ── Фильтры ── --}}
<form method="GET" action="{{ route('admin.visual.fragments.index') }}" class="admin-card p-4 mb-4">
  <div class="frg-filters">
    <div>
      <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Поиск</label>
      <div class="relative">
        <i class="fas fa-magnifying-glass frg-search-ico"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Название или slug…"
               class="frg-input frg-input--search">
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Зона</label>
      <select name="zone" class="frg-input">
        <option value="">Все зоны</option>
        @foreach($zoneLabels as $value => $label)
          <option value="{{ $value }}" @selected(request('zone') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Статус</label>
      <select name="status" class="frg-input">
        <option value="">Все</option>
        <option value="active" @selected(request('status') === 'active')>Включённые</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Выключенные</option>
      </select>
    </div>

    <div class="flex gap-2">
      <button type="submit" class="frg-btn frg-btn--primary"><i class="fas fa-magnifying-glass"></i> Найти</button>
      @if($hasFilters)
        <a href="{{ route('admin.visual.fragments.index') }}" class="frg-btn" title="Сбросить фильтры">
          <i class="fas fa-rotate-left"></i>
        </a>
      @endif
    </div>
  </div>
</form>

@if($fragments->isEmpty())
  {{-- ── Пустое состояние ── --}}
  <div class="admin-card p-10 text-center">
    <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-puzzle-piece"></i></span>

    @if($hasFilters)
      <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Ничего не найдено</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Под выбранные фильтры не подходит ни один фрагмент.</p>
      <a href="{{ route('admin.visual.fragments.index') }}" class="frg-btn">
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
      <a href="{{ route('admin.visual.fragments.create') }}" class="frg-btn frg-btn--primary">
        <i class="fas fa-plus"></i> Создать фрагмент
      </a>
    @endif
  </div>
@else

  {{-- ── Массовые действия ───────────────────────────────────────────────
       Полоса появляется, только когда что-то отмечено. Раньше она висела
       карточкой всегда, занимая место под действие, которое чаще всего не
       требуется — тот же приём уже применён в медиатеке. --}}
  <form method="POST" action="{{ route('admin.visual.fragments.bulkToggle') }}" id="fragBulkForm"
        class="admin-card p-3 mb-4 frg-bulk" hidden>
    @csrf
    <div class="flex flex-wrap items-center gap-2">
      <span id="fragBulkCounter" class="text-sm font-semibold text-gray-700 dark:text-gray-300"></span>
      <span class="text-gray-300 dark:text-gray-600">·</span>
      <select name="action" class="frg-input frg-input--sm">
        <option value="enable">Включить</option>
        <option value="disable">Выключить</option>
      </select>
      <button type="submit" class="frg-btn frg-btn--primary"><i class="fas fa-bolt"></i> Применить</button>
      <button type="submit" form="fragBulkRebuild" class="frg-btn"><i class="fas fa-arrows-rotate"></i> Пересобрать HTML</button>
      <button type="button" id="fragClearSel" class="frg-btn"><i class="fas fa-xmark"></i> Снять отметки</button>
    </div>
  </form>

  {{-- ── Карточки ── --}}
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($fragments as $f)
      @php
        $text     = trim(strip_tags((string) $f->html_cached));
        $length   = mb_strlen($text);
        $isSystem = $f->isSystem();
        $plan     = $zonePlan[$f->zone] ?? null;
        $zoneName = $zoneLabels[$f->zone] ?? ($f->zone ?: 'Без зоны');
      @endphp

      <div class="admin-card p-0 frg-card {{ $f->is_active ? 'frg-card--on' : '' }}">

        {{-- Мини-макет: где именно появится блок --}}
        <div class="frg-map {{ $plan ? 'frg-map--' . $plan['area'] : 'frg-map--none' }}">
          @if($plan)
            {{-- Подпись — над макетом отдельной строкой. Раньше она лежала
                 поверх нижней полосы и с ней сливалась. --}}
            <span class="frg-map__where">
              <i class="fas {{ $plan['area'] === 'panel' ? 'fa-table-columns' : 'fa-globe' }}"></i>
              {{ $plan['area'] === 'panel' ? 'Панель' : 'Сайт' }}
            </span>

            {{-- Макет страницы: у него есть рамка и поля, поэтому даже самая
                 тонкая полоса не прижимается к краю карточки и не читается
                 как её граница — на это и жаловались. --}}
            <div class="frg-map__page">
              <div class="frg-map__slot frg-map__slot--topbar {{ $plan['slot'] === 'topbar' ? 'is-here' : '' }}"></div>
              <div class="frg-map__slot frg-map__slot--header {{ $plan['slot'] === 'header' ? 'is-here' : '' }}"></div>
              <div class="frg-map__slot frg-map__slot--content {{ $plan['slot'] === 'content' ? 'is-here' : '' }}">
                <span class="frg-map__line"></span>
                <span class="frg-map__line frg-map__line--short"></span>
              </div>
              <div class="frg-map__slot frg-map__slot--footer {{ $plan['slot'] === 'footer' ? 'is-here' : '' }}"></div>
            </div>
          @else
            <span class="frg-map__manual"><i class="fas fa-code"></i> вставка вручную по slug</span>
          @endif

          <label class="frg-pick" title="Отметить">
            <input type="checkbox" name="ids[]" value="{{ $f->id }}" form="fragBulkForm" class="frag-checkbox">
            <span class="frg-pick__box"><i class="fas fa-check"></i></span>
          </label>
        </div>

        <div class="p-4">
          <div class="flex items-start gap-2">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <h2 class="font-bold text-gray-900 dark:text-white truncate">{{ $f->title }}</h2>
                @if($isSystem)
                  <span class="frg-tag" title="Slug и зона закреплены системой">системный</span>
                @endif
              </div>
              <div class="text-xs font-mono text-gray-400 mt-0.5 truncate">{{ $f->slug }}</div>
            </div>

            {{-- Переключатель прямо на карточке: самое частое действие раздела.
                 Раньше состояние показывала неподвижная плашка, а менялось оно
                 только в редакторе или массовым действием. --}}
            @if($isSystem)
              <span class="frg-state is-locked" title="Системный фрагмент выключить нельзя">
                <i class="fas fa-lock"></i> всегда
              </span>
            @else
              <button type="submit" form="frag-toggle-{{ $f->id }}"
                      class="frg-state {{ $f->is_active ? 'is-on' : 'is-off' }}"
                      title="{{ $f->is_active ? 'Выключить — блок исчезнет со страниц' : 'Включить — блок появится на страницах' }}">
                <span class="frg-state__dot"></span>
                {{ $f->is_active ? 'показан' : 'скрыт' }}
              </button>
            @endif
          </div>

          <div class="frg-zone mt-3"><i class="fas fa-location-dot"></i> {{ $zoneName }}</div>

          @if($length > 0)
            <p class="frg-excerpt mt-2">{{ \Illuminate\Support\Str::limit($text, 90) }}</p>
            <div class="text-xs text-gray-400 mt-1">
              {{ $length }} симв.
              @if($f->updated_at) · обновлён {{ $f->updated_at->format('d.m.Y') }} @endif
            </div>
          @else
            <p class="frg-empty mt-2">
              <i class="fas fa-triangle-exclamation"></i> пусто — на страницу ничего не выведется
            </p>
          @endif

          <div class="flex items-center gap-2 mt-4">
            <a href="{{ route('admin.visual.fragments.edit', $f) }}" class="frg-btn frg-btn--primary flex-1 justify-center">
              <i class="fas fa-pen"></i> Редактировать
            </a>

            <a href="{{ route('admin.visual.fragments.history', $f) }}" class="frg-icon" title="История версий">
              <i class="fas fa-clock-rotate-left"></i>
            </a>
            <button type="submit" form="frag-duplicate-{{ $f->id }}" class="frg-icon" title="Дублировать">
              <i class="fas fa-copy"></i>
            </button>
            <button type="submit" form="frag-rebuild-{{ $f->id }}" class="frg-icon" title="Пересобрать HTML из шаблона">
              <i class="fas fa-arrows-rotate"></i>
            </button>
            <button type="submit" form="frag-delete-{{ $f->id }}" class="frg-icon frg-icon--danger" title="Удалить">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="mt-4">{{ $fragments->links() }}</div>

  {{-- Формы действий — вне карточек: вложенные формы HTML запрещает.
       Подтверждения выводятся директивой безопасной вставки в JS, а не
       через addslashes: тот ломается на первой же двойной кавычке в
       названии — описанная в памятке проекта грабля. Названия директив
       здесь намеренно словами: Blade выполняет их и внутри комментария. --}}
  <form id="fragBulkRebuild" method="POST" action="{{ route('admin.visual.fragments.bulkRebuild') }}" class="hidden">
    @csrf
  </form>
  @foreach($fragments as $f)
    @unless($f->isSystem())
      <form id="frag-toggle-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.toggle', $f) }}" class="hidden">@csrf</form>
    @endunless
    <form id="frag-duplicate-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.duplicate', $f) }}" class="hidden">@csrf</form>
    <form id="frag-rebuild-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.rebuild', $f) }}" class="hidden"
          onsubmit="return confirm('Пересобрать HTML фрагмента ' + @js($f->title) + ' из шаблона? Ручные правки содержимого будут заменены.');">
      @csrf
    </form>
    <form id="frag-delete-{{ $f->id }}" method="POST" action="{{ route('admin.visual.fragments.destroy', $f) }}" class="hidden"
          onsubmit="return confirm('Удалить фрагмент ' + @js($f->title) + '?');">
      @csrf @method('DELETE')
    </form>
  @endforeach

  <p class="admin-note mt-5 p-3">
    Выключенный или пустой фрагмент на страницу не попадает — вёрстка остаётся прежней.
    Системные фрагменты (site-header, site-footer) массовым переключением не затрагиваются.
  </p>
@endif
@endsection

@push('styles')
<style>
    /* ── Список фрагментов ────────────────────────────────────────────────
       Карточками, как в разделе «Темы»: там на карточке видно, как тема
       выглядит, здесь — куда попадёт блок. Раньше была таблица, и зона была
       просто строкой: прочитать «Сайт · под содержимым» можно, а представить
       себе место на странице — нет.

       Литеральный CSS, а не Tailwind-утилиты: в собранном tailwind.min.css
       этого проекта нет ни прозрачности через дробь, ни произвольных
       значений, ни варианта peer-checked. */

    /* Сводка */
    .frg-summary{ display:flex; flex-wrap:wrap; align-items:center; gap:.75rem;
        padding:.6rem .9rem; font-size:.8rem;
        background:#f9fafb; border:1px solid #e5e7eb; color:#4b5563 }
    .dark .frg-summary{ background:#111827; border-color:#374151; color:#d1d5db }
    .frg-summary__item{ display:inline-flex; align-items:center; gap:.4rem }
    .frg-summary__item i{ color:#9ca3af }
    .frg-summary__item.is-on i{ color:#16a34a }
    .frg-summary__item.is-off i{ color:#9ca3af }
    .frg-summary__item.is-warn i{ color:#d97706 }
    .frg-summary__note{ color:#6b7280; font-style:italic }
    .dark .frg-summary__note{ color:#9ca3af }

    /* Сетка фильтров. Сначала я задал её классом с произвольным значением
       и наступил на ту же мину, что и в редакторе: произвольных значений в
       сборке нет вовсе, класс молча не применяется. */
    .frg-filters{ display:grid; grid-template-columns:1fr; gap:.75rem; align-items:end }
    @media (min-width:768px){
        .frg-filters{ grid-template-columns:minmax(0,1fr) 12rem 10rem auto }
    }

    /* Поля фильтров */
    .frg-input{ display:block; width:100%; padding:.5rem .75rem; font-size:.875rem;
        color:#111827; background:#fff; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s }
    .frg-input:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .frg-input--sm{ width:auto; padding:.35rem .6rem; font-size:.8rem }
    .frg-input--search{ padding-left:2.25rem }
    .dark .frg-input{ color:#f3f4f6; background:#111827; border-color:#374151 }

    /* Лупа в поле поиска: сдвиговых утилит вроде -translate-y-1/2 в сборке
       нет, поэтому позиционируем расчётом от высоты строки. */
    .frg-search-ico{ position:absolute; left:.75rem; top:50%; margin-top:-.5rem;
        font-size:.8rem; color:#9ca3af; pointer-events:none }

    /* Кнопки */
    .frg-btn{ display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .8rem;
        font-size:.8rem; font-weight:600; white-space:nowrap; cursor:pointer;
        color:#374151; background:#fff; border:1px solid #d1d5db; text-decoration:none;
        transition:background-color .15s, border-color .15s, color .15s }
    .frg-btn:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
    .dark .frg-btn{ color:#d1d5db; background:#1f2937; border-color:#374151 }
    .dark .frg-btn:hover{ background:#374151 }

    .frg-btn--primary{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary) }
    .frg-btn--primary:hover{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary); filter:brightness(1.08) }

    .frg-icon{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:2.25rem; height:2.25rem; cursor:pointer;
        color:#6b7280; background:transparent; border:1px solid #d1d5db;
        transition:border-color .15s, color .15s }
    .frg-icon:hover{ border-color:var(--admin-primary); color:var(--admin-primary) }
    .frg-icon--danger:hover{ border-color:#dc2626; color:#dc2626 }
    .dark .frg-icon{ color:#9ca3af; border-color:#374151 }

    /* Карточка */
    .frg-card{ display:flex; flex-direction:column; overflow:hidden }
    .frg-card--on{ outline:2px solid var(--admin-primary); outline-offset:-2px }

    /* ── Мини-макет страницы ──────────────────────────────────────────
       Та же мысль, что у превью темы: показать, а не описать словами.

       Первая версия рисовала полосы прямо на подложке карточки. Тонкая
       полоса верхней зоны (6px) прижималась к краю и читалась как рамка
       карточки, а подпись «САЙТ» лежала поверх нижней полосы и сливалась с
       ней. Теперь у макета есть собственная рамка и поля, подпись вынесена
       отдельной строкой над ним, а полосы разделены зазорами. */
    .frg-map{ position:relative; padding:.6rem .75rem .7rem;
        background:#f8fafc; border-bottom:1px solid #e5e7eb }
    .dark .frg-map{ background:#0f172a; border-bottom-color:#374151 }

    .frg-map__where{ display:inline-flex; align-items:center; gap:.3rem; margin-bottom:.4rem;
        font-size:.6rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#64748b }
    .frg-map__where i{ font-size:.6rem; color:#94a3b8 }
    .dark .frg-map__where{ color:#94a3b8 }

    /* Сама «страница» */
    .frg-map__page{ display:flex; flex-direction:column; gap:3px; height:78px; padding:4px;
        background:#fff; border:1px solid #e2e8f0 }
    .dark .frg-map__page{ background:#1e293b; border-color:#334155 }
    .frg-map--panel .frg-map__page{ background:#fdfdff; border-color:#dbe0f5 }
    .dark .frg-map--panel .frg-map__page{ background:#1e1b4b; border-color:#312e81 }

    .frg-map__slot{ flex:none; background:#e8edf3 }
    .dark .frg-map__slot{ background:#334155 }
    .frg-map__slot--topbar{ height:7px }
    .frg-map__slot--header{ height:13px }
    .frg-map__slot--content{ flex:1; display:flex; flex-direction:column; justify-content:center;
        gap:4px; padding:0 5px; background:transparent }
    .frg-map__slot--footer{ height:11px }

    /* Строки текста в теле страницы — чтобы «содержимое» читалось как
       содержимое, а не как ещё одна пустая полоса. */
    .frg-map__line{ display:block; height:3px; width:100%; background:#e8edf3 }
    .frg-map__line--short{ width:60% }
    .dark .frg-map__line{ background:#334155 }

    /* Подсвеченная полоса — та самая зона фрагмента. Заливка акцентом плюс
       кольцо вокруг: у самой тонкой полосы одной заливки не хватало, чтобы
       её заметить. */
    .frg-map__slot.is-here{ position:relative; background:var(--admin-primary) }
    .frg-map__slot.is-here::after{ content:''; position:absolute; inset:-3px;
        border:1px solid var(--admin-primary); opacity:.45 }
    .frg-map__slot--content.is-here{ background:color-mix(in srgb, var(--admin-primary) 18%, transparent) }
    .frg-map__slot--content.is-here .frg-map__line{ background:var(--admin-primary); opacity:.55 }

    .frg-map--none{ display:flex; align-items:center; justify-content:center; min-height:104px }

    /* Отметка для массовых действий */
    .frg-pick{ position:absolute; top:8px; right:8px; cursor:pointer }
    .frg-pick input{ position:absolute; opacity:0; width:0; height:0 }
    .frg-pick__box{ display:flex; align-items:center; justify-content:center;
        width:1.35rem; height:1.35rem; font-size:.65rem; color:transparent;
        background:#fff; border:1px solid #cbd5e1; transition:background-color .15s, color .15s }
    .frg-pick input:checked ~ .frg-pick__box{ color:var(--admin-on-primary,#fff);
        background:var(--admin-primary); border-color:var(--admin-primary) }
    .dark .frg-pick__box{ background:#1f2937; border-color:#4b5563 }

    /* Состояние: кнопка, а не подпись */
    .frg-state{ display:inline-flex; align-items:center; gap:.35rem; flex:none;
        padding:.2rem .5rem; font-size:.7rem; font-weight:700; cursor:pointer;
        border:1px solid transparent; transition:filter .15s }
    .frg-state:hover{ filter:brightness(.95) }
    .frg-state__dot{ width:.45rem; height:.45rem; border-radius:9999px; background:currentColor }
    .frg-state.is-on{ color:color-mix(in srgb, #16a34a 60%, #111827);
        background:color-mix(in srgb, #16a34a 16%, #fff);
        border-color:color-mix(in srgb, #16a34a 30%, #fff) }
    .frg-state.is-off{ color:#6b7280; background:#f3f4f6; border-color:#e5e7eb }
    .frg-state.is-locked{ color:#6b7280; background:#f3f4f6; border-color:#e5e7eb; cursor:default }
    .dark .frg-state.is-off, .dark .frg-state.is-locked{ color:#9ca3af; background:#374151; border-color:#4b5563 }

    .frg-tag{ font-size:.65rem; padding:.1rem .4rem; color:#6b7280; background:#f3f4f6; border:1px solid #e5e7eb }
    .dark .frg-tag{ color:#9ca3af; background:#374151; border-color:#4b5563 }

    .frg-zone{ display:inline-flex; align-items:center; gap:.4rem; font-size:.75rem; color:#6b7280 }
    .frg-zone i{ color:var(--admin-primary) }
    .dark .frg-zone{ color:#9ca3af }

    .frg-excerpt{ font-size:.8rem; line-height:1.5; color:#4b5563 }
    .dark .frg-excerpt{ color:#d1d5db }

    .frg-empty{ font-size:.75rem; color:color-mix(in srgb, #d97706 60%, #111827);
        background:color-mix(in srgb, #d97706 12%, #fff);
        border:1px solid color-mix(in srgb, #d97706 26%, #fff); padding:.35rem .5rem }

    /* Полоса массовых действий появляется только при выборе */
    .frg-bulk[hidden]{ display:none }
</style>
@endpush

@push('scripts')
<script>
  (function () {
    const boxes = () => Array.prototype.slice.call(document.querySelectorAll('.frag-checkbox'));
    const bulk = document.getElementById('fragBulkForm');
    const counter = document.getElementById('fragBulkCounter');
    const rebuildForm = document.getElementById('fragBulkRebuild');
    const clearBtn = document.getElementById('fragClearSel');

    function refresh() {
      const checked = boxes().filter(cb => cb.checked);

      // Полоса действий показывается, только когда есть что применять.
      if (bulk) { bulk.hidden = checked.length === 0; }
      if (counter) { counter.textContent = 'Отмечено: ' + checked.length; }

      // Массовая пересборка — своя форма, копируем в неё отмеченные id.
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
    }

    boxes().forEach(cb => cb.addEventListener('change', refresh));

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        boxes().forEach(cb => { cb.checked = false; });
        refresh();
      });
    }

    refresh();
  })();
</script>
@endpush
