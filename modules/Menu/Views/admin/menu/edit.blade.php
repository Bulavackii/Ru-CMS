@extends('layouts.admin')

@section('title', __('admin.menu.page_edit'))

@section('content')
    {{-- ───────────────────────── Header ───────────────────────── --}}
    <div class="admin-accent-bar mb-0"></div>
    {{-- Шапка раздела в ДВА РЯДА (просьба владельца, разобранная по
         снимкам): сверху — название меню и его состояние, снизу — позиция и
         возврат к списку. Прежняя раскладка складывала название, позицию и
         состояние в один блок, а ссылка «Назад к списку» уезжала третьей
         строкой, оставляя половину ширины пустой. --}}
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">

        {{-- Ряд 1: название + состояние --}}
        <div class="mh-row">
            <span class="admin-icon-badge">@themeIcon('bars')</span>

            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ $menu->title }}
            </h1>

            {{-- ⚠️ Факт — НЕРАЗРЫВНАЯ группа. Строка переносится по любому
                 месту, и на телефоне подпись «Статус:» оставалась в одной
                 строке, а значение «Включено» уезжало в следующую: фраза
                 разрывалась пополам. Переносим между фактами, не внутри. --}}
            <span class="mi-fact mh-status inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.menu.status_label') }}
                @if($menu->active)
                    <span class="inline-flex items-center gap-1 text-green-600">
                        <span class="h-2 w-2 rounded-full bg-green-500 inline-block"></span> {{ __('admin.menu.enabled') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-gray-500">
                        <span class="h-2 w-2 rounded-full bg-gray-400 inline-block"></span> {{ __('admin.menu.disabled') }}
                    </span>
                @endif
            </span>
        </div>

        {{-- Ряд 2: позиция + возврат --}}
        <div class="mh-row mh-row--sub">
            <span class="mi-fact text-sm text-gray-500 dark:text-gray-400">
                📍 {{ __('admin.menu.position_label') }} <b>{{ $menu->position }}</b>
            </span>

            <a href="{{ route('admin.menus.index') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                @themeIcon('arrow-left') {{ __('admin.menu.back_to_list') }}
            </a>
        </div>
    </div>

    {{-- ───────────────────── Help block ───────────────────── --}}
    <div class="admin-note mb-6 p-4 text-sm">
        @themeIcon('lightbulb')
        {{ __('admin.menu.drag_hint_1') }}
        <span class="mi-hotkey"><kbd class="px-1.5 py-0.5 border border-indigo-300 bg-white dark:bg-gray-800">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 border border-indigo-300 bg-white dark:bg-gray-800">S</kbd> {{ __('admin.menu.drag_hint_2') }}</span>
    </div>

    {{-- ─────────────────── Add Item Form ─────────────────── --}}
    <form action="{{ route('admin.menu_items.store', $menu) }}" method="POST"
          class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-2xl p-6 mb-10">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Левая колонка --}}
            <div class="lg:col-span-2 space-y-5">
                {{-- Название --}}
                <div>
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🏷️ {{ __('admin.menu.item_name') }}</label>
                    <input type="text" name="title" id="mi-title" maxlength="80" required
                           class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="{{ __('admin.menu.item_name_ph') }}">
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ __('admin.menu.item_name_hint') }}</span>
                        <span><span id="mi-title-count">0</span>/80</span>
                    </div>
                </div>

                {{-- Тип --}}
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-gray-200">🔗 {{ __('admin.menu.item_type') }}</label>
                    <input type="hidden" name="type" id="typeHidden" value="url">
                    @php
                        $typeCards = [
                            ['key'=>'url','title'=>__('admin.menu.type_url'),'desc'=>__('admin.menu.type_url_desc'),'icon'=>'link'],
                            ['key'=>'page','title'=>__('admin.menu.type_page'),'desc'=>__('admin.menu.type_page_desc'),'icon'=>'file-text'],
                            ['key'=>'category','title'=>__('admin.menu.type_category'),'desc'=>__('admin.menu.type_category_desc'),'icon'=>'tags'],
                        ];
                    @endphp
                    <div class="grid sm:grid-cols-3 gap-3">
                        @foreach($typeCards as $c)
                            <button type="button" data-type="{{ $c['key'] }}"
                                    class="type-card relative text-left border p-4 transition
                                           {{ $loop->first ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900' : 'border-gray-300 dark:border-gray-700 hover:border-indigo-400' }}">
                                <div class="flex items-start gap-3">
                                    <span class="text-xl text-indigo-600 dark:text-indigo-400">@themeIcon($c['icon'])</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $c['title'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $c['desc'] }}</div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Родительский пункт --}}
                <div>
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🗂️ {{ __('admin.menu.parent') }}</label>
                    <select name="parent_id" id="mi-parent"
                            class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">{{ __('admin.menu.no_parent') }}</option>
                        @php
                            $flattenItems = function ($nodes, $depth = 0) use (&$flattenItems) {
                                $out = [];
                                foreach ($nodes as $node) {
                                    $out[] = ['item' => $node, 'depth' => $depth];
                                    if ($node->children && $node->children->count()) {
                                        $out = array_merge($out, $flattenItems($node->children, $depth + 1));
                                    }
                                }
                                return $out;
                            };
                            $flatMenuItems = $flattenItems($items);
                        @endphp
                        @foreach($flatMenuItems as $row)
                            <option value="{{ $row['item']->id }}">
                                {{ str_repeat('— ', $row['depth']) }}{{ $row['item']->title }}
                            </option>
                        @endforeach
                    </select>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('admin.menu.parent_hint') }}

                    </div>
                </div>

                {{-- Поле URL --}}
                <div id="field-url">
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🌐 URL</label>
                    <input type="text" name="url" id="mi-url"
                           class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="{{ __('admin.menu.url_ph') }}">
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.menu.url_hint') }}</div>
                </div>

                {{-- Связанный объект --}}
                <div id="field-linked" class="hidden">
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🔍 {{ __('admin.menu.linked') }}</label>
                    <select name="linked_id" id="linked-id"
                            class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">{{ __('admin.menu.choose') }}</option>
                    </select>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.menu.linked_hint') }}</div>
                </div>

                {{-- Дополнительные настройки --}}
                <div class="grid sm:grid-cols-2 gap-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    {{-- Активность --}}
                    <div>
                        <label class="inline-flex items-center gap-2.5 select-none cursor-pointer">
                            <span class="admin-toggle">
                                <input type="checkbox" name="active" value="1" checked>
                                <span class="track"></span>
                                <span class="knob"></span>
                            </span>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('admin.menu.show_on_site') }}</span>
                        </label>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.menu.show_hint') }}</div>
                    </div>

                    {{-- ── Значок пункта ────────────────────────────────────
                         Два способа задать одно и то же, поэтому они собраны
                         в один блок с явным «или»: раньше поле «Иконка» стояло
                         среди прочих, и было неочевидно, что туда пишут ИМЯ из
                         набора, а не путь к файлу.

                         Своя картинка нужна была для соцсетей: фирменных глифов
                         у нас четыре, а сетей бывает сколько угодно. Картинка
                         имеет приоритет над именем. --}}
                    <div class="sm:col-span-2 mi-icons">
                        <div class="mi-icons__head">
                            <span class="mi-icons__title">{{ __('admin.menu.icon') }}</span>
                            <span class="mi-icons__note">картинка важнее имени: загрузили — показывается она</span>
                        </div>

                        <div class="mi-icons__grid">
                            {{-- Имя значка из набора темы --}}
                            <div>
                                <label class="mi-label" for="mi-icon">Имя из набора</label>
                                <input type="text" name="icon" id="mi-icon" maxlength="50"
                                       class="mi-input" placeholder="{{ __('admin.menu.icon_ph') }}">
                                <p class="mi-hint">{{ __('admin.menu.icon_hint') }}</p>
                            </div>

                            {{-- Своя картинка --}}
                            <div>
                                <label class="mi-label">Своя картинка</label>

                                <div class="mi-file">
                                    <label class="mi-file__pick" for="mi-icon-image">
                                        @themeIcon('image')
                                        <span id="mi-icon-image-name">Выбрать файл</span>
                                    </label>
                                    <input type="file" name="icon_image" id="mi-icon-image" class="hidden"
                                           accept="image/png,image/jpeg,image/webp,image/gif">

                                    {{-- Превью загруженной картинки. Показывается
                                         и для уже сохранённых пунктов — иначе
                                         понять, что картинка есть, было нельзя. --}}
                                    <div id="mi-icon-image-box" class="mi-file__preview" hidden>
                                        <img id="mi-icon-image-prev" alt="">
                                        <button type="button" id="mi-icon-image-drop" class="mi-file__drop"
                                                title="Убрать картинку">
                                            @themeIcon('trash-alt')
                                        </button>
                                    </div>

                                    {{-- Флаг снятия: сам файловый input очистить
                                         недостаточно — сервер должен понять, что
                                         прежнюю картинку надо удалить. --}}
                                    <input type="hidden" name="remove_icon_image" id="mi-icon-image-remove" value="0">
                                </div>

                                <p class="mi-hint">PNG, JPEG, WebP или GIF, до 1 МБ и не больше 512×512.
                                    SVG не принимаем: он исполняет скрипт.</p>
                            </div>
                        </div>
                    </div>

                    {{-- CSS класс --}}
                    <div>
                        <label class="mi-label" for="mi-css-class">{{ __('admin.menu.css_class') }}</label>
                        <input type="text" name="css_class" id="mi-css-class" maxlength="255"
                               class="mi-input" placeholder="{{ __('admin.menu.css_ph') }}">
                        <p class="mi-hint">Свой класс для оформления — нужен, только если правите вёрстку.</p>
                    </div>

                    {{-- Target --}}
                    <div>
                        <label class="mi-label" for="mi-target">Как открывать</label>
                        <select name="target" id="mi-target" class="mi-input">
                            <option value="">{{ __('admin.menu.target_default') }}</option>
                            <option value="_self">{{ __('admin.menu.target_self') }}</option>
                            <option value="_blank">{{ __('admin.menu.target_blank') }}</option>
                        </select>
                        <p class="mi-hint">«В новой вкладке» — для ссылок на чужие сайты.</p>
                    </div>

                    {{-- Rel --}}
                    <div class="sm:col-span-2">
                        <label class="mi-label" for="mi-rel">{{ __('admin.menu.rel') }}</label>
                        <input type="text" name="rel" id="mi-rel" maxlength="100"
                               class="mi-input" placeholder="{{ __('admin.menu.rel_ph') }}">
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.menu.rel_hint') }}</div>
                    </div>
                </div>

                {{-- SEO --}}
                <div class="grid sm:grid-cols-3 gap-3 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">🧠 Meta Title</label>
                        <input type="text" name="meta_title"
                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Meta Description</label>
                        <input type="text" name="meta_description"
                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">🏷️ Meta Keywords</label>
                        <input type="text" name="meta_keywords"
                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Превью --}}
            <aside class="lg:col-span-1 space-y-3">
                <div class="rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">{{ __('admin.menu.item_preview') }}</div>
                    <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="font-medium text-gray-900 dark:text-white" id="pv-title">{{ __('admin.menu.test_link') }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('admin.menu.type_label') }} <span id="pv-type">url</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1" id="pv-url">—</div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('admin.menu.add_hint') }}
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                    @themeIcon('plus') {{ __('admin.menu.add_item') }}
                </button>
            </aside>
        </div>
    </form>

    {{-- ───────────── Toolbar над списком ───────────── --}}
    <div class="mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <button id="expand-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ __('admin.menu.expand_all') }}
            </button>
            <button id="collapse-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ __('admin.menu.collapse_all') }}
            </button>
            <button id="select-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ __('admin.menu.select_all') }}
            </button>
            <button id="deselect-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hidden">
                {{ __('admin.menu.deselect') }}
            </button>
            <div id="bulk-actions" class="hidden flex items-center gap-2">
                <button id="bulk-activate" class="px-3 py-1.5 rounded-md bg-green-600 text-white text-sm hover:bg-green-700">
                    {{ __('admin.menu.enable') }}
                </button>
                <button id="bulk-deactivate" class="px-3 py-1.5 rounded-md bg-yellow-600 text-white text-sm hover:bg-yellow-700">
                    {{ __('admin.menu.disable') }}
                </button>
                <button id="bulk-delete" class="px-3 py-1.5 rounded-md bg-red-600 text-white text-sm hover:bg-red-700">
                    {{ __('admin.delete') }}
                </button>
                <span id="selected-count" class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.menu.selected_count') }}</span>
            </div>
        </div>
        <div class="relative w-full sm:w-72">
            {{-- Инлайн-SVG лупа (как на списке меню): фиксированный размер, не зависит
                 от размера lucide-иконки темы, которая лезла на текст в узком поле. --}}
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                 width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
            </svg>
            <input id="filter-input" type="text" placeholder="{{ __('admin.menu.filter_ph') }}"
                   class="w-full border border-gray-300 dark:border-gray-700 rounded-md pl-10 pr-3 py-2 text-sm dark:bg-gray-800 dark:text-white
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        </div>
    </div>

    {{-- ─────────────────── Tree / Drag&Drop ─────────────────── --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 p-6">
        <ul id="menu-tree" class="space-y-2"></ul>

        {{-- Панель сохранения порядка. РАНЬШЕ была sticky bottom-3 и висела над
             нижними пунктами дерева, перехватывая мышь — из-за чего их нельзя
             было перетащить. Теперь статична, под списком: drag-and-drop работает
             по всей высоте, а быстрое сохранение — по Ctrl+S из любого места. --}}
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
            {{-- ⚠️ Подсказка про сочетание клавиш прячется на сенсорных: там
                 клавиатуры нет, и строка про Ctrl+S занимает место, ничего не
                 сообщая. Тот же приём уже применён к подписи Ctrl+K у поиска
                 в шапке. --}}
            <div class="mi-hotkey text-xs text-gray-500 dark:text-gray-400">
                @themeIcon('keyboard') {{ __('admin.menu.hotkey') }} <b>Ctrl + S</b> {{ __('admin.menu.hotkey_order') }}
            </div>
            <button id="save-order"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition inline-flex items-center gap-2">
                @themeIcon('save') {{ __('admin.menu.save_order') }}
            </button>
        </div>
    </div>

    {{-- Резервный список (как было) --}}
    @if ($items->isNotEmpty())
        <details class="mt-8">
            <summary class="cursor-pointer text-sm text-gray-500 dark:text-gray-400 hover:underline">{{ __('admin.menu.fallback') }}</summary>
            <div class="mt-3 space-y-2">
                @foreach ($items as $it)
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 text-sm">
                        <b class="text-gray-900 dark:text-white">{{ $it->title }}</b>
                        <span class="text-xs text-gray-500"> ({{ __('admin.menu.type_label') }} {{ $it->type }}, id: {{ $it->linked_id }})</span>
                    </div>
                @endforeach
            </div>
        </details>
    @endif
@endsection

@php
    // Нужен клиенту, чтобы правильно рисовать иконку каждого пункта в дереве
    // (см. пояснение у iconMarkup() ниже — @themeIcon внутри JS-шаблона
    // компилировался бы один раз на сервере, а не по данным конкретного пункта).
    // Тема берётся из общей переменной композера, а не отдельным запросом
    $iconMode = data_get(($activeTheme ?? null)?->config, 'icon_mode', 'lucide');
@endphp
@push('scripts')
<script src="{{ local_js('sortable.min.js') }}"></script>
<script>
/* --------- helpers --------- */
const $  = (sel, root=document) => root.querySelector(sel);
const $$ = (sel, root=document) => [...root.querySelectorAll(sel)];
const menuData = @json($items);
const iconMode = @json($iconMode);

// Переводы имён значков — те же, что у @themeIcon на сервере. Без них
// имя из базы («vk», «rutube») уходит в набор как есть, значок не
// рисуется, а в консоли копятся предупреждения — по одному на пункт.
const iconAliases = @json(\App\Providers\ThemeServiceProvider::aliasesFor($iconMode));

/* ✅ Шаблоны URL (будем подставлять ID на клиенте) */
const destroyUrlTmpl = @json(route('admin.menu_items.destroy', [$menu, '__ID__']));
const updateUrlTmpl = @json(route('admin.menu_items.update', [$menu, '__ID__']));
const csrf = @json(csrf_token());

// Экранирование перед вставкой пользовательских строк (title/icon) в innerHTML —
// title пункта меню вводит администратор, значит доверять ему как готовому
// HTML нельзя (иначе кавычки/теги в названии сломают вёрстку или выполнят JS).
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Рендер иконки пункта на клиенте по имени, введённому в поле «Иконка».
// Раньше тут стояло `@themeIcon('${item.icon}')` — но это Blade-директива,
// она компилируется В ОДИН PHP-вызов на сервере при рендере страницы, ещё до
// того как в браузере появится конкретный item из JS-цикла. В итоге сервер
// один раз резолвил иконку для буквальной строки "${item.icon}" (такой иконки
// не существует) и этот же результат подставлялся всем пунктам без разбора.
// Теперь имя иконки достаётся из данных пункта в момент отрисовки в браузере,
// а не на сервере, и учитывает активный icon_mode темы (так же, как и
// @themeIcon делает на бэкенде для остальных иконок этой же страницы).
function iconMarkup(name, cls) {
    const n = String(name || '').trim();
    if (!n) return '';
    // Сперва перевод, потом экранирование: переводим ИМЯ, а не разметку.
    const safeName = escapeHtml(iconAliases[n] || n);
    if (iconMode === 'lucide')    return `<i data-lucide="${safeName}" class="${cls}"></i>`;
    if (iconMode === 'bootstrap') return `<i class="bi bi-${safeName} ${cls}"></i>`;
    if (iconMode === 'remix')     return `<i class="ri-${safeName} ${cls}"></i>`;
    if (iconMode === 'tabler')    return `<i class="ti ti-${safeName} ${cls}"></i>`;
    return `<i class="fa-solid fa-${safeName} ${cls}"></i>`;
}

// Lucide рисует иконки, заменяя <i data-lucide="…"> на SVG вызовом
// createIcons(). Header/footer вызывают его один раз при загрузке страницы —
// но дерево меню и модалка строятся JS-ом ПОЗЖЕ (innerHTML), поэтому их
// data-lucide элементы этот ранний вызов не застаёт и под дефолтной темой
// (icon_mode = lucide) иконки дерева оставались невидимыми. Дёргаем повторно
// после каждой динамической вставки. Для FA/bootstrap/remix/tabler это no-op
// (они рисуются по CSS-классам без JS), для отсутствующего lucide — тоже.
function refreshThemeIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try { window.lucide.createIcons(); } catch (e) {}
    }
}

