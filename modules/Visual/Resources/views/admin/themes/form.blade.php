@extends('layouts.admin')
@section('title', $theme->exists ? 'Редактировать тему' : 'Создать тему')

@php
    $t   = $theme->tokens ?? [];
    $cfg = $theme->config ?? [];

    /*
     * Список шрифтов строится ИЗ ТОГО, ЧТО ДЕЙСТВИТЕЛЬНО ЛЕЖИТ НА СЕРВЕРЕ.
     *
     * Раньше он был вписан в эту вьюху руками и разошёлся с набором файлов:
     * предлагалось четырнадцать шрифтов, которых нет (выбрав Poppins, автор
     * молча получал системный), и не предлагалось семь, которые есть.
     * Второе определение одного и того же в этом проекте расходилось уже не
     * раз — поэтому источник теперь один.
     */
    $kinds = [
        'sans'  => 'Без засечек',
        'serif' => 'С засечками',
        'mono'  => 'Моноширинные',
        'hand'  => 'Рукописные',
    ];

    $fontGroups = [];

    foreach (LOCAL_FONTS as $font) {
        $tail = match ($font['kind']) {
            'serif' => 'serif',
            'mono'  => 'ui-monospace, monospace',
            'hand'  => 'cursive',
            default => 'system-ui, sans-serif',
        };

        $fontGroups[$font['kind']][$font['family'] . ', ' . $tail] = $font['label'];
    }

    $systemStack = '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif';
    $font        = old('tokens.font.base', data_get($t, 'font.base', $systemStack));

    /*
     * Записанный ранее набор шрифтов может не совпасть ни с одним пунктом
     * списка: у существующих тем встречаются наборы, собранные до того, как
     * список стал строиться из установленных файлов. Если такой набор не
     * показать отдельным пунктом, браузер выберет первый — и первое же
     * сохранение молча заменило бы шрифт темы.
     */
    $known = collect($fontGroups)->flatMap(fn ($list) => array_keys($list))->push($systemStack);
    $customFont = $font && !$known->contains($font) ? $font : null;
    $provider    = old('config.font_provider', data_get($cfg, 'font_provider'));
    $fname       = old('config.font_name', data_get($cfg, 'font_name'));
    $radius      = old('tokens.radius.md', data_get($t, 'radius.md', '12px'));
    $logoUrl     = data_get($cfg, 'logo_url');
    $bgUrl       = data_get($cfg, 'background_url');
    $iconMode    = old('config.icon_mode', data_get($cfg, 'icon_mode', 'lucide'));
@endphp

@section('content')
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div class="flex items-center gap-3 min-w-0">
    <span class="admin-icon-badge"><i class="fas fa-palette"></i></span>
    <div class="min-w-0">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">
        {{ $theme->exists ? 'Редактировать тему' : 'Новая тема' }}
      </h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
        {{ $theme->exists ? $theme->title . ' · ' . $theme->slug : 'Цвета, шрифт, иконки и оформление сайта' }}
      </p>
    </div>
  </div>

  <a href="{{ route('admin.visual.themes.index') }}"
     class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
            hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
    <i class="fas fa-arrow-left"></i> К списку
  </a>
</div>

