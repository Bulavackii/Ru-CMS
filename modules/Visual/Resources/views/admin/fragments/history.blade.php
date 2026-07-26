@extends('layouts.admin')
@section('title', 'История фрагмента')

@section('content')
{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-clock-rotate-left"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">История версий</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                {{ $fragment->title }} · <span class="font-mono">{{ $fragment->slug }}</span>
            </p>
        </div>
    </div>

    <a href="{{ route('admin.visual.fragments.edit', $fragment) }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-left"></i> К фрагменту
    </a>
</div>

@includeIf('layouts.partials.flash')

@if($revisions->isEmpty())
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-clock-rotate-left"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Версий пока нет</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Каждое сохранение фрагмента добавляет сюда запись — можно вернуться к любой из последних 50.
        </p>
    </div>
@else
    <div class="admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Когда</th>
                    <th class="px-4 py-3 text-left font-semibold">Кто</th>
                    <th class="px-4 py-3 text-left font-semibold">Что было сохранено</th>
                    <th class="px-4 py-3 text-center font-semibold">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($revisions as $revision)
                    @php $snapshot = (array) $revision->snapshot; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 align-top whitespace-nowrap text-gray-900 dark:text-white">
                            {{ optional($revision->created_at)->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                            {{ $revision->creator->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                            <div>{{ $snapshot['title'] ?? '—' }}</div>
                            <div class="text-xs text-gray-400 mt-1">
                                зона: {{ \Modules\Visual\Models\Fragment::ZONE_LABELS[$snapshot['zone'] ?? ''] ?? ($snapshot['zone'] ?: 'без зоны') }}
                                · {{ ($snapshot['is_active'] ?? false) ? 'включён' : 'выключен' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top text-center">
                            <button type="submit" form="revert-{{ $revision->id }}"
                                    class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600
                                           text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600
                                           px-3 py-1.5 text-sm transition">
                                <i class="fas fa-rotate-left"></i> Вернуть
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $revisions->links() }}</div>

    {{-- Формы отката — вне таблицы --}}
    @foreach($revisions as $revision)
        <form id="revert-{{ $revision->id }}" method="POST"
              action="{{ route('admin.visual.fragments.revert', [$fragment, $revision->id]) }}" class="hidden"
              onsubmit="return confirm('Вернуть фрагмент к версии от {{ optional($revision->created_at)->format('d.m.Y H:i') }}?');">
            @csrf
        </form>
    @endforeach

    <p class="admin-hint mt-5 p-3">
        Откат создаёт новую версию, поэтому вернуться к текущему состоянию можно будет так же.
        Хранятся последние 50 версий.
    </p>
@endif
@endsection
