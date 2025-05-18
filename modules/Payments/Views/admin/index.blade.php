@extends('layouts.admin')

@section('title', 'Способы оплаты')

@section('content')
    {{-- 🔝 Заголовок и кнопка добавления --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">💳 Способы оплаты</h1>
        <a href="{{ route('admin.payments.create') }}"
           class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-5 py-2.5 rounded-md shadow-sm text-sm font-semibold transition">
            <i class="fas fa-plus text-xs"></i> Добавить
        </a>
    </div>

    {{-- 📋 Таблица способов оплаты --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">🏷️ Название</th>
                    <th class="px-6 py-3 font-semibold">⚙️ Тип</th>
                    <th class="px-6 py-3 font-semibold text-center">✅ Статус</th>
                    <th class="px-6 py-3 font-semibold text-center">🛠️ Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($methods as $method)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{-- 🏷️ Название метода --}}
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $method->title }}
                        </td>

                        {{-- ⚙️ Тип метода --}}
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300 capitalize">
                            {{ $method->type === 'online' ? '💻 Онлайн' : '🏦 Офлайн' }}
                        </td>

                        {{-- ✅ Статус активности --}}
                        <td class="px-6 py-4 text-center">
                            @if ($method->active)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-white text-xs font-semibold">
                                    <i class="fas fa-check-circle"></i> Включен
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-white text-xs font-semibold">
                                    <i class="fas fa-times-circle"></i> Выключен
                                </span>
                            @endif
                        </td>

                        {{-- ✏️ Действия (редактировать / удалить) --}}
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center justify-center gap-2">
                                {{-- ✏️ Редактировать --}}
                                <a href="{{ route('admin.payments.edit', $method->id) }}"
                                   class="inline-flex items-center justify-center text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium"
                                   title="Редактировать">
                                    <i class="fas fa-pen"></i>
                                </a>

                                {{-- 🗑️ Удалить --}}
                                <form action="{{ route('admin.payments.destroy', $method->id) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Удалить этот способ оплаты?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center text-red-600 dark:text-red-400 hover:underline text-sm font-medium"
                                            title="Удалить">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    {{-- ❗ Нет способов оплаты --}}
                    <tr>
                        <td colspan="4" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                            🤷 Нет способов оплаты.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
