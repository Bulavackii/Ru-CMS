@extends('layouts.admin')

@section('title', 'Редактировать меню')

@section('content')
    {{-- ───────────────────────── Header ───────────────────────── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge">@themeIcon('bars')</span>
            <div class="min-w-0 space-y-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                    {{ $menu->title }}
                </h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span>📍 Позиция: <b>{{ $menu->position }}</b></span>
                    <span class="inline-flex items-center gap-1">
                        Статус:
                        @if($menu->active)
                            <span class="inline-flex items-center gap-1 text-green-600">
                                <span class="h-2 w-2 rounded-full bg-green-500 inline-block"></span> Включено
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-gray-500">
                                <span class="h-2 w-2 rounded-full bg-gray-400 inline-block"></span> Выключено
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.menus.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 shrink-0">
            @themeIcon('arrow-left') Назад к списку
        </a>
    </div>

    {{-- ───────────────────── Help block ───────────────────── --}}
    <div class="admin-hint mb-6 p-4 text-sm">
        @themeIcon('lightbulb')
        Перетаскивайте пункты мышью за «ручку» слева, чтобы менять порядок и вложенность. Нажмите
        <kbd class="px-1.5 py-0.5 border border-indigo-300 bg-white dark:bg-gray-800">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 border border-indigo-300 bg-white dark:bg-gray-800">S</kbd> — быстрое сохранение порядка.
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
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🏷️ Название</label>
                    <input type="text" name="title" id="mi-title" maxlength="80" required
                           class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Например: Тестовая ссылка">
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Понятное имя, видимое на сайте.</span>
                        <span><span id="mi-title-count">0</span>/80</span>
                    </div>
                </div>

                {{-- Тип --}}
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-gray-200">🔗 Тип пункта</label>
                    <input type="hidden" name="type" id="typeHidden" value="url">
                    @php
                        $typeCards = [
                            ['key'=>'url','title'=>'Внешняя ссылка','desc'=>'Произвольный URL','icon'=>'link'],
                            ['key'=>'page','title'=>'Страница','desc'=>'Ссылка на внутреннюю страницу','icon'=>'file-alt'],
                            ['key'=>'category','title'=>'Категория','desc'=>'Ссылка на категорию','icon'=>'tags'],
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
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🗂️ Родительский пункт</label>
                    <select name="parent_id" id="mi-parent"
                            class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Без родителя (корневой уровень) —</option>
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
                        Оставьте пустым для корневого уровня. Пункт встанет последним среди пунктов выбранного уровня.
                        Максимальная вложенность — 3 уровня.
                    </div>
                </div>

                {{-- Поле URL --}}
                <div id="field-url">
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🌐 URL</label>
                    <input type="text" name="url" id="mi-url"
                           class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="https://example.com или /contacts">
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Поддерживаются абсолютные и относительные адреса.</div>
                </div>

                {{-- Связанный объект --}}
                <div id="field-linked" class="hidden">
                    <label class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">🔍 Связанный объект</label>
                    <select name="linked_id" id="linked-id"
                            class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Выберите —</option>
                    </select>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Список подгружается автоматически по выбранному типу.</div>
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
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Показывать на сайте</span>
                        </label>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Выключенный пункт остаётся в меню, но не отображается посетителям.</div>
                    </div>

                    {{-- Иконка --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">🎨 Иконка</label>
                        <input type="text" name="icon" id="mi-icon" maxlength="50"
                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"
                               placeholder="Например: home, user, settings">
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Название иконки (FontAwesome/Tabler)</div>
                    </div>

                    {{-- CSS класс --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">🎨 CSS класс</label>
                        <input type="text" name="css_class" id="mi-css-class" maxlength="255"
                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"
                               placeholder="Например: btn-primary">
                    </div>

                    {{-- Target --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">🔗 Target</label>
                        <select name="target" id="mi-target"
                                class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white">
                            <option value="">По умолчанию (_self)</option>
                            <option value="_self">_self (текущее окно)</option>
                            <option value="_blank">_blank (новое окно)</option>
                        </select>
                    </div>

                    {{-- Rel --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300">🔗 Rel атрибут</label>
                        <input type="text" name="rel" id="mi-rel" maxlength="100"
                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"
                               placeholder="Например: nofollow noopener">
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Для SEO и безопасности ссылок</div>
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
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">Превью пункта</div>
                    <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="font-medium text-gray-900 dark:text-white" id="pv-title">Тестовая ссылка</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Тип: <span id="pv-type">url</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1" id="pv-url">—</div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Пункт добавится в конец текущего уровня.
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                    @themeIcon('plus') Добавить пункт
                </button>
            </aside>
        </div>
    </form>

    {{-- ───────────── Toolbar над списком ───────────── --}}
    <div class="mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <button id="expand-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                Развернуть всё
            </button>
            <button id="collapse-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                Свернуть всё
            </button>
            <button id="select-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                Выбрать всё
            </button>
            <button id="deselect-all" class="px-3 py-1.5 rounded-md border text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hidden">
                Снять выбор
            </button>
            <div id="bulk-actions" class="hidden flex items-center gap-2">
                <button id="bulk-activate" class="px-3 py-1.5 rounded-md bg-green-600 text-white text-sm hover:bg-green-700">
                    Активировать
                </button>
                <button id="bulk-deactivate" class="px-3 py-1.5 rounded-md bg-yellow-600 text-white text-sm hover:bg-yellow-700">
                    Деактивировать
                </button>
                <button id="bulk-delete" class="px-3 py-1.5 rounded-md bg-red-600 text-white text-sm hover:bg-red-700">
                    Удалить
                </button>
                <span id="selected-count" class="text-sm text-gray-600 dark:text-gray-400">0 выбрано</span>
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
            <input id="filter-input" type="text" placeholder="Быстрый поиск по пунктам…"
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
            <div class="text-xs text-gray-500 dark:text-gray-400">
                @themeIcon('keyboard') Горячая клавиша: <b>Ctrl + S</b> — сохранить порядок
            </div>
            <button id="save-order"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition inline-flex items-center gap-2">
                @themeIcon('save') Сохранить порядок
            </button>
        </div>
    </div>

    {{-- Резервный список (как было) --}}
    @if ($items->isNotEmpty())
        <details class="mt-8">
            <summary class="cursor-pointer text-sm text-gray-500 dark:text-gray-400 hover:underline">Показать резервный список</summary>
            <div class="mt-3 space-y-2">
                @foreach ($items as $it)
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 text-sm">
                        <b class="text-gray-900 dark:text-white">{{ $it->title }}</b>
                        <span class="text-xs text-gray-500"> (тип: {{ $it->type }}, id: {{ $it->linked_id }})</span>
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
    const safeName = escapeHtml(n);
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
    pvTitle.textContent = titleInput.value.trim() || 'Тестовая ссылка';
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
    select.innerHTML = '<option>Загрузка…</option>';
    const url = type === 'page' ? @json(route('admin.ajax.pages')) : @json(route('admin.ajax.categories'));
    fetch(url).then(r=>r.json()).then(list=>{
        select.innerHTML = list.map(i=>`<option value="${i.id}">${i.title}</option>`).join('');
    }).catch(()=> select.innerHTML = '<option>Ошибка загрузки</option>');
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
          '<span class="text-xs px-1.5 py-0.5 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Активен</span>' :
          '<span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Скрыт</span>';
        const iconDisplay = item.icon ? `<span class="text-xs text-gray-400">${iconMarkup(item.icon, 'text-xs text-gray-400')}</span>` : '';

        li.innerHTML = `
          <div class="handle flex items-center justify-between px-3 py-2 ${hasChildren ? 'bg-indigo-50 dark:bg-indigo-900' : ''} ${item.active === false ? 'opacity-60' : ''}">
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <input type="checkbox" class="item-checkbox" data-item-id="${item.id}" title="Выбрать для массовых операций">
              <span class="text-gray-400 cursor-move">@themeIcon('grip-vertical')</span>
              <button type="button" class="toggle-btn ${hasChildren ? '' : 'invisible'} text-gray-500 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Переключить дочерние">
                @themeIcon('chevron-down')
              </button>
              ${iconDisplay}
              <span class="font-medium truncate">${escapeHtml(item.title)}</span>
              <span class="text-xs text-gray-500">(${item.type})</span>
              ${activeBadge}
              ${depth >= 2 ? '<span class="text-xs px-1.5 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">Макс. уровень</span>' : ''}
            </div>

            <div class="flex items-center gap-1">
              <button type="button" class="edit-item-btn text-indigo-600 hover:text-indigo-700 text-sm" data-item-id="${item.id}" title="Редактировать">
                @themeIcon('edit')
              </button>
              <form method="POST" class="mi-del-form inline">
                <input type="hidden" name="_token" value="${csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="text-red-600 hover:text-red-700 text-sm" title="Удалить">
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
document.getElementById('menu-tree').replaceWith(rootTree);
refreshThemeIcons(); // отрисовать lucide-иконки только что построенного дерева

// Подтверждение удаления — читаем название прямо из DOM (textContent уже
// безопасно декодирован браузером), а не пересобираем строку с title пункта
// заново: так не нужно отдельно экранировать её ещё и для контекста confirm().
rootTree.addEventListener('submit', (e) => {
    const form = e.target.closest('.mi-del-form');
    if (!form) return;
    const title = form.closest('li')?.querySelector('.handle .font-medium')?.textContent?.trim() || 'этот пункт';
    if (!confirm(`Удалить пункт «${title}»?`)) {
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
                    '<span class="text-xs text-red-600 font-bold">⚠️ Макс. уровень!</span>');
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
                toast('⚠️ Превышена максимальная глубина вложенности (3 уровня)!', true);
                // Можно вернуть элемент обратно или просто предупредить
            }
        },
        onAdd: function(evt) {
            const depth = getDepth(evt.item);
            if (depth > 2) {
                toast('⚠️ Нельзя переместить: превышена максимальная глубина!', true);
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
        toast('⚠️ Превышена максимальная глубина вложенности!', true);
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
        toast('⚠️ Превышена максимальная глубина вложенности (3 уровня)!', true);
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
            toast('Порядок сохранён');
        } else {
            toast(result.message || 'Ошибка сохранения', true);
        }
    } catch(e){ toast('Ошибка сохранения', true); }
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
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">✏️ Редактировать пункт меню</h3>
            <button type="button" class="close-edit-modal text-gray-400 hover:text-gray-600">@themeIcon('times')</button>
        </div>
        <form id="edit-item-form" class="space-y-4">
            <input type="hidden" name="_token" value="${csrf}">
            <input type="hidden" name="_method" value="PUT">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">🏷️ Название</label>
                    <input type="text" name="title" id="edit-title" required class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">🔗 Тип</label>
                    <select name="type" id="edit-type" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="url">URL</option>
                        <option value="page">Страница</option>
                        <option value="category">Категория</option>
                    </select>
                </div>
                <div id="edit-url-field">
                    <label class="block text-sm font-semibold mb-1">🌐 URL</label>
                    <input type="text" name="url" id="edit-url" class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div id="edit-linked-field" class="hidden">
                    <label class="block text-sm font-semibold mb-1">🔍 Связанный объект</label>
                    <select name="linked_id" id="edit-linked-id" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="">— Выберите —</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold mb-1">🗂️ Родительский пункт</label>
                    <select name="parent_id" id="edit-parent" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="">— Без родителя (корневой уровень) —</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">🎨 Иконка</label>
                    <input type="text" name="icon" id="edit-icon" class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">🎨 CSS класс</label>
                    <input type="text" name="css_class" id="edit-css-class" class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">🔗 Target</label>
                    <select name="target" id="edit-target" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="">По умолчанию</option>
                        <option value="_self">_self</option>
                        <option value="_blank">_blank</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">🔗 Rel</label>
                    <input type="text" name="rel" id="edit-rel" class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                        <span class="admin-toggle">
                            <input type="checkbox" name="active" value="1" id="edit-active">
                            <span class="track"></span>
                            <span class="knob"></span>
                        </span>
                        <span class="text-sm font-medium">Показывать на сайте</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-2 justify-end mt-6">
                <button type="button" class="close-edit-modal px-4 py-2 rounded-md border text-sm">Отмена</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">Сохранить</button>
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

function openEditModal(item) {
    $('#edit-title').value = item.title || '';
    $('#edit-type').value = item.type || 'url';
    $('#edit-url').value = item.url || '';
    $('#edit-icon').value = item.icon || '';
    $('#edit-css-class').value = item.css_class || '';
    $('#edit-target').value = item.target || '';
    $('#edit-rel').value = item.rel || '';
    $('#edit-active').checked = item.active !== false;
    $('#edit-linked-id').value = item.linked_id || '';

    const excluded = new Set([item.id, ...collectDescendantIds(item)]);
    const parentSelect = $('#edit-parent');
    parentSelect.innerHTML = '<option value="">— Без родителя (корневой уровень) —</option>' +
        flattenTree(menuData)
            .filter(row => !excluded.has(row.item.id))
            .map(row => `<option value="${row.item.id}">${'— '.repeat(row.depth)}${escapeHtml(row.item.title)}</option>`)
            .join('');
    parentSelect.value = item.parent_id || '';

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
    select.innerHTML = '<option>Загрузка…</option>';
    const url = type === 'page' ? @json(route('admin.ajax.pages')) : @json(route('admin.ajax.categories'));
    fetch(url).then(r=>r.json()).then(list=>{
        select.innerHTML = '<option value="">— Выберите —</option>' + 
            list.map(i=>`<option value="${i.id}" ${i.id == selectedId ? 'selected' : ''}>${i.title}</option>`).join('');
    }).catch(()=> select.innerHTML = '<option>Ошибка загрузки</option>');
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
            toast('Пункт меню обновлён');
            setTimeout(() => location.reload(), 500);
        } else {
            const text = await response.text();
            toast('Ошибка обновления: ' + text.substring(0, 100), true);
        }
    } catch (err) {
        toast('Ошибка обновления', true);
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
        $('#selected-count').textContent = `${count} выбрано`;
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
        toast('Выберите хотя бы один пункт', true);
        return;
    }

    const itemIds = Array.from(selectedItems);
    const actionText = action === 'activate' ? 'активировать' : action === 'deactivate' ? 'деактивировать' : 'удалить';

    if (!confirm(`Вы уверены, что хотите ${actionText} ${itemIds.length} пункт(ов)?`)) {
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
            toast(`Готово: ${result.count ?? itemIds.length} пункт(ов)`);
            setTimeout(() => location.reload(), 600);
        } else {
            toast(result.message || 'Ошибка при выполнении операции', true);
        }
    } catch (err) {
        toast('Ошибка при выполнении операции', true);
    }
}

$('#bulk-activate').addEventListener('click', () => bulkAction('activate'));
$('#bulk-deactivate').addEventListener('click', () => bulkAction('deactivate'));
$('#bulk-delete').addEventListener('click', () => bulkAction('delete'));
</script>
@endpush

@push('styles')
<style>
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