/* --------- live preview + counters --------- */
const titleInput = $('#mi-title'), titleCount = $('#mi-title-count');
const pvTitle = $('#pv-title'), pvType = $('#pv-type'), pvUrl = $('#pv-url');
const typeHidden = $('#typeHidden'), fieldUrl = $('#field-url'), fieldLinked = $('#field-linked');

const updateTitle = () => {
    titleCount.textContent = (titleInput.value || '').length;
    pvTitle.textContent = titleInput.value.trim() || @js(__('admin.menu.test_link'));
};
titleInput.addEventListener('input', updateTitle); updateTitle();

// type cards switcher
$$('.type-card').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const key = btn.dataset.type;
        typeHidden.value = key;
        pvType.textContent = key;

        $$('.type-card').forEach(b=>{
            b.classList.remove('border-indigo-500','bg-indigo-50','dark:bg-indigo-900');
            b.classList.add('border-gray-300','dark:border-gray-700');
        });
        btn.classList.remove('border-gray-300','dark:border-gray-700');
        btn.classList.add('border-indigo-500','bg-indigo-50','dark:bg-indigo-900');

        if (key === 'url') {
            fieldUrl.classList.remove('hidden');
            fieldLinked.classList.add('hidden');
        } else {
            fieldUrl.classList.add('hidden');
            fieldLinked.classList.remove('hidden');
            loadLinked(key);
        }
    });
});

