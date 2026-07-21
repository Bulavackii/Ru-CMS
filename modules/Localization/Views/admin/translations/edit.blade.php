{{--
    Редактор строк одной группы (файла) переводов.

    Слева — список файлов локали с прогрессом, справа — таблица
    «ключ / эталон / перевод». Фильтрация по подстроке и по незаконченным
    строкам сделана на клиенте: строк сотни, гонять их через сервер незачем.
--}}
@extends('layouts.admin')

@section('title', 'Переводы: ' . $localeName)

@section('content')
<div x-data="translationEditor()">

    {{-- 🔰 Заголовок --}}
    <div class="mb-5 flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2 flex-wrap">
                📝 {{ $localeName }}
                <code class="text-sm font-mono text-gray-500">{{ $locale }}</code>
                @if ($isReference)
                    <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-gray-900 text-white dark:bg-gray-200 dark:text-gray-900">эталон</span>
                @endif
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Файл <code class="font-mono">resources/lang/{{ $locale }}/{{ $group }}.php</code>
            </p>
        </div>
        <a href="{{ route('admin.localization.translations.index') }}"
           class="border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Ко всем языкам
        </a>
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

    @if ($isReference)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-xs dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-200 flex gap-2">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <span>
                Это <strong>эталонный</strong> язык: его набор ключей задаёт структуру для всех остальных.
                Править текст безопасно, но добавление и удаление ключей затронет все языки —
                такие изменения лучше делать в коде вместе со вьюхами.
            </span>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-4">

        {{-- 📂 Файлы переводов --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-200 dark:border-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Файлы переводов
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[70vh] overflow-y-auto">
                    @foreach ($groups as $g)
                        @php
                            $gs = $groupStats[$g];
                            $active = $g === $group;
                        @endphp
                        <a href="{{ route('admin.localization.translations.edit', [$locale, $g]) }}"
                           class="block px-4 py-2.5 transition {{ $active ? 'bg-gray-900 text-white dark:bg-gray-200 dark:text-gray-900' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                            <div class="flex items-center justify-between gap-2">
                                <code class="font-mono text-xs {{ $active ? '' : 'text-gray-700 dark:text-gray-300' }}">{{ $g }}</code>
                                <span class="text-[11px] {{ $active ? 'opacity-70' : 'text-gray-400' }}">
                                    {{ $isReference ? $gs['total'] : $gs['percent'] . '%' }}
                                </span>
                            </div>
                            @unless ($isReference)
                                <div class="h-0.5 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-1.5">
                                    <div class="h-full {{ $gs['percent'] >= 90 ? 'bg-green-500' : ($gs['percent'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                         style="width: {{ $gs['percent'] }}%"></div>
                                </div>
                            @endunless
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ✏️ Строки --}}
        <div class="lg:col-span-3">
            <form method="POST" action="{{ route('admin.localization.translations.update', [$locale, $group]) }}">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">

                    {{-- Панель: поиск, фильтр, сохранение --}}
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center gap-3 justify-between">
                        <div class="flex items-center gap-3 flex-1 min-w-[260px]">
                            <input type="search" x-model="query" x-on:input="apply()"
                                   placeholder="Поиск по ключу или тексту…" autocomplete="off"
                                   class="flex-1 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded px-3 py-1.5 text-sm">
                            @unless ($isReference)
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap cursor-pointer">
                                    <input type="checkbox" x-model="todoOnly" x-on:change="apply()" class="rounded">
                                    только незаконченные
                                </label>
                            @endunless
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500" x-text="counter"></span>
                            <button type="submit" class="bg-black text-white px-4 py-1.5 rounded text-sm hover:bg-gray-800 transition">
                                <i class="fas fa-save mr-1"></i> Сохранить
                            </button>
                        </div>
                    </div>

                    {{-- Таблица --}}
                    <div class="overflow-x-auto max-h-[65vh] overflow-y-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider sticky top-0 z-10">
                                <tr>
                                    <th class="py-2 px-3 text-left w-1/4">Ключ</th>
                                    @unless ($isReference)
                                        <th class="py-2 px-3 text-left w-1/3">Эталон ({{ $reference }})</th>
                                    @endunless
                                    <th class="py-2 px-3 text-left">Перевод ({{ $locale }})</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($rows as $row)
                                    <tr class="tr-row align-top"
                                        data-todo="{{ ($row['missing'] || $row['same']) && !$isReference ? '1' : '0' }}"
                                        data-haystack="{{ mb_strtolower($row['key'] . ' ' . $row['reference'] . ' ' . $row['value']) }}">
                                        <td class="py-2 px-3">
                                            <code class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all">{{ $row['key'] }}</code>
                                            @if ($row['extra'])
                                                <span class="ml-1 text-[10px] px-1 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200" title="Ключа нет в эталонном языке">лишний</span>
                                            @elseif ($row['missing'] && !$isReference)
                                                <span class="ml-1 text-[10px] px-1 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">нет</span>
                                            @elseif ($row['same'] && !$isReference)
                                                <span class="ml-1 text-[10px] px-1 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300" title="Совпадает с эталоном">=</span>
                                            @endif
                                        </td>
                                        @unless ($isReference)
                                            <td class="py-2 px-3 text-xs text-gray-500 dark:text-gray-400">{{ $row['reference'] }}</td>
                                        @endunless
                                        <td class="py-2 px-3">
                                            <textarea name="lines[{{ $row['key'] }}]" rows="1" spellcheck="false"
                                                      x-on:input="fit($event.target)" x-init="fit($el)"
                                                      class="tr-input w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded px-2 py-1 text-sm font-normal resize-y"
                                                      style="min-height: 34px;">{{ $row['value'] }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Подвал --}}
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-gray-500">
                            Пустое поле = ключ удаляется из файла, текст берётся из
                            <code class="font-mono">{{ config('app.fallback_locale') }}</code>.
                            Перед записью создаётся <code class="font-mono">.bak</code>.
                        </span>
                        <button type="submit" class="bg-black text-white px-4 py-1.5 rounded text-sm hover:bg-gray-800 transition">
                            <i class="fas fa-save mr-1"></i> Сохранить
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function translationEditor() {
        return {
            query: '',
            todoOnly: false,
            counter: '',

            init() {
                this.apply();
            },

            // Автовысота поля под содержимое
            fit(el) {
                el.style.height = 'auto';
                el.style.height = (el.scrollHeight + 2) + 'px';
            },

            apply() {
                const q = (this.query || '').trim().toLowerCase();
                const rows = document.querySelectorAll('.tr-row');
                let shown = 0;

                rows.forEach((row) => {
                    const matchesText = q === '' || row.dataset.haystack.indexOf(q) !== -1;
                    const matchesTodo = !this.todoOnly || row.dataset.todo === '1';
                    const visible = matchesText && matchesTodo;

                    row.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });

                this.counter = shown + ' из ' + rows.length + ' строк';
            },
        };
    }
</script>
@endpush
@endsection
