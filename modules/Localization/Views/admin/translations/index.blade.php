{{--
    Список языков с прогрессом перевода.

    Вёрстка — Tailwind, как во всей админке (layouts.admin подключает
    tailwind.min.css + Font Awesome). Bootstrap в проекте нет, поэтому
    диалоги сделаны на Alpine, а не на data-bs-* модалках.
--}}
@extends('layouts.admin')

@section('title', 'Переводы интерфейса')

@section('content')
<div x-data="{ addOpen: false, deleteCode: null, deleteName: '' }">

    {{-- 🔰 Заголовок --}}
    <div class="mb-6 flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📝 Переводы интерфейса</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Правка языковых файлов <code class="font-mono">resources/lang</code> прямо из админки.
                Эталон структуры ключей — <strong>{{ $reference }}</strong>.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" x-on:click="addOpen = true"
                    class="bg-black text-white px-4 py-2 rounded text-sm hover:bg-gray-800 transition inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Добавить язык
            </button>
            <a href="{{ route('admin.localization.index') }}"
               class="border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition inline-flex items-center gap-2">
                <i class="fas fa-globe"></i> Страны и форматы
            </a>
        </div>
    </div>

    {{-- ✅ Флеш-сообщения --}}
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ℹ️ Как считается прогресс --}}
    <div class="mb-5 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 px-4 py-3 text-xs text-gray-600 dark:text-gray-400 flex gap-2">
        <i class="fas fa-info-circle mt-0.5 text-gray-400"></i>
        <span>
            Прогресс считается по ключам эталонного языка. <strong>Не переведено</strong> — ключ
            отсутствует либо его значение дословно совпадает с эталоном (так бывает сразу после
            создания языка копированием). Короткие слова вроде «Email» законно совпадают,
            поэтому проценты — ориентир, а не точная метрика.
        </span>
    </div>

    {{-- 🌍 Карточки языков --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($locales as $locale)
            @php
                $s = $locale['stats'];
                $percent = $s['reference'] ? 100 : $s['percent'];
                $barColor = $s['reference']
                    ? 'bg-gray-900 dark:bg-gray-200'
                    : ($percent >= 90 ? 'bg-green-500' : ($percent >= 50 ? 'bg-amber-500' : 'bg-red-500'));
            @endphp
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4 flex flex-col">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $locale['name'] }}</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <code class="text-xs text-gray-500 font-mono">{{ $locale['code'] }}</code>
                            @if ($s['reference'])
                                <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-gray-900 text-white dark:bg-gray-200 dark:text-gray-900">эталон</span>
                            @elseif ($locale['protected'])
                                <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200">fallback</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-2xl font-bold {{ $percent >= 90 ? 'text-green-600' : 'text-gray-400' }}">{{ $percent }}%</div>
                </div>

                <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-2">
                    <div class="h-full {{ $barColor }} transition-all" style="width: {{ $percent }}%"></div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 flex-1">
                    @if ($s['reference'])
                        {{ $s['total'] }} ключей — это исходный язык проекта
                    @else
                        Переведено {{ $s['translated'] }} из {{ $s['total'] }}
                        @if ($s['missing'] > 0)
                            · <span class="text-red-600 dark:text-red-400">нет {{ $s['missing'] }}</span>
                        @endif
                        @if ($s['same'] > 0)
                            · <span class="text-amber-600 dark:text-amber-400">совпадает {{ $s['same'] }}</span>
                        @endif
                    @endif
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.localization.translations.edit', $locale['code']) }}"
                       class="flex-1 text-center bg-black text-white px-3 py-2 rounded text-sm hover:bg-gray-800 transition">
                        <i class="fas fa-pen mr-1"></i> Редактировать
                    </a>
                    @unless ($locale['protected'])
                        <button type="button"
                                x-on:click="deleteCode = @js($locale['code']); deleteName = @js($locale['name'])"
                                title="Удалить язык"
                                class="px-3 py-2 rounded text-sm border border-red-200 text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 text-xs text-gray-500 dark:text-gray-400">
        <i class="fas fa-folder-open mr-1"></i>
        Файлы переводов ({{ count($groups) }}):
        @foreach ($groups as $g)<code class="font-mono">{{ $g }}</code>@if (!$loop->last), @endif @endforeach
    </div>

    {{-- ➕ Диалог: новый язык --}}
    <div x-show="addOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-on:keydown.escape.window="addOpen = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md" x-on:click.outside="addOpen = false">
            <form method="POST" action="{{ route('admin.localization.translations.store') }}">
                @csrf
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="font-semibold text-gray-900 dark:text-white">➕ Новый язык</h2>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Код языка</label>
                        <input type="text" name="code" id="code" required
                               placeholder="например uk, pl, pt_BR"
                               pattern="[A-Za-z]{2,3}([_-][A-Za-z]{2,8})?"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded px-3 py-2 text-sm">
                        <p class="text-xs text-gray-500 mt-1">
                            ISO-код: 2–3 буквы, при необходимости с регионом через подчёркивание.
                            Каталог <code class="font-mono">resources/lang/&lt;код&gt;</code> создастся автоматически.
                        </p>
                    </div>
                    <div>
                        <label for="copy_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Скопировать строки из</label>
                        <select name="copy_from" id="copy_from"
                                class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded px-3 py-2 text-sm">
                            @foreach ($locales as $locale)
                                <option value="{{ $locale['code'] }}" @selected($locale['code'] === $reference)>
                                    {{ $locale['name'] }} ({{ $locale['code'] }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Значения копируются как есть — интерфейс сразу работает,
                            а в редакторе видно, что осталось перевести.
                        </p>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button type="button" x-on:click="addOpen = false"
                            class="px-4 py-2 rounded text-sm border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Отмена
                    </button>
                    <button type="submit" class="px-4 py-2 rounded text-sm bg-black text-white hover:bg-gray-800">
                        Создать и открыть редактор
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 🗑️ Диалог: удаление языка --}}
    <div x-show="deleteCode !== null" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-on:keydown.escape.window="deleteCode = null">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md" x-on:click.outside="deleteCode = null">
            <form method="POST" x-bind:action="@js(url('admin/localization/translations')) + '/' + deleteCode">
                @csrf
                @method('DELETE')
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="font-semibold text-red-600">🗑️ Удалить язык <span x-text="deleteName"></span></h2>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                    <p class="mb-1">
                        Каталог <code class="font-mono">resources/lang/<span x-text="deleteCode"></span></code>
                        будет удалён со всеми файлами переводов.
                    </p>
                    <p class="text-xs text-gray-500">Из админки это необратимо — восстановить можно только из git.</p>
                </div>
                <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button type="button" x-on:click="deleteCode = null"
                            class="px-4 py-2 rounded text-sm border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Отмена
                    </button>
                    <button type="submit" class="px-4 py-2 rounded text-sm bg-red-600 text-white hover:bg-red-700">
                        Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