$('#mi-url').addEventListener('input', e => pvUrl.textContent = e.target.value || '—');

// ajax load linked entities
function loadLinked(type){
    const select = $('#linked-id');
    select.innerHTML = '<option>' + @js(__('admin.menu.loading')) + '</option>';
    const url = type === 'page' ? @json(route('admin.ajax.pages')) : @json(route('admin.ajax.categories'));
    fetch(url).then(r=>r.json()).then(list=>{
        select.innerHTML = list.map(i=>`<option value="${i.id}">${i.title}</option>`).join('');
    }).catch(()=> select.innerHTML = '<option>' + @js(__('admin.menu.load_error')) + '</option>');
}

/* --------- build tree UI from data --------- */
function renderList(items, depth=0){
    const ul = document.createElement('ul');
    ul.className = 'space-y-2 ' + (depth ? 'pl-4' : '');

    items.forEach(item=>{
        const li = document.createElement('li');
        li.dataset.id = item.id;
        li.className = 'border rounded bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700';

        const hasChildren = item.children && item.children.length;

        const activeBadge = item.active !== false ?
          '<span class="text-xs px-1.5 py-0.5 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">' + @js(__('admin.menu.item_active')) + '</span>' :
          '<span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">' + @js(__('admin.menu.item_hidden')) + '</span>';
        const iconDisplay = item.icon ? `<span class="text-xs text-gray-400">${iconMarkup(item.icon, 'text-xs text-gray-400')}</span>` : '';

        li.innerHTML = `
          <div class="handle flex items-center justify-between px-3 py-2 ${hasChildren ? 'bg-indigo-50 dark:bg-indigo-900' : ''} ${item.active === false ? 'opacity-60' : ''}">
            {{-- flex-wrap: у пункта третьего уровня к строке добавляется
                 плашка «макс. уровень», и на узком экране она вылезала за
                 край — замер на 360 давал 46 пикселей прокрутки всей
                 странице. Видно это только на дереве с вложенностью. --}}
            <div class="flex flex-wrap items-center gap-2 flex-1 min-w-0">
              <input type="checkbox" class="item-checkbox" data-item-id="${item.id}" title="{{ __('admin.menu.pick_for_bulk') }}">
              <span class="text-gray-400 cursor-move">@themeIcon('grip-vertical')</span>
              <button type="button" class="toggle-btn ${hasChildren ? '' : 'invisible'} text-gray-500 hover:text-gray-700 dark:hover:text-gray-200" aria-label="{{ __('admin.menu.toggle_children') }}">
                @themeIcon('chevron-down')
              </button>
              ${iconDisplay}
              <span class="mi-name font-medium truncate">${escapeHtml(item.title)}</span>
              {{-- Тип, состояние и пометка уровня — ОДНОЙ группой. Раньше они
                   лежали в строке по отдельности, и на узком экране перенос
                   заставал их в разных местах: у пункта с коротким названием
                   плашка оставалась в строке, у длинного уезжала вниз, а у
                   третьего вниз уезжала только пометка уровня. Список из
                   четырёх пунктов выглядел собранным из четырёх разных
                   вёрсток. Группа переносится целиком — вид один. --}}
              <span class="mi-meta">
                <span class="text-xs text-gray-500">(${item.type})</span>
                ${activeBadge}
                ${depth >= 2 ? '<span class="text-xs px-1.5 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">' + @js(__('admin.menu.max_level')) + '</span>' : ''}
              </span>
            </div>

            <div class="flex items-center gap-1">
              <button type="button" class="edit-item-btn text-indigo-600 hover:text-indigo-700 text-sm" data-item-id="${item.id}" title="{{ __('admin.edit') }}">
                @themeIcon('edit')
              </button>
              <form method="POST" class="mi-del-form inline">
                <input type="hidden" name="_token" value="${csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="text-red-600 hover:text-red-700 text-sm" title="{{ __('admin.delete') }}">
                  @themeIcon('trash')
                </button>
              </form>
            </div>
          </div>
        `;

        // ✅ корректный action
        li.querySelector('.mi-del-form').action = destroyUrlTmpl.replace('__ID__', item.id);

        // Пустой список-приёмник под каждым пунктом — это зона, куда можно
        // ВЛОЖИТЬ другой пункт (создать новый уровень). Раньше у листьев он был
        // 'hidden' (display:none) → у SortableJS не было хитбокса, и вложение в
        // лист было невозможно: работало только переупорядочивание среди уже
        // видимых соседей. Теперь помечаем приёмник классом '.mi-childlist' и во
        // время перетаскивания показываем как drop-зону (CSS внизу файла).
        // Помечаем только там, где вложение ещё разрешено по глубине: пункт на
        // depth<=1 может принять ребёнка (это станет уровнем 2 или 3); у пункта
        // на depth==2 ребёнок был бы уже 4-м уровнем — такой приёмник оставляем
        // скрытым, чтобы не предлагать недопустимое вложение.
        const child = document.createElement('ul');
        child.className = 'pl-4 space-y-2 ' + (depth <= 1 ? 'mi-childlist' : 'hidden');
        li.appendChild(child);

        if (hasChildren) child.replaceWith(renderList(item.children, depth+1));
        ul.appendChild(li);
    });
    return ul;
}

