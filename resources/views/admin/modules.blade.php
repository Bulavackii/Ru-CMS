@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    {{-- 🔹 Заголовок + действие --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🧩 Список модулей</h1>
        <label for="upload-module"
            class="inline-flex items-center gap-2 cursor-pointer bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md shadow-md text-sm font-semibold transition-all duration-200">
            <i class="fas fa-upload"></i> Установить модуль
        </label>
    </div>

    {{-- 🔽 Форма установки модуля --}}
    <form method="POST" action="{{ route('admin.modules.install') }}" enctype="multipart/form-data" class="hidden"
        id="upload-form">
        @csrf
        <input type="file" name="module" id="upload-module" accept=".zip" class="hidden"
            onchange="document.getElementById('upload-form').submit()">
    </form>

    {{-- 📊 Таблица модулей --}}
    <div class="overflow-x-auto mt-4">
        <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden shadow-md bg-white dark:bg-gray-900">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase">
                <tr>
                    <th class="px-4 py-3">📦 Название</th>
                    <th class="px-4 py-3">🧾 Версия</th>
                    <th class="px-4 py-3 text-center">📢 Статус</th>
                    <th class="px-4 py-3 text-center">📅 Дата установки</th>
                    <th class="px-4 py-3 text-center">📦 Приоритет</th>
                    <th class="px-4 py-3 text-center">⚙️ Действие</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($modules as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">
                            {{ $module->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $module->version }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($module->active)
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200">
                                    <i class="fas fa-check-circle"></i> Активен
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200">
                                    <i class="fas fa-power-off"></i> Неактивен
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            @if ($module->installed_at && $module->installed_at instanceof \Carbon\Carbon)
                                {{ $module->installed_at->format('d.m.Y H:i') }}
                            @else
                                Не установлен
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            {{ $module->priority }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.modules.toggle', ['id' => $module->id]) }}"
                                class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button
                                    class="inline-flex items-center gap-1 px-4 py-1 text-xs font-medium rounded text-white transition {{ $module->active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                                    <i class="fas {{ $module->active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                    {{ $module->active ? 'Отключить' : 'Включить' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.modules.destroy', ['id' => $module->id]) }}"
                                class="inline-block ml-2">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="inline-flex items-center gap-1 px-4 py-1 text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700">
                                    <i class="fas fa-trash-alt"></i> Удалить
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

    {{-- 📜 Сценарий автоотправки формы --}}
    <script>
        document.getElementById('upload-module')?.addEventListener('change', () => {
            document.getElementById('upload-form').submit();
        });
    </script>

    {{-- AJAX для переключения активности модуля --}}
    <script>
        document.querySelectorAll('form[action*="toggle"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const button = form.querySelector('button');
                const moduleId = form.querySelector('input[name="id"]').value;

                fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.innerHTML = data.status ? 'Отключить' : 'Включить';
                            button.classList.toggle('bg-red-600');
                            button.classList.toggle('bg-green-600');
                            button.classList.toggle('hover:bg-red-700');
                            button.classList.toggle('hover:bg-green-700');
                        }
                    });
            });
        });
    </script>
@endsection
