@extends('layouts.admin')

@section('title', 'Пользователи')
@section('header', 'Управление пользователями')

@section('content')

    {{-- ✅ Уведомление --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded shadow">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- 👥 Таблица пользователей --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-md overflow-hidden mb-10">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">🙍 Имя</th>
                    <th class="px-4 py-3 text-left">📧 Email</th>
                    <th class="px-4 py-3 text-left">🔐 Роль</th>
                    <th class="px-4 py-3 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            @if ($user->is_admin)
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-black text-white rounded-full">Админ</span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">Клиент</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if (auth()->id() !== $user->id)
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                      onsubmit="return confirm('Удалить пользователя?');"
                                      class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md text-sm shadow transition">
                                        <i class="fas fa-trash-alt"></i> Удалить
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400 italic text-xs">Вы</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