const rootTree = renderList(menuData);
// Идентификатор переносим на новый список: старый узел заменяется целиком,
// и без этой строки за дерево нечем зацепиться ни стилями, ни отладкой.
rootTree.id = 'menu-tree';
document.getElementById('menu-tree').replaceWith(rootTree);
refreshThemeIcons(); // отрисовать lucide-иконки только что построенного дерева

// Подтверждение удаления — читаем название прямо из DOM (textContent уже
// безопасно декодирован браузером), а не пересобираем строку с title пункта
// заново: так не нужно отдельно экранировать её ещё и для контекста confirm().
rootTree.addEventListener('submit', (e) => {
    const form = e.target.closest('.mi-del-form');
    if (!form) return;
    const title = form.closest('li')?.querySelector('.handle .font-medium')?.textContent?.trim() || @js(__('admin.menu.this_item'));
    if (!confirm(@js(__('admin.menu.delete_item')).replace(':title', title))) {
        e.preventDefault();
    }
});

// expand / collapse controls
document.getElementById('expand-all').addEventListener('click', ()=>{
    rootTree.querySelectorAll('.toggle-btn').forEach(b=>{
        const ul = b.closest('li')?.querySelector(':scope > ul');
        if (ul) ul.classList.remove('hidden');
    });
});
document.getElementById('collapse-all').addEventListener('click', ()=>{
    rootTree.querySelectorAll('.toggle-btn').forEach(b=>{
        const ul = b.closest('li')?.querySelector(':scope > ul');
        if (ul) ul.classList.add('hidden');
    });
});
rootTree.addEventListener('click', (e)=>{
    const btn = e.target.closest('.toggle-btn');
    if (!btn) return;
    const ul = btn.closest('li')?.querySelector(':scope > ul');
    if (ul) ul.classList.toggle('hidden');
});

// filter
document.getElementById('filter-input').addEventListener('input', (e)=>{
    const q = e.target.value.trim().toLowerCase();
    rootTree.querySelectorAll('li').forEach(li=>{
        const txt = li.querySelector('.handle .font-medium')?.textContent.toLowerCase() || '';
        li.style.display = txt.includes(q) ? '' : 'none';
    });
});

// Sortable for each UL recursively
(function initSortable(ul, depth = 0){
    new Sortable(ul, {
        group: 'nested',
        animation: 150,
        handle: '.handle',
        // forceFallback: тащим мышью силами самого SortableJS, а не нативным
        // HTML5 drag-and-drop. Нативный DnD здесь не запускался (пункты не
        // перетаскивались вовсе): он требует draggable=true НА МОМЕНТ mousedown,
        // а SortableJS выставляет его уже после нажатия — в ряде браузеров жест
        // при этом не стартует. fallbackOnBody уже стоял, но без forceFallback
        // был мёртвой опцией — теперь замысел на fallback-режим завершён.
        forceFallback: true,
        fallbackTolerance: 3,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        // Позволяет «уронить» пункт в ПУСТОЙ дочерний список (вложение под лист):
        // курсор должен оказаться в пределах этого числа px от пустого приёмника.
        // Без этого пустой <ul> у SortableJS не считается целью для drop.
        emptyInsertThreshold: 15,
        // ⚠️ SortableJS применяет каждый из этих классов через
        // classList.add()/remove(), а тот принимает РОВНО ОДИН токен: класс с
        // пробелами кидает InvalidCharacterError прямо в _onDragStart и обрывает
        // перетаскивание — ровно поэтому drag-and-drop не работал (раньше здесь
        // стояли многосложные Tailwind-строки вида 'bg-indigo-50 dark:... border-...').
        // Поэтому тут только односложные имена, а вся раскраска — литеральным CSS
        // в блоке стилей внизу файла (в этой Tailwind-сборке bg-indigo-*/opacity
        // всё равно рендерятся не полностью — см. CLAUDE.md).
        ghostClass: 'mi-ghost',
        chosenClass: 'mi-chosen',
        dragClass: 'mi-drag',
        onStart: function(evt) {
            evt.item.classList.add('dragging');
            // Подсветить пустые списки-приёмники на всё время перетаскивания —
            // чтобы было видно, куда можно вложить пункт (см. CSS .mi-childlist).
            document.body.classList.add('mi-dragging');
            // Показываем индикатор глубины
            const currentDepth = getDepth(evt.item);
            if (currentDepth >= 2) {
                evt.item.querySelector('.handle')?.insertAdjacentHTML('afterbegin', 
                    '<span class="text-xs text-red-600 font-bold">' + @js(__('admin.menu.max_level_warn')) + '</span>');
            }
        },
        onEnd: function(evt) {
            evt.item.classList.remove('dragging');
            document.body.classList.remove('mi-dragging');
            // Удаляем индикатор
            evt.item.querySelector('.handle .text-red-600')?.remove();
            
            // Проверяем глубину после перемещения
            const newDepth = getDepth(evt.item);
            if (newDepth > 2) {
                toast(@js(__('admin.menu.depth_exceeded')), true);
                // Можно вернуть элемент обратно или просто предупредить
            }
        },
        onAdd: function(evt) {
            const depth = getDepth(evt.item);
            if (depth > 2) {
                toast(@js(__('admin.menu.depth_move')), true);
                // Можно отменить перемещение
            }
        }
    });
    ul.querySelectorAll(':scope > li > ul').forEach(childUl => initSortable(childUl, depth + 1));
})(rootTree);

