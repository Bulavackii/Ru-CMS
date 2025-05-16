@extends('layouts.admin')

@section('title', 'Редактировать меню')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🧩 Меню: {{ $menu->title }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Позиция: {{ $menu->position }}</p>
    </div>

    <div id="menu-editor" class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg p-6">
        <ul id="menu-list" class="space-y-2">
            {{-- Пункты меню загружаются через JS с backend --}}
        </ul>
    </div>

    <div class="mt-6">
        <button id="save-menu-order"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
            💾 Сохранить порядок
        </button>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // Пример структуры JSON можно получить с backend и отрисовать вручную
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

        if (parent) {
            ul.dataset.parent = parent;
        }

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
</script>
@endpush
