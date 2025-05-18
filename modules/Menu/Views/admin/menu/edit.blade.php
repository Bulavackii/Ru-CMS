@extends('layouts.admin')

@section('title', 'Редактировать меню')

@section('content')
    {{-- 🧩 Заголовок страницы --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        {{-- Название меню --}}
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🧩 Меню: {{ $menu->title }}
        </h1>

        {{-- Позиция меню --}}
        <div class="text-sm text-gray-500 dark:text-gray-400">
            📍 Позиция: <strong>{{ $menu->position }}</strong>
        </div>
    </div>

    {{-- ➕ Добавление нового пункта меню --}}
    <form action="{{ route('admin.menu_items.store', $menu) }}" method="POST"
        class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl p-6 mb-10 space-y-6">
        @csrf

        {{-- 🔣 Поля формы --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🏷️ Название</label>
                <input type="text" name="title" required
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🔗 Тип</label>
                <select name="type" id="menu-type"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
                    <option value="url">🌍 Внешняя ссылка</option>
                    <option value="page">📄 Страница</option>
                    <option value="category">🏷️ Категория</option>
                </select>
            </div>

            <div id="url-field">
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🌐 URL (если
                    внешняя)</label>
                <input type="text" name="url"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>

            <div id="linked-id-field" style="display:none">
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🔍 Связанный объект</label>
                <select name="linked_id" id="linked-id"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white"></select>
            </div>
        </div>

        {{-- 🧠 SEO-поля --}}
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🧠 Meta Title</label>
                <input type="text" name="meta_title"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">📝 Meta Description</label>
                <input type="text" name="meta_description"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1 text-gray-700 dark:text-gray-300">🏷️ Meta Keywords</label>
                <input type="text" name="meta_keywords"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>
        </div>

        {{-- ✅ Кнопка отправки --}}
        <div class="pt-4">
            <button type="submit"
                class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow text-sm inline-flex items-center gap-2">
                ➕ Добавить пункт
            </button>
        </div>
    </form>

    {{-- 🔽 Разделитель --}}
    <hr class="my-8 border-t border-dashed border-gray-300 dark:border-gray-700">

    {{-- 🧩 Drag-and-drop редактор --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl p-6 relative">
        <ul id="menu-list" class="space-y-2">
            {{-- 🧩 Пункты меню загружаются через JS --}}
        </ul>

        {{-- 💾 Кнопка сохранения (sticky) --}}
        <div class="mt-6 sm:mt-8 text-right">
            <button id="save-menu-order"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow text-sm inline-flex items-center gap-2">
                <i class="fas fa-save"></i> Сохранить порядок
            </button>
        </div>
    </div>

    {{-- 📋 Список пунктов меню (резервный вывод) --}}
    @if ($items->isNotEmpty())
        <div class="mt-10 space-y-3">
            <h2 class="text-lg font-bold text-gray-700 dark:text-white mb-2">📌 Список пунктов</h2>

            @foreach ($items as $item)
                <div
                    class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $item->title }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            🔗 Тип: <span title="Тип привязки">{{ $item->type }}</span>
                            | 🆔 ID: <span title="ID объекта">{{ $item->linked_id }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.menu_items.destroy', [$menu, $item]) }}"
                        onsubmit="return confirm('Удалить пункт?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="text-red-600 hover:text-red-800 text-sm inline-flex items-center gap-1">
                            🗑️ Удалить
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const menuData = @json($items);

        function renderList(items, parent = null, depth = 0) {
            const ul = document.createElement('ul');
            ul.classList.add('space-y-2', 'pl-' + Math.min(depth * 4, 12));

            items.forEach(item => {
                const li = document.createElement('li');
                li.classList.add(
                    'p-2', 'border', 'rounded', 'bg-gray-50', 'dark:bg-gray-800', 'relative'
                );
                li.dataset.id = item.id;

                const hasChildren = item.children && item.children.length > 0;

                li.innerHTML = `
            <div class="flex justify-between items-center handle cursor-move px-2 py-1 rounded ${hasChildren ? 'bg-blue-50 dark:bg-blue-900' : ''}">
                <span class="font-medium flex items-center gap-1">
                    <i class="fas fa-ellipsis-v text-gray-400"></i>
                    ${item.title}
                </span>
                <span class="text-xs text-gray-500">${item.type}</span>
            </div>
        `;

                // даже если нет детей, вставляем пустой ul для потенциальной вложенности
                const childUl = document.createElement('ul');
                childUl.classList.add('pl-4', 'space-y-2');
                li.appendChild(childUl);

                if (hasChildren) {
                    const nested = renderList(item.children, item.id, depth + 1);
                    childUl.replaceWith(nested); // заменим пустой ul на реальный
                }

                ul.appendChild(li);
            });

            return ul;
        }

        const menuList = renderList(menuData);
        document.getElementById('menu-list').replaceWith(menuList);

        // Рекурсивно подключаем Sortable ко всем спискам
        function initSortable(ul) {
            new Sortable(ul, {
                group: 'nested',
                animation: 150,
                handle: '.handle',
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'bg-yellow-100',
            });

            ul.querySelectorAll(':scope > li > ul').forEach(nestedUl => {
                initSortable(nestedUl); // вложенные списки
            });
        }

        initSortable(menuList);

        // 🔁 Построение вложенности
        function buildOrder(ul) {
            return [...ul.children].map((li, index) => {
                const item = {
                    id: li.dataset.id,
                    order: index,
                };
                const nested = li.querySelector(':scope > ul');
                if (nested && nested.children.length > 0) {
                    item.children = buildOrder(nested);
                }
                return item;
            });
        }

        // 💾 Сохранение
        document.getElementById('save-menu-order').addEventListener('click', () => {
            const result = buildOrder(menuList);
            fetch("{{ route('admin.menus.updateOrder', $menu) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    items: result
                })
            }).then(() => location.reload());
        });

        // 🔄 AJAX-подгрузка сущностей
        document.getElementById('menu-type').addEventListener('change', function() {
            const selectedType = this.value;
            const linkedIdField = document.getElementById('linked-id-field');
            const linkedIdSelect = document.getElementById('linked-id');
            const urlField = document.getElementById('url-field');

            if (selectedType === 'url') {
                linkedIdField.style.display = 'none';
                urlField.style.display = 'block';
                return;
            }

            linkedIdField.style.display = 'block';
            urlField.style.display = 'none';
            linkedIdSelect.innerHTML = '<option value="">Загрузка...</option>';

            const url = selectedType === 'page' ?
                '{{ route('admin.ajax.pages') }}' :
                '{{ route('admin.ajax.categories') }}';

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    linkedIdSelect.innerHTML = data.map(item =>
                        `<option value="${item.id}">${item.title}</option>`).join('');
                });
        });
    </script>
@endpush