function getDepth(element) {
    let depth = 0;
    let parent = element.parentElement;
    while (parent && parent !== rootTree) {
        if (parent.tagName === 'UL') {
            depth++;
        }
        parent = parent.parentElement;
    }
    return depth;
}

// collect order
function collect(ul, depth = 0){
    if (depth > 2) {
        toast(@js(__('admin.menu.depth_simple')), true);
        return [];
    }
    return [...ul.children].map((li, idx)=>{
        const item = { id: li.dataset.id, order: idx };
        const child = li.querySelector(':scope > ul');
        if (child && child.children.length) {
            item.children = collect(child, depth + 1);
        }
        return item;
    });
}

// save
async function saveOrder(){
    const items = collect(rootTree);
    const maxDepth = checkMaxDepth(items);
    if (maxDepth > 2) {
        toast(@js(__('admin.menu.depth_exceeded')), true);
        return;
    }
    
    const payload = { items };
    const btn = document.getElementById('save-order');
    btn.disabled = true; btn.classList.add('opacity-70');
    try {
        const response = await fetch(@json(route('admin.menus.updateOrder', $menu)), {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf},
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.success) {
            toast(@js(__('admin.menu.order_saved')));
        } else {
            toast(result.message || @js(__('admin.menu.save_error')), true);
        }
    } catch(e){ toast(@js(__('admin.menu.save_error')), true); }
    finally { btn.disabled = false; btn.classList.remove('opacity-70'); }
}

function checkMaxDepth(items, currentDepth = 0) {
    let maxDepth = currentDepth;
    items.forEach(item => {
        if (item.children && item.children.length > 0) {
            const childDepth = checkMaxDepth(item.children, currentDepth + 1);
            maxDepth = Math.max(maxDepth, childDepth);
        }
    });
    return maxDepth;
}
document.getElementById('save-order').addEventListener('click', saveOrder);
document.addEventListener('keydown', e=>{
    if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='s'){ e.preventDefault(); saveOrder(); }
});

// tiny toast
function toast(msg, err=false){
    const t = document.createElement('div');
    t.className = 'fixed bottom-6 right-6 z-50 px-3 py-2 shadow text-sm text-white ' +
        (err ? 'bg-red-600' : 'bg-green-600');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(()=> t.remove(), 1800);
}

// Редактирование пункта меню
let editingItemId = null;
const editModal = document.createElement('div');
editModal.id = 'edit-modal';
editModal.className = 'fixed inset-0 z-50 hidden items-center justify-center';
editModal.style.background = 'rgba(0,0,0,.5)'; // bg-black/50 не рендерится в этой Tailwind-сборке
editModal.innerHTML = `
    <div class="bg-white dark:bg-gray-900 p-6 max-w-2xl w-full mx-4 overflow-y-auto" style="max-height:90vh">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">✏️ {{ __('admin.menu.edit_item') }}</h3>
            <button type="button" class="close-edit-modal text-gray-400 hover:text-gray-600">@themeIcon('times')</button>
        </div>
        <form id="edit-item-form" class="space-y-3">
            <input type="hidden" name="_token" value="${csrf}">
            <input type="hidden" name="_method" value="PUT">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="mi-label" for="edit-title">{{ __('admin.menu.item_name') }}</label>
                    <input type="text" name="title" id="edit-title" required class="mi-input">
                    <p class="mi-hint">Подпись, которую увидит посетитель.</p>
                </div>
                {{-- Поле было подписано «Шаблон» — ключом admin.common.f_template,
                     взятым из совсем другой формы. Это не шаблон, а СПОСОБ
                     ссылки, и та же самая настройка в форме добавления выше
                     подписана «Тип пункта». Владелец справедливо спросил, что
                     означают «Страница» и «Категория». --}}
                <div>
                    <label class="mi-label" for="edit-type">{{ __('admin.menu.item_type') }}</label>
                    <select name="type" id="edit-type" class="mi-input">
                        <option value="url">{{ __('admin.menu.type_url') }}</option>
                        <option value="page">{{ __('admin.menu.type_page') }}</option>
                        <option value="category">{{ __('admin.menu.type_category') }}</option>
                    </select>
                    {{-- Пояснение показывается ТОЛЬКО для выбранного типа: три
                         описания сразу занимали четыре строки и вытягивали
                         модалку за край окна. --}}
                    <p class="mi-hint" data-type-hint="url">Адрес пишете сами — любой, внутренний или внешний.</p>
                    <p class="mi-hint" data-type-hint="page" hidden>Готовая страница из раздела «Страницы»: ссылка соберётся сама и не сломается при смене адреса.</p>
                    <p class="mi-hint" data-type-hint="category" hidden>Ссылка на подборку материалов выбранной категории.</p>
                </div>
                <div id="edit-url-field">
                    <label class="block text-sm font-semibold mb-1">🌐 URL</label>
                    <input type="text" name="url" id="edit-url" class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div id="edit-linked-field" class="hidden">
                    <label class="block text-sm font-semibold mb-1">🔍 {{ __('admin.menu.linked') }}</label>
                    <select name="linked_id" id="edit-linked-id" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="">{{ __('admin.menu.choose') }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold mb-1">🗂️ {{ __('admin.menu.parent') }}</label>
                    <select name="parent_id" id="edit-parent" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="">{{ __('admin.menu.no_parent') }}</option>
                    </select>
                </div>
                {{-- ── Значок пункта ────────────────────────────────────────
                     Два способа задать одно и то же, поэтому собраны в один
                     блок с явным «или»: раньше поле «Иконка» стояло среди
                     прочих, и было неочевидно, что туда пишут ИМЯ из набора
                     значков, а не путь к файлу.

                     Своя картинка понадобилась для соцсетей: фирменных глифов
                     у нас четыре, а сетей бывает сколько угодно. Картинка
                     важнее имени — загрузили, показывается она. --}}
                <div class="sm:col-span-2 mi-icons">
                    <div class="mi-icons__head">
                        <span class="mi-icons__title">{{ __('admin.menu.icon') }}</span>
                        <span class="mi-icons__note">картинка важнее имени: загрузили — показывается она</span>
                    </div>

                    <div class="mi-icons__grid">
                        <div>
                            <label class="mi-label" for="edit-icon">Имя из набора</label>
                            <input type="text" name="icon" id="edit-icon" class="mi-input"
                                   placeholder="{{ __('admin.menu.icon_ph') }}">
                            <p class="mi-hint">{{ __('admin.menu.icon_hint') }}</p>
                        </div>

                        <div>
                            <label class="mi-label">Своя картинка</label>

                            <div class="mi-file">
                                <label class="mi-file__pick" for="edit-icon-image">
                                    @themeIcon('image')
                                    <span id="edit-icon-image-name">Выбрать файл</span>
                                </label>
                                <input type="file" name="icon_image" id="edit-icon-image" class="hidden"
                                       accept="image/png,image/jpeg,image/webp,image/gif">

                                {{-- Превью показывается и для УЖЕ загруженной
                                     картинки: иначе понять, что она есть, было
                                     нельзя — и убрать её тоже. --}}
                                <div id="edit-icon-image-box" class="mi-file__preview" hidden>
                                    <img id="edit-icon-image-prev" alt="">
                                    <button type="button" id="edit-icon-image-drop" class="mi-file__drop"
                                            title="Убрать картинку">@themeIcon('trash-alt')</button>
                                </div>

                                {{-- Флаг снятия: очистить файловый input мало —
                                     сервер должен понять, что прежнюю картинку
                                     надо удалить с диска. --}}
                                <input type="hidden" name="remove_icon_image" id="edit-icon-image-remove" value="0">
                            </div>

                            <p class="mi-hint">PNG, JPEG, WebP или GIF, до 1 МБ и не больше 512×512.
                                SVG не принимаем: он исполняет скрипт.</p>
                        </div>
                    </div>
                </div>

                {{-- Редко нужные поля — под раскрытием. Открытыми они занимали
                     треть модалки, и та переставала помещаться в окно, хотя
                     трогают их единицы пунктов. --}}
                <details class="mi-extra sm:col-span-2">
                    <summary>Дополнительно — класс, поведение ссылки, rel</summary>

                    <div class="mi-extra__grid">
                        <div>
                            <label class="mi-label" for="edit-css-class">{{ __('admin.menu.css_class') }}</label>
                            <input type="text" name="css_class" id="edit-css-class" class="mi-input">
                            <p class="mi-hint">Нужен, только если правите вёрстку.</p>
                        </div>
                        <div>
                            <label class="mi-label" for="edit-target">Как открывать</label>
                            <select name="target" id="edit-target" class="mi-input">
                                <option value="">{{ __('admin.menu.item_default') }}</option>
                                <option value="_self">В том же окне</option>
                                <option value="_blank">В новой вкладке</option>
                            </select>
                            <p class="mi-hint">«В новой вкладке» — для чужих сайтов.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mi-label" for="edit-rel">Rel</label>
                            <input type="text" name="rel" id="edit-rel" class="mi-input"
                                   placeholder="{{ __('admin.menu.rel_ph') }}">
                            <p class="mi-hint">{{ __('admin.menu.rel_hint') }}</p>
                        </div>
                    </div>
                </details>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                        <span class="admin-toggle">
                            <input type="checkbox" name="active" value="1" id="edit-active">
                            <span class="track"></span>
                            <span class="knob"></span>
                        </span>
                        <span class="text-sm font-medium">{{ __('admin.menu.show_on_site') }}</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-2 justify-end mt-6">
                <button type="button" class="close-edit-modal px-4 py-2 rounded-md border text-sm">{{ __('admin.cancel') }}</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">{{ __('admin.save') }}</button>
            </div>
        </form>
    </div>
`;
document.body.appendChild(editModal);
refreshThemeIcons(); // иконки внутри только что вставленной модалки

