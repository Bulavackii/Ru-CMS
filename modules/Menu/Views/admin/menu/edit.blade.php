@extends('layouts.admin')

@section('title', 'Редактировать меню')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🧩 Меню: {{ $menu->title }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Позиция: {{ $menu->position }}</p>
    </div>

    {{-- 🔘 Добавить пункт меню --}}
    <form action="{{ route('admin.menu_items.store', $menu) }}" method="POST" class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg p-6 mb-8">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold mb-1">Название</label>
                <input type="text" name="title" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Тип</label>
                <select name="type" id="menu-type" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
                    <option value="url">Внешняя ссылка</option>
                    <option value="page">Страница</option>
                    <option value="category">Категория</option>
                </select>
            </div>

            <div id="url-field">
                <label class="block text-sm font-semibold mb-1">URL (если внешняя ссылка)</label>
                <input type="text" name="url" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>

            <div id="linked-id-field" style="display:none">
                <label class="block text-sm font-semibold mb-1">Выберите объект</label>
                <select name="linked_id" id="linked-id" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white"></select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">SEO: Meta Title</label>
                <input type="text" name="meta_title" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">SEO: Meta Description</label>
                <input type="text" name="meta_description" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">SEO: Meta Keywords</label>
                <input type="text" name="meta_keywords" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white">
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow">
                ➕ Добавить пункт
            </button>
        </div>
    </form>

    {{-- 🔁 Список с drag-and-drop --}}
    <div id="menu-editor" class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg p-6">
        <ul id="menu-list" class="space-y-2">
            {{-- Пункты меню загружаются через JS --}}
        </ul>
    </div>

    <div class="mt-6">
        <button id="save-menu-order"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
            💾 Сохранить порядок
        </button>
    </div>

    @if ($items->isNotEmpty())
    <div class="mt-8">
        <h2 class="text-lg font-bold text-gray-700 dark:text-white mb-4">📌 Список пунктов</h2>

        <ul class="space-y-3">
            @foreach ($items as $item)
                <li class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $item->title }}</div>
                            <div class="text-xs text-gray-500 mt-1">Тип: {{ $item->type }} | ID: {{ $item->linked_id }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.menu_items.destroy', [$menu, $item]) }}" onsubmit="return confirm('Удалить пункт?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Удалить</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const menuData = @json($items);

    function renderList(items, parent = null) {
        const ul = document.createElement('ul');
        ul.classList.add('space-y-2', 'pl-4');

        items.forEach(item => {
            const li = document.createElement('li');
            li.classList.add('p-2', 'border', 'rounded', 'bg-gray-50', 'dark:bg-gray-800');
            li.dataset.id = item.id;
            li.innerHTML = `
                <div class="flex justify-between items-center">
                    <span class="font-medium">${item.title}</span>
                    <span class="text-xs text-gray-500">${item.type}</span>
                </div>
            `;

            if (item.children && item.children.length) {
                li.appendChild(renderList(item.children, item.id));
            }

            ul.appendChild(li);
        });

        if (parent) ul.dataset.parent = parent;
        return ul;
    }

    const menuList = document.getElementById('menu-list');
    menuList.replaceWith(renderList(menuData));

    new Sortable(menuList, {
        group: 'menu',
        animation: 150,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        handle: '.handle'
    });

    document.getElementById('save-menu-order').addEventListener('click', () => {
        const result = buildOrder(menuList);
        fetch("{{ route('admin.menus.updateOrder', $menu) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ items: result })
        }).then(r => location.reload());
    });

    function buildOrder(ul) {
        const children = Array.from(ul.children);
        return children.map((li, index) => {
            const item = {
                id: li.dataset.id,
                order: index,
            };
            const sub = li.querySelector('ul');
            if (sub) item.children = buildOrder(sub);
            return item;
        });
    }

    // 🧠 Динамическая подгрузка элементов
    document.getElementById('menu-type').addEventListener('change', function () {
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

        const url = selectedType === 'page'
            ? '{{ route('admin.ajax.pages') }}'
            : '{{ route('admin.ajax.categories') }}';

        fetch(url)
            .then(res => res.json())
            .then(data => {
                linkedIdSelect.innerHTML = data.map(item => `<option value="${item.id}">${item.title}</option>`).join('');
            });
    });
</script>
@endpush
