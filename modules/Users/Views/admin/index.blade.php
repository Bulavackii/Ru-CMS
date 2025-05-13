@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">👥 Список пользователей</h1>
        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-plus"></i> Добавить
        </a>
    </div>

    {{-- 🔍 Поиск --}}
    <div class="flex flex-wrap items-center gap-2 mb-6 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center w-full md:w-1/3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Поиск по имени или email"
                   class="p-3 border rounded-md shadow-sm w-full md:w-3/4 mr-4 focus:ring-2 focus:ring-blue-500 transition-all duration-300">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm text-sm font-semibold transition-all duration-300">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    {{-- 🧭 Фильтр --}}
    <div class="flex flex-wrap items-center gap-2 mb-6 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Роли:</span>

        <a href="{{ route('admin.users.index') }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm {{ !$currentRole ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            Все
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'admin']) }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm {{ $currentRole === 'admin' ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            Админы
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'client']) }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm {{ $currentRole === 'client' ? 'bg-black text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            Клиенты
        </a>
    </div>

    {{-- 📊 Таблица --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md mb-10">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                <tr>
                    <th class="px-4 py-3"></th>

                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th class="text-center w-32">Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-300">
                        <td class="px-4 py-3 text-center">
                            {{-- <td class="px-4 py-3 text-center"></td> --}}
                        </td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->is_admin)
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-blue-600 text-white rounded-full">Админ</span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">Клиент</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-col items-center justify-center gap-2">
                                {{-- 🔁 Переключение роли --}}
                                <form action="{{ route('admin.users.toggleRole', $user->id) }}" method="POST" class="w-full flex justify-center">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-9 h-9 flex items-center justify-center bg-yellow-600 hover:bg-yellow-700 text-white rounded-md shadow-md transition-all duration-300 hover:scale-105"
                                            title="Переключить роль">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>

                                {{-- 🔑 Смена пароля --}}
                                @if (!$user->is_admin || auth()->id() === $user->id)
                                    <a href="{{ route('admin.users.password.edit', $user->id) }}"
                                       class="w-9 h-9 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-md shadow-md transition-all duration-300 hover:scale-105"
                                       title="Изменить пароль">
                                        <i class="fas fa-key"></i>
                                    </a>
                                @endif

                                {{-- 🗑️ Удаление --}}
                                @if (auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                          onsubmit="return confirm('Удалить пользователя?');" class="w-full flex justify-center">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-9 h-9 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white rounded-md shadow-md transition-all duration-300 hover:scale-105"
                                                title="Удалить">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-sm" title="Нельзя удалить себя">
                                        <i class="fas fa-user-circle text-xl"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 📄 Пагинация --}}
    <div class="mt-6">
        {{ $users->links('vendor.pagination.tailwind') }}
    </div>
@endsection

<script>
    document.getElementById('check-all')?.addEventListener('change', e => {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
    });
</script>