// Обработчики для модального окна
rootTree.addEventListener('click', (e) => {
    const editBtn = e.target.closest('.edit-item-btn');
    if (!editBtn) return;
    
    const itemId = parseInt(editBtn.dataset.itemId);
    const item = findItemInTree(menuData, itemId);
    if (!item) return;
    
    editingItemId = itemId;
    openEditModal(item);
});

function findItemInTree(items, id) {
    for (const item of items) {
        if (item.id === id) return item;
        if (item.children) {
            const found = findItemInTree(item.children, id);
            if (found) return found;
        }
    }
    return null;
}

// Плоский список пунктов (с глубиной — для отступов в <select>)
function flattenTree(items, depth = 0) {
    const out = [];
    items.forEach(item => {
        out.push({ item, depth });
        if (item.children && item.children.length) {
            out.push(...flattenTree(item.children, depth + 1));
        }
    });
    return out;
}

// Сам пункт и все его потомки нельзя выбрать себе в родители — иначе
// получится цикл (то же самое server-side уже проверяет update(), но
// на клиенте эти варианты лучше вообще не показывать в списке).
function collectDescendantIds(item) {
    const ids = [];
    (item.children || []).forEach(child => {
        ids.push(child.id);
        ids.push(...collectDescendantIds(child));
    });
    return ids;
}

// Пояснение под списком типов показывается только для выбранного: три
// описания сразу вытягивали модалку за край окна.
function syncTypeHint() {
    const select = document.getElementById('edit-type');

    if (!select) return;

    document.querySelectorAll('[data-type-hint]').forEach(el => {
        el.hidden = el.dataset.typeHint !== select.value;
    });
}

document.getElementById('edit-type')?.addEventListener('change', syncTypeHint);

// ── Своя картинка значка ─────────────────────────────────────────────
// Один набор обработчиков на обе формы (добавление и правка): поля у них
// одинаковые, и вторая копия кода неминуемо разошлась бы с первой.
function setIconImage(prefix, url) {
    const box  = document.getElementById(prefix + '-icon-image-box');
    const img  = document.getElementById(prefix + '-icon-image-prev');
    const name = document.getElementById(prefix + '-icon-image-name');
    const drop = document.getElementById(prefix + '-icon-image-remove');
    const file = document.getElementById(prefix + '-icon-image');

    if (!box) return;

    if (url) {
        img.src = url;
        box.hidden = false;
        if (name) name.textContent = 'Заменить файл';
    } else {
        img.removeAttribute('src');
        box.hidden = true;
        if (name) name.textContent = 'Выбрать файл';
        if (file) file.value = '';
    }

    if (drop) drop.value = '0';
}

function bindIconImage(prefix) {
    const file = document.getElementById(prefix + '-icon-image');
    const drop = document.getElementById(prefix + '-icon-image-drop');
    const flag = document.getElementById(prefix + '-icon-image-remove');

    if (!file) return;

    file.addEventListener('change', () => {
        const chosen = file.files && file.files[0];

        if (!chosen) { return; }

        // Показываем выбранный файл ДО отправки: иначе непонятно, что
        // именно выбрано, пока форма не сохранена.
        setIconImage(prefix, URL.createObjectURL(chosen));
        if (flag) flag.value = '0';
    });

    if (drop) {
        drop.addEventListener('click', () => {
            setIconImage(prefix, '');
            // Снятие — намерение, а не пустое поле: сервер должен удалить
            // прежний файл с диска.
            if (flag) flag.value = '1';
        });
    }
}

['mi', 'edit'].forEach(bindIconImage);

