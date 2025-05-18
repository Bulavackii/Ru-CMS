@extends('layouts.admin')

@section('title', 'Пользователи')

@push('scripts')
    {{-- Alpine.js для интерактивности --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- 🔍 AJAX-поиск по имени и email --}}
    <script>
        function filterUsers() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr.user-row');

            rows.forEach(row => {
                const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
                const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
                const match = name.includes(search) || email.includes(search);
                row.style.display = match ? '' : 'none';
            });
        }
    </script>
@endpush

@section('content')
    {{-- 🧩 Заголовок и кнопка --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">👥 Список пользователей</h1>
        <a href="{{ route('admin.users.create') }}"
            class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-user-plus"></i> Добавить
        </a>
    </div>

    {{-- 🔍 Компактный поиск по имени и email --}}
    <div class="mb-6">
        <input type="text" id="searchInput" oninput="filterUsers()" placeholder="🔍 Поиск по имени или email..."
               class="w-full md:w-1/3 px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm transition" />
    </div>

    {{-- 📋 Таблица пользователей --}}
    <div class="overflow-x-auto rounded-xl shadow-md border border-gray-200 dark:border-gray-800">
        <table id="usersTable" class="min-w-full bg-white dark:bg-gray-900 text-sm text-left">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3">🆔</th>
                    <th class="px-4 py-3">👤 Имя</th>
                    <th class="px-4 py-3">📧 Email</th>
                    <th class="px-4 py-3">🔐 Роль</th>
                    <th class="px-4 py-3 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($users as $user)
                    <tr class="user-row hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400">{{ $user->id }}</td>
                        <td class="px-4 py-3 user-name text-gray-900 dark:text-white font-semibold">{{ $user->name }}</td>
                        <td class="px-4 py-3 user-email text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->is_admin)
                                <span class="inline-flex items-center gap-1 bg-blue-600 text-white text-xs px-3 py-1 rounded-full">
                                    <i class="fas fa-shield-alt"></i> Админ
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs px-3 py-1 rounded-full">
                                    <i class="fas fa-user"></i> Клиент
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2 flex-wrap">
                                <form action="{{ route('admin.users.toggleRole', $user->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="w-8 h-8 flex items-center justify-center bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow transition"
                                            title="Переключить роль">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                </form>

                                @if (!$user->is_admin || auth()->id() === $user->id)
                                    <a href="{{ route('admin.users.password.edit', $user->id) }}"
                                       class="w-8 h-8 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-md shadow transition"
                                       title="Сменить пароль">
                                        <i class="fas fa-key"></i>
                                    </a>
                                @endif

                                @if (auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                          onsubmit="return confirm('Удалить пользователя?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="w-8 h-8 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white rounded-md shadow transition"
                                                title="Удалить">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center px-4 py-6 text-gray-500 dark:text-gray-400">
                            📭 Пользователи не найдены.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $users->withQueryString()->links('vendor.pagination.tailwind') }}
    </div>
@endsection