@if ($errors->any())
  <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
    <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form id="themeForm" method="POST" enctype="multipart/form-data"
      action="{{ $theme->exists ? route('admin.visual.themes.update',$theme) : route('admin.visual.themes.store') }}"
      class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
  @csrf
  @if($theme->exists) @method('PUT') @endif

  <div class="lg:col-span-2 space-y-5">

    {{-- ── Название ─────────────────────────────────────────────── --}}
    <section class="admin-card p-5">
      <div class="thm-head">
        <span class="thm-head__ico"><i class="fas fa-tag"></i></span>
        <div>
          <h2 class="thm-head__title">Название</h2>
          <p class="admin-hint">Как тема называется в списке и по какому адресу к ней обращаться.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="thm-label">Название</label>
          <input type="text" name="title" class="admin-field" value="{{ old('title',$theme->title) }}" required>
        </div>
        <div>
          <label class="thm-label">Адрес (slug)</label>
          <input type="text" name="slug" class="admin-field" spellcheck="false"
                 value="{{ old('slug',$theme->slug) }}" required>
          <p class="admin-hint mt-1">Латиница, цифры и дефисы.</p>
        </div>
      </div>
    </section>

    {{-- ── Цвета ────────────────────────────────────────────────── --}}
    <section class="admin-card p-5">
      <div class="thm-head">
        <span class="thm-head__ico"><i class="fas fa-swatchbook"></i></span>
        <div>
          <h2 class="thm-head__title">Цвета</h2>
          <p class="admin-hint">Меняются сразу в примере справа.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <x-color name="tokens[colors][bg]"      label="Фон сайта"     :value="old('tokens.colors.bg',     data_get($t,'colors.bg','#ffffff'))" />
        <x-color name="tokens[colors][text]"    label="Текст"         :value="old('tokens.colors.text',   data_get($t,'colors.text','#111827'))" />
        <x-color name="tokens[colors][primary]" label="Основной"      :value="old('tokens.colors.primary',data_get($t,'colors.primary','#2563eb'))" />
        <x-color name="tokens[colors][accent]"  label="Дополнительный" :value="old('tokens.colors.accent', data_get($t,'colors.accent','#10b981'))" />
        <x-color name="tokens[colors][header]"  label="Фон шапки"     :value="old('tokens.colors.header', data_get($t,'colors.header','#ffffff'))" />
        <x-color name="tokens[colors][footer]"  label="Фон подвала"   :value="old('tokens.colors.footer', data_get($t,'colors.footer','#ffffff'))" />
      </div>
    </section>

    {{-- ── Шрифт и формы ────────────────────────────────────────── --}}
    <section class="admin-card p-5">
      <div class="thm-head">
        <span class="thm-head__ico"><i class="fas fa-font"></i></span>
        <div>
          <h2 class="thm-head__title">Шрифт и формы</h2>
          <p class="admin-hint">Все перечисленные шрифты лежат на вашем сервере — наружу не уходит ни один запрос.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="thm-label">Основной шрифт</label>
          <select name="tokens[font][base]" id="fontBase" class="admin-field">
            <option value="{{ $systemStack }}" @selected($font === $systemStack)>Системный шрифт устройства</option>

            @if ($customFont)
              <option value="{{ $customFont }}" selected>Как задано в теме — {{ \Illuminate\Support\Str::limit($customFont, 34) }}</option>
            @endif

            @foreach ($fontGroups as $kind => $list)
              <optgroup label="{{ $kinds[$kind] ?? $kind }}">
                @foreach ($list as $stack => $label)
                  <option value="{{ $stack }}" @selected($font === $stack)>{{ $label }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
          <p class="admin-hint mt-1">Системный подстраивается под устройство читателя и не грузится вовсе.</p>

          <label class="thm-label mt-4">Свой шрифт файлом</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="thm-file">
              <input type="file" name="font_woff2" accept=".woff2">
              <span><i class="fas fa-arrow-up-from-bracket"></i> Файл .woff2</span>
            </label>
            <label class="thm-file">
              <input type="file" name="font_ttf" accept=".ttf,.otf">
              <span><i class="fas fa-arrow-up-from-bracket"></i> Файл .ttf или .otf</span>
            </label>
          </div>
          <p class="admin-hint mt-1">Нужен, только если своего шрифта нет в списке выше.</p>
        </div>

        <div>
          <label class="thm-label">Скругление углов</label>
          <div class="thm-radius">
            <input type="range" min="0" max="24" step="1" value="{{ (int) $radius }}" id="radiusSlider">
            <input type="text" name="tokens[radius][md]" id="radiusValue" class="admin-field thm-radius__value"
                   value="{{ $radius }}" spellcheck="false">
          </div>
          <p class="admin-hint mt-1">Ноль — прямые края, как в панели управления.</p>

          <label class="thm-label mt-4">Откуда брать шрифт сайта</label>
          <select name="config[font_provider]" class="admin-field">
            <option value="">Не подключать — оставить системный</option>
            <option value="local"  @selected($provider==='local')>С вашего сервера — рекомендуется</option>
            <option value="google" @selected($provider==='google')>Google Fonts — запрос наружу</option>
            <option value="bunny"  @selected($provider==='bunny')>Bunny Fonts — запрос наружу</option>
          </select>

          <input type="text" name="config[font_name]" class="admin-field mt-3"
                 list="local-fonts-list" placeholder="Название шрифта, например Inter" value="{{ $fname }}">
          <datalist id="local-fonts-list">
            @foreach (LOCAL_FONTS as $item)
              <option value="{{ $item['label'] }}"></option>
            @endforeach
          </datalist>
          <p class="admin-hint mt-1">
            Два последних варианта обращаются к чужому серверу: адреса читателей уйдут туда.
          </p>
        </div>
      </div>
    </section>

    {{-- ── Логотип ──────────────────────────────────────────────── --}}
    <section class="admin-card p-5">
      <div class="thm-head">
        <span class="thm-head__ico"><i class="fas fa-image"></i></span>
        <div>
          <h2 class="thm-head__title">Логотип</h2>
          <p class="admin-hint">PNG или SVG. Виден в примере справа.</p>
        </div>
      </div>

      <input type="hidden" name="remove_logo" id="removeLogoFlag" value="0">

      <label class="thm-file thm-file--wide">
        <input type="file" name="logo" accept="image/*">
        <span><i class="fas fa-arrow-up-from-bracket"></i> Выбрать картинку</span>
      </label>

      @if($logoUrl)
        {{-- Кнопки «Убрать» и «Вернуть» работают ровно как раньше: тот же
             скрытый флаг, тот же пересчёт примера справа. Добавлено только
             скачивание — забрать знак себе, не открывая чужую тему. --}}
        <div id="logoPreview" class="thm-asset mt-3">
          <img src="{{ $logoUrl }}" alt="Логотип">
          <span class="thm-asset__path" title="{{ $logoUrl }}">{{ $logoUrl }}</span>
          <div class="thm-asset__actions">
            <a class="thm-btn" href="{{ $logoUrl }}" download title="Скачать файл знака">
              <i class="fas fa-download"></i> Скачать
            </a>
            <button type="button" class="thm-btn thm-btn--danger"
                    onclick="(function(){document.getElementById('removeLogoFlag').value=1; document.getElementById('logoPreview').style.display='none'; window.__themeLogoPrev=@js($logoUrl); window.__syncThemeVars();})();">
              <i class="fas fa-trash"></i> Убрать
            </button>
            <button type="button" class="thm-btn"
                    onclick="(function(){document.getElementById('removeLogoFlag').value=0; document.getElementById('logoPreview').style.display=''; window.__syncThemeVars();})();">
              <i class="fas fa-rotate-left"></i> Вернуть
            </button>
          </div>
        </div>
      @endif

      @include('Visual::admin.themes.partials.borrow', [
          'library' => $assetLibrary ?? collect(),
          'kind'    => 'logo',
      ])

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="thm-label">Положение</label>
          @php $lp = old('config.logo_position', data_get($cfg,'logo_position','left')); @endphp
          <select name="config[logo_position]" id="logoPos" class="admin-field">
            <option value="left"   @selected($lp==='left')>Слева</option>
            <option value="center" @selected($lp==='center')>По центру</option>
            <option value="right"  @selected($lp==='right')>Справа</option>
          </select>
        </div>
        <div>
          <label class="thm-label">Ширина</label>
          <input type="text" name="config[logo_width]" id="logoWidth" class="admin-field"
                 placeholder="120px или 32%" value="{{ old('config.logo_width', data_get($cfg,'logo_width')) }}">
        </div>
      </div>
    </section>

    {{-- ── Фон и значки ─────────────────────────────────────────── --}}
    <section class="admin-card p-5">
      <div class="thm-head">
        <span class="thm-head__ico"><i class="fas fa-layer-group"></i></span>
        <div>
          <h2 class="thm-head__title">Фон и значки</h2>
          <p class="admin-hint">Все наборы значков лежат на вашем сервере.</p>
        </div>
      </div>

      <input type="hidden" name="remove_bg" id="removeBgFlag" value="0">

      <label class="thm-label">Фоновый узор</label>
      <label class="thm-file thm-file--wide">
        <input type="file" name="bg_image" accept="image/*">
        <span><i class="fas fa-arrow-up-from-bracket"></i> Выбрать картинку</span>
      </label>
      <p class="admin-hint mt-1">Повторяется плиткой. {{ __('admin.themes.upload_limit', ['limit' => max_upload_label(10240)]) }}</p>

      @if($bgUrl)
        {{-- Раньше здесь был только путь к файлу: понять по нему, как узор
             выглядит, невозможно. Слева — настоящая плитка, повторённая так
             же, как она ляжет на странице. Кнопки «Убрать» и «Вернуть»
             прежние, тронуто только оформление вокруг них. --}}
        <div id="bgPreview" class="thm-asset mt-3">
          <span class="thm-asset__tile" style="background-image:url('{{ $bgUrl }}')"
                title="Так узор повторяется на странице"></span>
          <span class="thm-asset__path" title="{{ $bgUrl }}">{{ $bgUrl }}</span>
          <div class="thm-asset__actions">
            <a class="thm-btn" href="{{ $bgUrl }}" download title="Скачать файл узора">
              <i class="fas fa-download"></i> Скачать
            </a>
            <button type="button" class="thm-btn thm-btn--danger"
                    onclick="(function(){document.getElementById('removeBgFlag').value=1; document.getElementById('bgPreview').style.display='none'; window.__themeBgPrev=@js($bgUrl); window.__syncThemeVars();})();">
              <i class="fas fa-trash"></i> Убрать
            </button>
            <button type="button" class="thm-btn"
                    onclick="(function(){document.getElementById('removeBgFlag').value=0; document.getElementById('bgPreview').style.display=''; window.__syncThemeVars();})();">
              <i class="fas fa-rotate-left"></i> Вернуть
            </button>
          </div>
        </div>
      @endif

      @include('Visual::admin.themes.partials.borrow', [
          'library' => $assetLibrary ?? collect(),
          'kind'    => 'background',
      ])

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="thm-label">Набор значков</label>
          <select name="config[icon_mode]" class="admin-field">
            <option value="lucide"    @selected($iconMode==='lucide')>Lucide — тонкие линии</option>
            <option value="fa"        @selected($iconMode==='fa')>Font Awesome</option>
            <option value="bootstrap" @selected($iconMode==='bootstrap')>Bootstrap Icons</option>
            <option value="tabler"    @selected($iconMode==='tabler')>Tabler Icons</option>
            <option value="phosphor"  @selected($iconMode==='phosphor')>Phosphor — тонкие, 1530 значков</option>
            <option value="boxicons"  @selected($iconMode==='boxicons')>Boxicons — 1634 значка</option>
            <option value="remix"     @selected($iconMode==='remix')>Remix Icons</option>
            <option value="svg"       @selected($iconMode==='svg')>Свои SVG из архива</option>
          </select>
        </div>
        <div>
          <label class="thm-label">Свои значки архивом</label>
          <label class="thm-file thm-file--wide">
            <input type="file" name="icons_zip" accept=".zip">
            <span><i class="fas fa-file-zipper"></i> Архив ZIP с SVG</span>
          </label>
          @if($p = data_get($cfg,'icons_path'))
            <p class="admin-hint mt-1">Распакованы: {{ $p }}</p>
          @endif
        </div>
      </div>
    </section>

    {{-- ── Свои правила оформления ──────────────────────────────── --}}
    <section class="admin-card p-5">
      <div class="thm-head">
        <span class="thm-head__ico"><i class="fas fa-code"></i></span>
        <div>
          <h2 class="thm-head__title">Свои правила оформления</h2>
          <p class="admin-hint">Необязательно. Добавляются к теме последними и перекрывают остальное.</p>
        </div>
      </div>

      <textarea name="config[css]" class="admin-field thm-css" spellcheck="false"
                placeholder=":root{ --color-primary: #6366f1; }">{!! old('config.css', data_get($cfg,'css','')) !!}</textarea>
    </section>
  </div>

  {{-- ── Пример и действия ──────────────────────────────────────── --}}
  <aside class="lg:col-span-1">
    <div class="sticky top-4 space-y-5">
      <section class="admin-card p-5">
        <div class="thm-head">
          <span class="thm-head__ico"><i class="fas fa-eye"></i></span>
          <h2 class="thm-head__title">Пример</h2>
        </div>

        <div id="preview" class="thm-preview">
          <div id="pvHeaderWrap" class="thm-preview__bar">
            <img id="pvLogo" src="{{ $logoUrl }}" alt="Логотип"
                 style="display: {{ $logoUrl ? 'block' : 'none' }};">
            <div class="thm-preview__mark">R</div>
            <div class="thm-preview__name">RU CMS</div>
            <nav class="thm-preview__nav"><span>Главная</span><span>О нас</span><span>Контакты</span></nav>
          </div>

          <h4 class="thm-preview__h">Заголовок</h4>
          <p class="thm-preview__p">Пример текста в текущей теме. Кнопки ниже показывают оба цвета.</p>

          <div class="thm-preview__btns">
            <button type="button" class="thm-preview__btn">Основной</button>
            <button type="button" class="thm-preview__btn thm-preview__btn--accent">Дополнительный</button>
          </div>

          <div class="thm-preview__foot">Подвал</div>
        </div>
      </section>

      <section class="admin-card p-5">
        <div class="thm-actions">
          <button type="submit" class="thm-btn thm-btn--main">
            <i class="fas fa-floppy-disk"></i> Сохранить
          </button>

          @if($theme->exists)
            <button type="button" class="thm-btn"
                    onclick="document.getElementById('applyForm').submit()">
              <i class="fas fa-wand-magic-sparkles"></i> Применить к сайту
            </button>
            <button type="button" class="thm-btn thm-btn--danger"
                    onclick="if(confirm('Удалить тему «{{ $theme->title }}»? Действие необратимо.')) document.getElementById('deleteForm').submit()">
              <i class="fas fa-trash"></i> Удалить тему
            </button>
          @endif
        </div>
      </section>
    </div>
  </aside>
</form>

@if($theme->exists)
  <form id="applyForm" method="POST" action="{{ route('admin.visual.themes.apply',$theme) }}" class="hidden">@csrf @method('PATCH')</form>
  <form id="deleteForm" method="POST" action="{{ route('admin.visual.themes.destroy',$theme) }}" class="hidden">@csrf @method('DELETE')</form>
@endif

@push('styles')
<style>
  /* Оформление формы темы. Литеральный CSS, а не утилиты: в собранном
     Tailwind этого проекта нет ни прозрачности через дробь, ни произвольных
     значений, ни тёмных вариантов — см. памятку проекта. */

  .thm-head{ display:flex; align-items:flex-start; gap:12px; margin-bottom:18px; }
  .thm-head__ico{
      display:inline-flex; align-items:center; justify-content:center;
      width:34px; height:34px; flex:0 0 auto;
      color:#fff; background:var(--admin-primary,#6366f1);
  }
  .thm-head__title{ font-size:15px; font-weight:700; line-height:1.2; color:#111827; }
  .dark .thm-head__title{ color:#f3f4f6; }

  .thm-label{
      display:block; margin-bottom:6px;
      font-size:12px; font-weight:600; letter-spacing:.02em;
      color:#4b5563;
  }
  .dark .thm-label{ color:#cbd5e1; }

  /* Единый вид поля. Раньше на каждом поле висел свой набор утилит, и они
     разъезжались: где-то рамка была, где-то нет, высоты не совпадали. */
  .admin-field{
      display:block; width:100%;
      padding:9px 11px;
      font:inherit; font-size:13.5px; line-height:1.4;
      color:#111827; background:#fff;
      border:1px solid #d9dce5;
      transition:border-color .15s ease, box-shadow .15s ease;
  }
  .admin-field:focus{
      outline:none;
      border-color:var(--admin-primary,#6366f1);
      box-shadow:0 0 0 3px var(--admin-primary-glow,rgba(99,102,241,.25));
  }
  .dark .admin-field{ color:#e5e7eb; background:#111827; border-color:#374151; }

  select.admin-field{ cursor:pointer; }
  .thm-css{ min-height:120px; font-family:ui-monospace,SFMono-Regular,Consolas,monospace; font-size:12.5px; }

  /* Цвет */
  .thm-color__row{ display:flex; align-items:center; gap:8px; }
  .thm-color__dot{
      width:38px; height:38px; flex:0 0 auto; padding:2px;
      background:#fff; border:1px solid #d9dce5; cursor:pointer;
  }
  .dark .thm-color__dot{ background:#111827; border-color:#374151; }
  .thm-color__hex{ font-family:ui-monospace,SFMono-Regular,Consolas,monospace; text-transform:lowercase; }

  /* Скругление */
  .thm-radius{ display:flex; align-items:center; gap:12px; }
  .thm-radius input[type=range]{ flex:1 1 auto; accent-color:var(--admin-primary,#6366f1); }
  .thm-radius__value{ width:88px; flex:0 0 auto; text-align:center; font-family:ui-monospace,Consolas,monospace; }

  /* Выбор файла. Родное поле выглядит в каждом браузере по-своему и не
     вписывается ни в какое оформление — прячем его, оставляя подпись. */
  .thm-file{ display:block; cursor:pointer; }
  .thm-file input{ position:absolute; width:1px; height:1px; opacity:0; }
  .thm-file span{
      display:flex; align-items:center; justify-content:center; gap:8px;
      padding:9px 12px;
      font-size:13px; font-weight:600; color:#4b5563;
      background:#f7f8fa; border:1px dashed #cbd0dd;
      transition:color .15s ease, border-color .15s ease, background .15s ease;
  }
  .thm-file:hover span{ color:var(--admin-primary,#6366f1); border-color:var(--admin-primary,#6366f1); background:#fff; }
  .thm-file input:focus-visible + span{ outline:2px solid var(--admin-primary,#6366f1); outline-offset:2px; }
  .dark .thm-file span{ color:#cbd5e1; background:#111827; border-color:#374151; }

  /* Уже загруженное */
  .thm-asset{ display:flex; align-items:center; gap:12px; padding:10px; border:1px solid #e5e7eb; background:#fafbfc; }
  .dark .thm-asset{ background:#111827; border-color:#374151; }
  .thm-asset img{ height:42px; object-fit:contain; background:#fff; padding:3px; border:1px solid #e5e7eb; }
  .thm-asset__path{ flex:1 1 auto; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:#6b7280; }
  /* Плитка узора: показываем ровно так, как он ляжет на странице —
     повторением, а не одной растянутой картинкой. */
  .thm-asset__tile{ width:64px; height:42px; flex:0 0 auto; border:1px solid #e5e7eb;
      background-repeat:repeat; background-size:48px 48px; background-color:#fff; }
  .dark .thm-asset__tile{ border-color:#374151; }

  /* Ассеты других тем */
  .thm-borrow{ margin-top:.75rem; border:1px solid #e5e7eb; background:#fafbfc; }
  .dark .thm-borrow{ border-color:#374151; background:#111827; }
  .thm-borrow > summary{ padding:.55rem .75rem; font-size:.78rem; font-weight:600;
      color:#4b5563; cursor:pointer; list-style:none; }
  .dark .thm-borrow > summary{ color:#d1d5db; }
  .thm-borrow > summary::-webkit-details-marker{ display:none; }
  .thm-borrow__list{ display:flex; flex-direction:column; gap:.4rem;
      padding:0 .75rem .7rem; }
  .thm-borrow__row{ display:flex; align-items:center; gap:.6rem; font-size:.78rem; }
  .thm-borrow__dot{ width:.7rem; height:.7rem; flex:none; border:1px solid rgba(0,0,0,.15); }
  .thm-borrow__name{ flex:1 1 auto; min-width:0; overflow:hidden;
      text-overflow:ellipsis; white-space:nowrap; color:#374151; }
  .dark .thm-borrow__name{ color:#d1d5db; }
  .thm-borrow__tile{ width:34px; height:22px; flex:none; border:1px solid #e5e7eb;
      background-repeat:repeat; background-size:26px 26px; background-color:#fff; }
  .thm-borrow__logo{ height:22px; width:34px; object-fit:contain; flex:none;
      border:1px solid #e5e7eb; background:#fff; padding:2px; }
  .dark .thm-borrow__tile, .dark .thm-borrow__logo{ border-color:#374151; }

  .thm-asset__actions{ display:flex; gap:6px; margin-left:auto; flex:0 0 auto; }

  /* Кнопки */
  .thm-btn{
      display:inline-flex; align-items:center; justify-content:center; gap:8px;
      padding:9px 14px;
      font:inherit; font-size:13px; font-weight:600;
      color:#374151; background:#fff; border:1px solid #d9dce5; cursor:pointer;
      transition:color .15s ease, border-color .15s ease, background .15s ease;
  }
  .thm-btn:hover{ color:var(--admin-primary,#6366f1); border-color:var(--admin-primary,#6366f1); }
  .thm-btn--main{ color:#fff; background:var(--admin-primary,#6366f1); border-color:var(--admin-primary,#6366f1); }
  .thm-btn--main:hover{ color:#fff; filter:brightness(1.08); }
  .thm-btn--danger:hover{ color:#b91c1c; border-color:#b91c1c; }
  .dark .thm-btn{ color:#cbd5e1; background:#1f2937; border-color:#374151; }

  .thm-actions{ display:flex; flex-direction:column; gap:8px; }

  /* Пример */
  .thm-preview{
      padding:14px; border:1px solid #e5e7eb;
      background:var(--color-bg,#fff);
      color:var(--color-text,#111827);
      font-family:var(--font-base,-apple-system,BlinkMacSystemFont,Inter,system-ui,sans-serif);
  }
  .dark .thm-preview{ border-color:#374151; }
  .thm-preview__bar{ display:flex; gap:10px; align-items:center; padding:8px 10px; margin-bottom:12px; background:var(--color-header,#fff); }
  .thm-preview__bar img{ height:28px; object-fit:contain; }
  .thm-preview__mark{
      display:flex; align-items:center; justify-content:center;
      width:28px; height:28px; flex:0 0 auto;
      font-size:13px; font-weight:700; color:var(--on-accent,#fff); background:var(--color-primary,#2563eb);
  }
  .thm-preview__name{ font-weight:700; font-size:14px; }
  /* ⚠️ Перенос обязателен: это макет сайта в миниатюре, и на планшете в
     альбомной ориентации ряд ссылок не влезал в узкую колонку предпросмотра —
     17 пикселей прокрутки получала ВСЯ страница настроек темы. Уменьшать
     кегль здесь нельзя (он и так 12, а это образец шрифта темы), поэтому
     переносим. */
  .thm-preview__nav{ display:flex; flex-wrap:wrap; gap:10px; margin-left:auto;
      font-size:12px; opacity:.75; }
  .thm-preview__h{ font-size:17px; font-weight:700; margin-bottom:6px; }
  .thm-preview__p{ font-size:13px; opacity:.8; margin-bottom:12px; }
  .thm-preview__btns{ display:flex; flex-wrap:wrap; gap:8px; }
  .thm-preview__btn{
      padding:7px 13px; font:inherit; font-size:13px; font-weight:600;
      color:var(--on-accent,#fff); background:var(--color-primary,#2563eb); border:0; cursor:default;
  }
  .thm-preview__btn--accent{ background:var(--color-accent,#10b981); }
  .thm-preview__foot{ margin-top:14px; padding:8px 10px; font-size:12px; background:var(--color-footer,#fff); }
</style>
@endpush

@push('scripts')
<script>
  window.__syncThemeVars = function(){
    const root = document.querySelector('#preview');
    if (!root) return;

    const get  = (sel, def) => (document.querySelector(`[name="${sel}"]`)?.value || def);
    const setVar = (name, value) => root.style.setProperty(name, value);

    setVar('--color-bg',     get('tokens[colors][bg]','#ffffff'));
    setVar('--color-text',   get('tokens[colors][text]','#111827'));
    setVar('--color-primary',get('tokens[colors][primary]','#2563eb'));
    setVar('--color-accent', get('tokens[colors][accent]','#10b981'));
    setVar('--color-header', get('tokens[colors][header]','#ffffff'));
    setVar('--color-footer', get('tokens[colors][footer]','#ffffff'));

    setVar('--font-base', document.getElementById('fontBase')?.value || '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');

    // Скругление показываем на тех частях примера, у которых оно бывает и на
    // сайте: полосы, кнопки, значок. Панель управления живёт с прямыми
    // краями, и общее правило обнулило бы радиус — поэтому задаём напрямую.
    const r = document.getElementById('radiusValue')?.value || '12px';
    setVar('--radius-md', r);
    root.querySelectorAll('.thm-preview__bar, .thm-preview__btn, .thm-preview__mark, .thm-preview__foot')
        .forEach(el => el.style.borderRadius = r);

    const logoUrlCfg = @json($logoUrl);
    const removedLogo = document.getElementById('removeLogoFlag')?.value === '1';
    const logoUrl = removedLogo ? null : (logoUrlCfg || window.__themeLogoPrev || null);
    const logoImg = document.getElementById('pvLogo');

    if (logoImg) {
      if (logoUrl) { logoImg.src = logoUrl; logoImg.style.display = ''; }
      else { logoImg.style.display = 'none'; }

      logoImg.style.width = document.getElementById('logoWidth')?.value || '';

      const lp = document.getElementById('logoPos')?.value || 'left';
      const wrap = document.getElementById('pvHeaderWrap');

      if (wrap) {
        wrap.style.justifyContent = (lp === 'center') ? 'center' : (lp === 'right' ? 'flex-end' : 'flex-start');
      }
    }

    const bgUrlCfg = @json($bgUrl);
    const removedBg = document.getElementById('removeBgFlag')?.value === '1';
    const bgUrl = removedBg ? null : (bgUrlCfg || window.__themeBgPrev || null);

    if (bgUrl) {
      root.style.backgroundImage = `url(${bgUrl})`;
      root.style.backgroundRepeat = 'repeat';
      root.style.backgroundSize = 'auto';
    } else {
      root.style.backgroundImage = '';
    }
  };

  // Ползунок и поле показывают одно значение с двух сторон.
  const slider = document.getElementById('radiusSlider');
  const rVal   = document.getElementById('radiusValue');

  if (slider && rVal) {
    slider.addEventListener('input', () => { rVal.value = slider.value + 'px'; window.__syncThemeVars(); });
    rVal.addEventListener('input', () => { slider.value = parseInt(rVal.value) || 0; window.__syncThemeVars(); });
  }

  // Подпись у выбора файла заменяется на имя выбранного: иначе после выбора
  // не видно, что вообще выбрано — родное поле спрятано.
  document.querySelectorAll('.thm-file input[type=file]').forEach(input => {
    const caption = input.nextElementSibling;
    const original = caption.innerHTML;

    input.addEventListener('change', () => {
      caption.innerHTML = input.files && input.files.length
        ? '<i class="fas fa-check"></i> ' + input.files[0].name
        : original;
    });
  });

  document.addEventListener('input', e => {
    if (e.target.matches('input, select, textarea')) window.__syncThemeVars();
  });

  window.__syncThemeVars();
</script>
@endpush
@endsection