function openEditModal(item) {
    $('#edit-title').value = item.title || '';
    $('#edit-type').value = item.type || 'url';
    $('#edit-url').value = item.url || '';
    $('#edit-icon').value = item.icon || '';
    // Уже загруженная картинка: показываем её, иначе понять, что она есть,
    // было нельзя — и убрать тоже.
    setIconImage('edit', item.icon_image_url || '');
    $('#edit-css-class').value = item.css_class || '';
    $('#edit-target').value = item.target || '';
    $('#edit-rel').value = item.rel || '';
    $('#edit-active').checked = item.active !== false;
    $('#edit-linked-id').value = item.linked_id || '';

    const excluded = new Set([item.id, ...collectDescendantIds(item)]);
    const parentSelect = $('#edit-parent');
    parentSelect.innerHTML = '<option value="">{{ __('admin.menu.no_parent') }}</option>' +
        flattenTree(menuData)
            .filter(row => !excluded.has(row.item.id))
            .map(row => `<option value="${row.item.id}">${'— '.repeat(row.depth)}${escapeHtml(row.item.title)}</option>`)
            .join('');
    parentSelect.value = item.parent_id || '';

    syncTypeHint();

    // Показать/скрыть поля в зависимости от типа
    if (item.type === 'url') {
        $('#edit-url-field').classList.remove('hidden');
        $('#edit-linked-field').classList.add('hidden');
    } else {
        $('#edit-url-field').classList.add('hidden');
        $('#edit-linked-field').classList.remove('hidden');
        loadLinkedForEdit(item.type, item.linked_id);
    }
    
    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
}

function loadLinkedForEdit(type, selectedId) {
    const select = $('#edit-linked-id');
    select.innerHTML = '<option>' + @js(__('admin.menu.loading')) + '</option>';
    const url = type === 'page' ? @json(route('admin.ajax.pages')) : @json(route('admin.ajax.categories'));
    fetch(url).then(r=>r.json()).then(list=>{
        select.innerHTML = '<option value="">{{ __('admin.menu.choose') }}</option>' + 
            list.map(i=>`<option value="${i.id}" ${i.id == selectedId ? 'selected' : ''}>${i.title}</option>`).join('');
    }).catch(()=> select.innerHTML = '<option>' + @js(__('admin.menu.load_error')) + '</option>');
}

$('#edit-type').addEventListener('change', (e) => {
    if (e.target.value === 'url') {
        $('#edit-url-field').classList.remove('hidden');
        $('#edit-linked-field').classList.add('hidden');
    } else {
        $('#edit-url-field').classList.add('hidden');
        $('#edit-linked-field').classList.remove('hidden');
        loadLinkedForEdit(e.target.value);
    }
});

$$('.close-edit-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
        editingItemId = null;
    });
});

$('#edit-item-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!editingItemId) return;
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch(updateUrlTmpl.replace('__ID__', editingItemId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
            },
            body: formData,
        });
        
        if (response.ok) {
            toast(@js(__('admin.menu.item_updated')));
            setTimeout(() => location.reload(), 500);
        } else {
            const text = await response.text();
            toast(@js(__('admin.menu.update_error')) + ' ' + text.substring(0, 100), true);
        }
    } catch (err) {
        toast(@js(__('admin.menu.update_error')), true);
    }
});

// Массовые операции
let selectedItems = new Set();

function updateBulkActions() {
    const count = selectedItems.size;
    const bulkActions = $('#bulk-actions');
    const deselectBtn = $('#deselect-all');
    
    if (count > 0) {
        bulkActions.classList.remove('hidden');
        deselectBtn.classList.remove('hidden');
        $('#selected-count').textContent = count + ' ' + @js(__('admin.menu.selected_word'));
    } else {
        bulkActions.classList.add('hidden');
        deselectBtn.classList.add('hidden');
    }
}

rootTree.addEventListener('change', (e) => {
    if (e.target.classList.contains('item-checkbox')) {
        const itemId = parseInt(e.target.dataset.itemId);
        if (e.target.checked) {
            selectedItems.add(itemId);
        } else {
            selectedItems.delete(itemId);
        }
        updateBulkActions();
    }
});

$('#select-all').addEventListener('click', () => {
    rootTree.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = true;
        selectedItems.add(parseInt(cb.dataset.itemId));
    });
    updateBulkActions();
});

$('#deselect-all').addEventListener('click', () => {
    rootTree.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = false;
    });
    selectedItems.clear();
    updateBulkActions();
});

// Раньше активация/деактивация тут были no-op: показывали тост об успехе и
// просто перезагружали страницу, ничего не меняя в БД (комментарий так и
// гласил: «Пока просто перезагружаем страницу»). Теперь один запрос к
// admin.menu_items.bulk реально применяет действие на сервере.
const bulkUrlTmpl = @json(route('admin.menu_items.bulk', $menu));

async function bulkAction(action) {
    if (selectedItems.size === 0) {
        toast(@js(__('admin.menu.pick_item')), true);
        return;
    }

    const itemIds = Array.from(selectedItems);
    const actionText = action === 'activate' ? @js(__('admin.menu.act_enable')) : action === 'deactivate' ? @js(__('admin.menu.act_disable')) : @js(__('admin.menu.act_delete'));

    if (!confirm(@js(__('admin.menu.bulk_confirm')).replace(':action', actionText).replace(':count', itemIds.length))) {
        return;
    }

    try {
        const response = await fetch(bulkUrlTmpl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ action, ids: itemIds }),
        });
        const result = await response.json().catch(() => ({}));

        if (response.ok && result.success) {
            toast(@js(__('admin.menu.bulk_done')).replace(':count', result.count ?? itemIds.length));
            setTimeout(() => location.reload(), 600);
        } else {
            toast(result.message || @js(__('admin.menu.bulk_error')), true);
        }
    } catch (err) {
        toast(@js(__('admin.menu.bulk_error')), true);
    }
}

$('#bulk-activate').addEventListener('click', () => bulkAction('activate'));
$('#bulk-deactivate').addEventListener('click', () => bulkAction('deactivate'));
$('#bulk-delete').addEventListener('click', () => bulkAction('delete'));
</script>
@endpush

