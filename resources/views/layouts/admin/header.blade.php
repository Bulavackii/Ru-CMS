<header class="bg-white p-4 shadow text-xl font-semibold flex justify-between items-center">
    <div>
        @yield('header', 'Панель администратора')
    </div>

    <form method="GET" action="{{ route('admin.search.index') }}" class="flex space-x-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск..."
               class="border rounded px-3 py-1 text-sm w-64">
        <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">🔍</button>
    </form>
</header>
