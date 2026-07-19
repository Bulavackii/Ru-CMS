@extends('layouts.admin')

@section('title', 'Модули')

@section('content')
    {{-- 🔰 Заголовок и форма установки нового модуля --}}
    <div class="mb-6 flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            🧩 Управление модулями
        </h1>

        {{-- 📥 Установка нового модуля через ZIP --}}
        <form action="{{ route('admin.modules.install') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="module" required class="border border-gray-300 rounded px-2 py-1 text-sm">
            <input type="text" name="signature" placeholder="Подпись (опционально)" class="border border-gray-300 rounded px-2 py-1 text-sm" style="width: 200px;">
            <button type="submit"
                    class="bg-black text-white px-4 py-2 rounded text-sm hover:bg-gray-800 transition">
                ⬆️ Установить
            </button>
        </form>
    </div>

    {{-- 📋 Таблица модулей --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-md overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4">📦 Название</th>
                    <th class="py-3 px-4">🧾 Версия</th>
                    <th class="py-3 px-4 text-center">⚙️ Статус</th>
                    <th class="py-3 px-4 text-center">🔐 Подпись</th>
                    <th class="py-3 px-4 text-center">📥 Установлен</th>
                    <th class="py-3 px-4 text-center">🔢 Приоритет</th>
                    <th class="py-3 px-4 text-center">⚙️ Действия</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($modules as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{-- 📦 Название --}}
                        <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                            {{ $module->name }}
                            @if ($module->is_protected)
                                <span class="ml-2 text-xs text-yellow-600 dark:text-yellow-400" title="Системный модуль">
                                    🛡️
                                </span>
                            @endif
                        </td>

                        {{-- 🧾 Версия --}}
                        <td class="py-3 px-4 text-gray-800 dark:text-gray-200">
                            {{ $module->version ?? '—' }}
                        </td>

                        {{-- ⚙️ Статус --}}
                        <td class="py-3 px-4 text-center">
                            @if ($module->active)
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200 rounded-full">
                                    <i class="fas fa-check-circle"></i> Активен
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200 rounded-full">
                                    <i class="fas fa-times-circle"></i> Неактивен
                                </span>
                            @endif
                        </td>

                        {{-- 🔐 Подпись --}}
                        <td class="py-3 px-4 text-center">
                            @if ($module->is_signed)
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200 rounded-full">
                                    <i class="fas fa-shield-alt"></i> Подписан
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-full">
                                    <i class="fas fa-exclamation-triangle"></i> Без подписи
                                </span>
                            @endif
                        </td>

                        {{-- 📥 Установлен --}}
                        <td class="py-3 px-4 text-center">
                            {!! $module->is_installed ? '✅' : '❌' !!}
                        </td>

                        {{-- 🔢 Приоритет --}}
                        <td class="py-3 px-4 text-center">
                            {{ $module->priority ?? 0 }}
                        </td>

                        {{-- ⚙️ Действия --}}
                        <td class="py-3 px-4 text-center space-x-1 space-y-1">
                            <div class="flex flex-wrap justify-center gap-1">
                                {{-- 🔄 Переключение активности --}}
                                @if ($module->is_protected)
                                    <span class="text-xs px-3 py-1 rounded bg-gray-400 text-white cursor-not-allowed" title="Системный модуль - нельзя отключить">
                                        🔒 Защищен
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button class="text-xs px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white">
                                            {{ $module->active ? 'Отключить' : 'Включить' }}
                                        </button>
                                    </form>
                                @endif

                                {{-- 🔑 Генерация ключей --}}
                                @if ($module->is_installed && !$module->is_signed)
                                    <form method="POST" action="{{ route('admin.modules.generateKeys', $module->id) }}" class="inline">
                                        @csrf
                                        <button class="text-xs px-3 py-1 rounded bg-purple-600 hover:bg-purple-700 text-white" title="Сгенерировать ключи и подпись">
                                            🔑 Ключи
                                        </button>
                                    </form>
                                @endif

                                {{-- 🛡️ Проверка безопасности --}}
                                @if ($module->is_installed)
                                    <a href="{{ route('admin.modules.securityCheck', $module->id) }}" class="text-xs px-3 py-1 rounded bg-yellow-600 hover:bg-yellow-700 text-white inline-block">
                                        🛡️ Проверить
                                    </a>
                                @endif

                                {{-- 📥 Архивирование --}}
                                <form method="POST" action="{{ route('admin.modules.archive', $module->id) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="text-xs px-3 py-1 rounded bg-yellow-600 hover:bg-yellow-700 text-white">
                                        Архивировать
                                    </button>
                                </form>

                                {{-- ❌ Удаление --}}
                                @if ($module->is_protected)
                                    <span class="text-xs px-3 py-1 rounded bg-gray-400 text-white cursor-not-allowed" title="Системный модуль - нельзя удалить">
                                        🛡️ Защищен
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('admin.modules.destroy', $module->id) }}" class="inline" onsubmit="return confirm('Удалить модуль {{ $module->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs px-3 py-1 rounded bg-red-600 hover:bg-red-700 text-white">
                                            Удалить
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">
                            📭 Модули не найдены
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 📊 Отчеты безопасности --}}
    @if (session('security_report'))
        <div class="mt-6 p-4 border rounded-lg {{ session('security_report.safe') ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <h3 class="font-bold mb-2">🛡️ Отчет безопасности: {{ session('security_report.module') }}</h3>
            <p class="mb-2">
                Статус подписи:
                <strong class="{{ session('security_report.signed') ? 'text-green-600' : 'text-red-600' }}">
                    {{ session('security_report.signed') ? '✅ Валидна' : '❌ Нет валидной подписи' }}
                </strong>
            </p>
            @if (!empty(session('security_report.warnings')))
                <p class="font-bold text-red-600 mb-1">⚠️ Обнаружены подозрительные операции:</p>
                <ul class="list-disc list-inside text-sm">
                    @foreach (session('security_report.warnings') as $warning)
                        <li>
                            <code>{{ $warning['file'] }}</code>
                            @if ($warning['line'])
                                (строка {{ $warning['line'] }})
                            @endif
                            - <span class="font-mono text-xs bg-gray-200 px-1 rounded">{{ $warning['pattern'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-green-600">✅ Вредоносный код не обнаружен</p>
            @endif
        </div>
    @endif

    {{-- 📝 Инструкция --}}
    <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <h3 class="font-bold text-blue-800 dark:text-blue-300 mb-2">ℹ️ Инструкция по безопасности</h3>
        <ul class="text-sm space-y-1 text-blue-700 dark:text-blue-200">
            <li>• Для защиты модуля сгенерируйте ключи (кнопка 🔑 Ключи)</li>
            <li>• Подписанные модули нельзя активировать без валидной подписи</li>
            <li>• При установке можно передать подпись для автоматической проверки</li>
            <li>• Проверка безопасности сканирует код на опасные операции</li>
            <li>• Архивация сохраняет подпись вместе с файлами</li>
        </ul>
    </div>
@endsection