@push('styles')
<style>
    /* ── Шапка раздела: два ряда ──────────────────────────────
       Ряд 1 — название и состояние, ряд 2 — позиция и возврат. Значок
       стоит слева от названия, состояние прижато к правому краю: так у
       обоих рядов один правый край и шапка читается таблицей, а не
       набором строк. */
    /* ⚠️ Селектор усилен намеренно. Общее правило панели делает шапку
       раздела строкой с переносом (`body main .admin-glass`, сила 0-2-2) —
       оно сильнее одиночного класса, и два ряда вставали в одну строку на
       широком экране. Здесь ряды заданы явно, поэтому перенос не нужен. */
    body main .admin-glass.mh{ display:flex; flex-direction:column;
        flex-wrap:nowrap; align-items:stretch; gap:.4rem }
    .mh-row{ display:flex; align-items:center; gap:.6rem; min-width:0 }
    /* Базис ноль, иначе длинное название не сжимается, а выталкивает
       состояние на следующую строку (та же ловушка, что в списке пунктов). */
    .mh-title{ flex:1 1 0; min-width:0 }
    .mh-status{ flex:none }
    .mh-row--sub{ justify-content:space-between; padding-left:2.9rem }
    .mh-back{ flex:none }

    @media (max-width: 480px){
        /* На узком экране отступ под значок съедает место у позиции. */
        .mh-row--sub{ padding-left:0 }
    }

    /* Факт в строке состояния не разрывается посередине. */
    .mi-fact{ white-space:nowrap }

    /* Сочетания клавиш — только там, где есть клавиатура. */
    @media (max-width: 1024px), (max-height: 500px){
        .mi-hotkey{ display:none }
    }

    /* Строка пункта: название и его пометки. */
    .mi-meta{ display:inline-flex; align-items:center; gap:.35rem; flex:none }

    @media (max-width: 1024px), (max-height: 500px){
        /* Пометки — своей строкой под названием, с отступом под значок
           перетаскивания: так все пункты выглядят одинаково независимо от
           длины названия. */
        .mi-meta{ flex:0 0 100%; padding-left:4.3rem }

        /* ⚠️ Базис НОЛЬ, а не auto. При `flex:1 1 auto` основой служит
           содержимое: длинное название не влезало рядом со значком и
           переезжало на новую строку целиком — у короткого названия значок и
           текст стояли в строке, у длинного значок оставался один, а текст
           уходил под него. Владелец прислал снимок именно с этим. С нулевым
           базисом название сжимается и усекается многоточием, оставаясь в
           строке при любой длине. */
        .mi-name{ flex:1 1 0; min-width:0 }
    }

    .dragging {
        opacity: 0.5;
        transform: rotate(2deg);
    }
    /* Односложные классы для SortableJS (ghostClass / chosenClass / dragClass).
       Раскраска держится тут литеральным CSS: имя класса с пробелами ломает
       classList.add()/remove() внутри Sortable, а indigo-утилиты с прозрачностью
       в этой Tailwind-сборке всё равно не все рендерятся. */
    .mi-ghost {
        opacity: 0.5;
        background: #e0e7ff;   /* indigo-100 */
    }
    .mi-chosen {
        background: #eef2ff;   /* indigo-50  */
        border-color: #818cf8; /* indigo-400 */
    }
    .mi-drag {
        opacity: 0.5;
    }
    /* Пустой список-приёмник для вложения. Обычно схлопнут (0px, не мешает
       вёрстке), а во время перетаскивания (body.mi-dragging) проявляется
       пунктирной зоной, куда можно вложить пункт и создать новый уровень.
       display:block !important перебивает .hidden, если приёмник свернули
       кнопкой «Свернуть всё». */
    .mi-childlist:empty { min-height: 0; }
    body.mi-dragging .mi-childlist:empty {
        display: block !important;
        min-height: 30px;
        margin-top: 4px;
        border: 1px dashed #a5b4fc;   /* indigo-300 */
        background: #eef2ff;          /* indigo-50  */
    }
</style>
@endpush

@push('styles')
<style>
    /* ── Поля пункта меню ─────────────────────────────────────────────
       Литеральный CSS: в собранном tailwind.min.css этого проекта нет ни
       произвольных значений, ни прозрачности через дробь. */

    .mi-label{ display:block; margin-bottom:.25rem; font-size:.78rem; font-weight:600; color:#374151 }
    .dark .mi-label{ color:#d1d5db }

    .mi-input{ display:block; width:100%; padding:.5rem .7rem; font-size:.85rem;
        color:#111827; background:#fff; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s }
    .mi-input:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .dark .mi-input{ color:#f3f4f6; background:#111827; border-color:#374151 }

    /* Пояснение под полем: раньше по названию «Rel» или «Target» понять,
       что туда писать, было нельзя. */
    .mi-hint{ margin-top:.25rem; font-size:.72rem; line-height:1.4; color:#6b7280 }
    .dark .mi-hint{ color:#9ca3af }

    /* Блок значка: имя ИЛИ картинка */
    .mi-icons{ padding:.75rem; border:1px solid #e5e7eb; background:#f9fafb }
    .dark .mi-icons{ border-color:#374151; background:#111827 }
    .mi-icons__head{ display:flex; flex-wrap:wrap; align-items:baseline; gap:.5rem; margin-bottom:.6rem }
    .mi-icons__title{ font-size:.78rem; font-weight:700; color:#374151 }
    .dark .mi-icons__title{ color:#d1d5db }
    .mi-icons__note{ font-size:.7rem; color:#6b7280; font-style:italic }
    .mi-icons__grid{ display:grid; gap:.75rem }
    @media (min-width:640px){ .mi-icons__grid{ grid-template-columns:1fr 1fr } }

    /* Выбор файла */
    .mi-file{ display:flex; align-items:center; gap:.5rem }
    .mi-file__pick{ display:inline-flex; align-items:center; gap:.4rem; flex:1;
        padding:.45rem .6rem; font-size:.78rem; font-weight:600; cursor:pointer;
        color:#374151; background:#fff; border:1px dashed #cbd5e1;
        transition:border-color .15s, color .15s }
    .mi-file__pick:hover{ border-color:var(--admin-primary); color:var(--admin-primary) }
    .dark .mi-file__pick{ color:#d1d5db; background:#1f2937; border-color:#4b5563 }
    .mi-file__pick svg, .mi-file__pick i{ width:.95rem; height:.95rem }

    .mi-file__preview{ position:relative; flex:none; width:2.6rem; height:2.6rem;
        border:1px solid #e5e7eb; background:#fff }
    .dark .mi-file__preview{ border-color:#374151; background:#1f2937 }
    .mi-file__preview img{ width:100%; height:100%; object-fit:contain; padding:2px }
    .mi-file__preview[hidden]{ display:none }

    .mi-file__drop{ position:absolute; top:-.4rem; right:-.4rem;
        display:inline-flex; align-items:center; justify-content:center;
        width:1.2rem; height:1.2rem; cursor:pointer; color:#fff; background:#dc2626; border:0 }
    .mi-file__drop svg, .mi-file__drop i{ width:.65rem; height:.65rem }

    /* Редкие поля под раскрытием */
    .mi-extra{ border:1px solid #e5e7eb; background:#f9fafb }
    .dark .mi-extra{ border-color:#374151; background:#111827 }
    .mi-extra > summary{ padding:.5rem .7rem; font-size:.78rem; font-weight:600;
        color:#4b5563; cursor:pointer; list-style:none }
    .dark .mi-extra > summary{ color:#d1d5db }
    .mi-extra > summary::-webkit-details-marker{ display:none }
    .mi-extra > summary::before{ content:'\25B8'; display:inline-block; margin-right:.4rem;
        transition:transform .15s }
    .mi-extra[open] > summary::before{ transform:rotate(90deg) }
    .mi-extra__grid{ display:grid; gap:.7rem; padding:0 .7rem .7rem }
    @media (min-width:640px){ .mi-extra__grid{ grid-template-columns:1fr 1fr } }
    .mi-extra__grid > .sm\:col-span-2{ grid-column:1 / -1 }

    /* ── Вложенность на телефонах и планшетах ─────────────────────────
       Шаг отступа — pl-4, то есть 16 пикселей. На широком экране рядом
       ещё и поля пункта, подчинённость видно и так; на телефоне карточки
       занимают всю ширину, и шаг в 16 на их фоне почти не читается —
       замер давал уровни на 41, 58 и 75 при ширине окна 414.
       Шаг крупнее плюс направляющая линия: она показывает ветку целиком,
       а не только сдвиг одной строки. */
    @media (max-width: 1024px), (max-height: 500px){
        #menu-tree ul.pl-4{
            padding-left:1.5rem;
            margin-left:.65rem;
            border-left:2px solid color-mix(in srgb, var(--admin-primary, #6366f1) 30%, transparent);
        }
    }
</style>
@endpush
