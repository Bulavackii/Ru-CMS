@extends('layouts.admin')

@section('title', 'Пользователи')

@push('scripts')
    {{-- Alpine.js для интерактивности --}}
    <script src="{{ local_js('alpine.min.js') }}" defer></script>

    {{-- 🔍 Скрипты для массовых операций --}}
    <script>
        // Выбрать все
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkSubmit();
        });

        // Обновление кнопки при изменении чекбоксов
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkSubmit);
        });

        function updateBulkSubmit() {
            const checked = document.querySelectorAll('.user-checkbox:checked').length;
            const action = document.getElementById('bulkAction').value;
            document.getElementById('bulkSubmit').disabled = !(checked > 0 && action);
        }

        // Показать/скрыть выбор роли
        document.getElementById('bulkAction')?.addEventListener('change', function() {
            const roleSelect = document.getElementById('bulkRole');
            if (this.value === 'assign_role') {
                roleSelect.style.display = 'block';
                roleSelect.required = true;
            } else {
                roleSelect.style.display = 'none';
                roleSelect.required = false;
            }
            updateBulkSubmit();
        });

        // Подтверждение массового удаления
        document.getElementById('bulkForm')?.addEventListener('submit', function(e) {
            const action = document.getElementById('bulkAction').value;
            const checked = document.querySelectorAll('.user-checkbox:checked').length;
            
            if (action === 'delete' && !confirm(`Удалить ${checked} пользователей?`)) {
                e.preventDefault();
            }
        });
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

    {{-- 🔍 Фильтры и поиск --}}
    <div class="mb-6 space-y-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $search }}" 
                       placeholder="🔍 Поиск по имени, email, телефону..."
                       class="w-full px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm transition" />
            </div>
            <div>
                <select name="role" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Все роли</option>
                    <option value="admin" {{ $currentRole === 'admin' ? 'selected' : '' }}>Администраторы</option>
                    <option value="user" {{ $currentRole === 'user' ? 'selected' : '' }}>Пользователи</option>
                </select>
            </div>
            @if($roles->count() > 0)
            <div>
                <select name="role_filter" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Все роли (детально)</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ $roleFilter == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-semibold transition">
                Применить
            </button>
            @if($search || $currentRole || $roleFilter)
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-md text-sm font-semibold transition">
                    Сбросить
                </a>
            @endif
        </form>
    </div>

    {{-- 🔄 Массовые операции --}}
    <form method="POST" action="{{ route('admin.users.bulkAction') }}" id="bulkForm" class="mb-4">
        @csrf
        <div class="flex items-center gap-3 flex-wrap">
            <select name="action" id="bulkAction" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm">
                <option value="">Выберите действие...</option>
                <option value="delete">Удалить выбранных</option>
                <option value="assign_role">Назначить роль</option>
            </select>
            <select name="role_id" id="bulkRole" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" style="display:none;">
                <option value="">Выберите роль...</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-semibold transition" disabled id="bulkSubmit">
                Применить
            </button>
        </div>
    </form>

    {{-- 📋 Таблица пользователей --}}
    <div class="overflow-x-auto rounded-xl shadow-md border border-gray-200 dark:border-gray-800">
        <table id="usersTable" class="min-w-full bg-white dark:bg-gray-900 text-sm text-left">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-400">
                    </th>
                    <th class="px-4 py-3">🆔</th>
                    <th class="px-4 py-3">👤 Имя</th>
                    <th class="px-4 py-3">📧 Email</th>
                    <th class="px-4 py-3">📞 Телефон</th>
                    <th class="px-4 py-3">🔐 Роль</th>
                    <th class="px-4 py-3">📅 Регистрация</th>
                    <th class="px-4 py-3">🕐 Последний вход</th>
                    <th class="px-4 py-3 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($users as $user)
                    <tr class="user-row hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3">
                            @if(auth()->id() !== $user->id)
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox rounded border-gray-400">
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400">{{ $user->id }}</td>
                        <td class="px-4 py-3 user-name text-gray-900 dark:text-white font-semibold">{{ $user->name }}</td>
                        <td class="px-4 py-3 user-email text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $user->formatted_phone ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_admin)
                                <span class="inline-flex items-center gap-1 bg-blue-600 text-white text-xs px-3 py-1 rounded-full">
                                    <i class="fas fa-shield-alt"></i> Админ
                                </span>
                            @else
                                @if($user->roles->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->roles as $role)
                                            <span class="inline-flex items-center gap-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 text-xs px-2 py-1 rounded-full">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs px-3 py-1 rounded-full">
                                        <i class="fas fa-user"></i> Без роли
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                            {{ $user->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d.m.Y H:i') }}
                                @if($user->last_login_ip)
                                    <br><span class="text-gray-500">IP: {{ $user->last_login_ip }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">Никогда</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2 flex-wrap">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="w-8 h-8 flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow transition"
                                   title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="{{ route('admin.users.loginHistory', $user) }}"
                                   class="w-8 h-8 flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white rounded-md shadow transition"
                                   title="История входов">
                                    <i class="fas fa-history"></i>
                                </a>

                                @if (!$user->is_admin || auth()->id() === $user->id)
                                    <a href="{{ route('admin.users.password.edit', $user) }}"
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
                        <td colspan="9" class="text-center px-4 py-6 text-gray-500 dark:text-gray-400">
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
