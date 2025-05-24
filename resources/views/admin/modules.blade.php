@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🧩 Управление модулями
        </h1>

        <form action="{{ route('admin.modules.install') }}" method="POST" enctype="multipart/form-data"
              class="flex items-center gap-2">
            @csrf
            <input type="file" name="module" required class="border border-gray-300 rounded px-2 py-1 text-sm">
            <button type="submit" class="bg-black text-white px-4 py-2 rounded text-sm hover:bg-gray-800 transition">
                ⬆️ Установить
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table
            class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-md overflow-hidden">
            <thead
                class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wider">
            <tr>
                <th class="py-3 px-4 text-center">📦 Название</th>
                <th class="py-3 px-4 text-center">🧾 Версия</th>
                <th class="py-3 px-4 text-center">⚙️ Статус</th>
                <th class="py-3 px-4 text-center">📥 Установлен</th>
                <th class="py-3 px-4 text-center">🔢 Приоритет</th>
                <th class="py-3 px-4 text-center">⚙️</th>
            </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($modules as $module)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    {{-- 📦 Название --}}
                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white text-center">
                        {{ $module->title ?? $module->name }}
                    </td>

                    {{-- 🧾 Версия --}}
                    <td class="py-3 px-4 text-center text-gray-800 dark:text-gray-200">
                        {{ $module->version ?? '—' }}
                    </td>

                    {{-- ⚙️ Статус --}}
                    <td class="py-3 px-4 text-center">
                        @if ($module->active)
                            <i class="fas fa-check-circle text-green-500 text-lg" title="Активен"></i>
                        @else
                            <i class="fas fa-times-circle text-red-500 text-lg" title="Неактивен"></i>
                        @endif
                    </td>

                    {{-- 📥 Установлен --}}
                    <td class="py-3 px-4 text-center">
                        @if ($module->is_installed)
                            <i class="fas fa-check-circle text-green-500 text-lg" title="Установлен"></i>
                        @else
                            <i class="fas fa-times-circle text-red-500 text-lg" title="Не установлен"></i>
                        @endif
                    </td>

                    {{-- 🔢 Приоритет --}}
                    <td class="py-3 px-4 text-center cursor-move handle" data-id="{{ $module->id }}">
                        {{ $module->priority ?? 0 }}
                    </td>

                    {{-- ⚙️ Действия --}}
                    <td class="py-3 px-4 text-center space-x-1">
                        <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}" class="inline">
                            @csrf @method('PATCH')
                            <button title="Переключить" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-power-off"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.modules.archive', $module->id) }}" class="inline">
                            @csrf @method('PATCH')
                            <button title="Архивировать" class="text-yellow-600 hover:text-yellow-800">
                                <i class="fas fa-box-archive"></i>
                            </button>
                        </form>

                        @php
                            $archivePath = base_path("modules/archives/{$module->name}.zip");
                        @endphp
                        @if (file_exists($archivePath))
                            <a href="{{ route('admin.modules.downloadArchive', ['name' => $module->name]) }}"
                               class="text-indigo-600 hover:text-indigo-800" title="Скачать архив">
                                <i class="fas fa-download"></i>
                            </a>
                        @endif

                        <form method="POST" action="{{ route('admin.modules.destroy', $module->id) }}"
                              class="inline" onsubmit="return confirm('Удалить модуль {{ $module->name }}?')">
                            @csrf @method('DELETE')
                            <button title="Удалить" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
                        📭 Модули не найдены
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.querySelector('tbody');
            if (tbody) {
                new Sortable(tbody, {
                    handle: '.handle',
                    animation: 150,
                    onEnd: function () {
                        let ids = [];
                        document.querySelectorAll('.handle').forEach((el, index) => {
                            ids.push({
                                id: el.dataset.id,
                                priority: index + 1
                            });
                        });

                        fetch('{{ route('admin.modules.reorder') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({order: ids})
                        });
                    }
                });
            }
        });
    </script>
@endpush
