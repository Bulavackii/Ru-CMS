@extends('layouts.admin')

@section('title', __('admin.messages.title'))

@section('content')
@php
    $tabs = [
        'all' => 'fa-inbox',
        'inbox' => 'fa-arrow-down',
        'sent' => 'fa-arrow-up',
        'unread' => 'fa-envelope',
        'important' => 'fa-star',
        'archived' => 'fa-box-archive',
    ];

    $filtered = $search !== '' || $filter !== 'all';
@endphp

{{-- ── Шапка раздела ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-4
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-envelope"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.messages.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.messages.subtitle') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.messages.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
              px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
        <i class="fas fa-plus"></i> {{ __('admin.messages.create') }}
    </a>
</div>

{{-- ── Поиск ── --}}
<form method="GET" action="{{ route('admin.messages.index') }}" class="msg-search admin-card mb-4">
    <input type="hidden" name="filter" value="{{ $filter }}">

    <i class="fas fa-magnifying-glass msg-search__ico"></i>

    <input type="search" name="search" value="{{ $search }}"
           placeholder="{{ __('admin.messages.search_ph') }}"
           class="msg-search__field">

    <button type="submit"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                   px-4 py-2 text-sm font-semibold transition flex-shrink-0">
        {{ __('admin.messages.search') }}
    </button>

    @if($search !== '')
        <a href="{{ route('admin.messages.index', ['filter' => $filter]) }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition flex-shrink-0">
            {{ __('admin.messages.reset') }}
        </a>
    @endif
</form>

{{-- ── Вкладки ── --}}
<div class="msg-tabs mb-4">
    @foreach($tabs as $key => $icon)
        <a href="{{ route('admin.messages.index', array_filter(['filter' => $key, 'search' => $search])) }}"
           class="msg-tab {{ $filter === $key ? 'is-active' : '' }}">
            <i class="fas {{ $icon }}"></i>
            <span>{{ __('admin.messages.f_' . $key) }}</span>
            {{-- Счётчика у архива раньше не было — вкладка стояла без числа. --}}
            <b>{{ $counts[$key] ?? 0 }}</b>
        </a>
    @endforeach
</div>

@if($messages->isNotEmpty())
    <form method="POST" action="{{ route('admin.messages.bulk') }}" x-data="{ picked: [] }">
        @csrf

        {{-- Панель массовых действий: раньше выбор был, а выбрать «все»
             можно было только кнопкой, которую дорисовывал скрипт. --}}
        <div class="msg-bulk admin-card mb-3">
            <label class="msg-bulk__all">
                <input type="checkbox"
                       @change="picked = $event.target.checked ? @js($messages->pluck('id')->all()) : []"
                       :checked="picked.length === {{ $messages->count() }}">
                <span>{{ __('admin.messages.select_all') }}</span>
            </label>

            <span class="text-sm text-gray-500 dark:text-gray-400" x-show="picked.length" x-cloak
                  x-text="@js(__('admin.messages.selected')).replace(':count', picked.length)"></span>

            <div class="msg-bulk__actions" x-show="picked.length" x-cloak>
                <select name="action" required
                        class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                    <option value="">{{ __('admin.messages.bulk_action') }}</option>
                    <option value="read">{{ __('admin.messages.bulk_read') }}</option>
                    <option value="important">{{ __('admin.messages.bulk_important') }}</option>
                    <option value="archive">{{ __('admin.messages.bulk_archive') }}</option>
                    <option value="delete">{{ __('admin.messages.bulk_delete') }}</option>
                </select>

                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               px-4 py-2 text-sm font-semibold transition">
                    <i class="fas fa-check"></i> {{ __('admin.messages.apply') }}
                </button>

                <button type="button" @click="picked = []"
                        class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                               hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                    {{ __('admin.messages.clear') }}
                </button>
            </div>
        </div>

        @foreach($messages as $msg)
            @php
                $incoming = $msg->to_user_id === auth()->id();
                $unread = $incoming && ! $msg->is_read;
            @endphp

            <div class="msg-row {{ $unread ? 'is-unread' : '' }}" :class="picked.includes({{ $msg->id }}) && 'is-picked'">
                <input type="checkbox" name="ids[]" value="{{ $msg->id }}" x-model.number="picked" class="msg-row__pick">

                <a href="{{ route('admin.messages.show', $msg) }}" class="msg-row__main">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($msg->is_important)
                            <i class="fas fa-star msg-star" title="{{ __('admin.messages.is_important') }}"></i>
                        @endif

                        <strong class="msg-row__subject">{{ $msg->subject }}</strong>

                        @if($unread)
                            <span class="msg-chip msg-chip--new">{{ __('admin.messages.is_unread') }}</span>
                        @endif

                        @if($msg->attachments->count())
                            <span class="msg-chip">
                                <i class="fas fa-paperclip"></i>
                                {{ __('admin.messages.has_files', ['count' => $msg->attachments->count()]) }}
                            </span>
                        @endif
                    </div>

                    <div class="msg-row__meta">
                        @if($incoming)
                            <span><i class="fas fa-arrow-down"></i>
                                {{ __('admin.messages.from') }}: {{ $msg->sender->name ?? __('admin.messages.unknown') }}</span>
                        @else
                            <span><i class="fas fa-arrow-up"></i>
                                {{ __('admin.messages.to') }}: {{ $msg->receiver->name ?? __('admin.messages.unknown') }}</span>
                        @endif

                        <span>{{ $msg->created_at->format('d.m.Y H:i') }}</span>
                        <span class="msg-row__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($msg->body), 90) }}</span>
                    </div>
                </a>

                <div class="msg-row__actions">
                    <a href="{{ route('admin.messages.reply', $msg) }}" class="msg-btn" title="{{ __('admin.messages.reply') }}">
                        <i class="fas fa-reply"></i>
                    </a>

                    {{-- Форма важности лежала ВНУТРИ формы массовых действий —
                         вложенные form браузер выбрасывает при разборе, кнопка
                         отправляла внешнюю форму. Отсюда — form=""/formaction. --}}
                    <button type="submit" class="msg-btn" form="msg-toggle-{{ $msg->id }}"
                            title="{{ $msg->is_important ? __('admin.messages.unmark_important') : __('admin.messages.mark_important') }}">
                        <i class="fas fa-star {{ $msg->is_important ? 'msg-star' : '' }}"></i>
                    </button>

                    <button type="submit" class="msg-btn msg-btn--danger" form="msg-delete-{{ $msg->id }}"
                            title="{{ __('admin.messages.delete') }}"
                            onclick="return confirm(@js(__('admin.messages.delete_confirm')))">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </form>

    {{-- Формы действий над отдельным письмом живут ВНЕ формы массовых
         действий и связаны с кнопками через атрибут form. --}}
    @foreach($messages as $msg)
        <form id="msg-toggle-{{ $msg->id }}" method="POST"
              action="{{ route('admin.messages.toggle-important', $msg) }}" class="hidden">@csrf</form>

        <form id="msg-delete-{{ $msg->id }}" method="POST"
              action="{{ route('admin.messages.destroy', $msg) }}" class="hidden">@csrf @method('DELETE')</form>
    @endforeach

    @if($messages->hasPages())
        <div class="mt-4">{{ $messages->links() }}</div>
    @endif
@else
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-envelope-open"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
            {{ $filtered ? __('admin.messages.empty_filtered') : __('admin.messages.empty') }}
        </h2>
        <p class="admin-hint max-w-xl mx-auto mb-4">
            {{ $filtered ? __('admin.messages.empty_filtered_hint') : __('admin.messages.empty_hint') }}
        </p>

        @unless($filtered)
            <a href="{{ route('admin.messages.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                      px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-plus"></i> {{ __('admin.messages.empty_create') }}
            </a>
        @endunless
    </div>
@endif
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    .msg-search{ display:flex; align-items:center; gap:.5rem; padding:.6rem .75rem; position:relative }
    .msg-search__ico{ color:#9ca3af; font-size:.9rem; padding-left:.35rem }
    .msg-search__field{ flex:1; min-width:0; border:0; background:transparent; padding:.5rem .25rem;
                        font-size:.9rem; color:#111827; outline:none }

    .msg-tabs{ display:flex; flex-wrap:wrap; gap:.35rem }
    .msg-tab{ display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .85rem; font-size:.85rem;
              font-weight:600; color:#475569; border:1px solid #e5e7eb; background:#fff; transition:.15s }
    .msg-tab:hover{ border-color:#c7d2fe; color:#4f46e5 }
    .msg-tab i{ font-size:.8rem; color:#9ca3af }
    .msg-tab b{ font-size:.75rem; color:#6b7280; font-weight:700 }
    .msg-tab.is-active{ background:#4f46e5; border-color:#4f46e5; color:#fff }
    .msg-tab.is-active i, .msg-tab.is-active b{ color:#e0e7ff }

    .msg-bulk{ display:flex; flex-wrap:wrap; align-items:center; gap:.75rem; padding:.65rem .85rem }
    .msg-bulk__all{ display:inline-flex; align-items:center; gap:.5rem; font-size:.85rem; font-weight:600;
                    color:#374151; cursor:pointer }
    .msg-bulk__actions{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; margin-left:auto }

    .msg-row{ display:flex; align-items:flex-start; gap:.75rem; border:1px solid #eef2f7; background:#fff;
              padding:.75rem .85rem; margin-bottom:.4rem; transition:border-color .15s }
    .msg-row:hover{ border-color:#c7d2fe }
    .msg-row.is-unread{ border-left:3px solid #4f46e5 }
    .msg-row.is-picked{ border-color:#818cf8; background:#f5f7ff }
    .msg-row__pick{ margin-top:.3rem; flex-shrink:0 }
    .msg-row__main{ flex:1; min-width:0; display:block }
    .msg-row__subject{ color:#111827; font-size:.95rem }
    .msg-row.is-unread .msg-row__subject{ font-weight:700 }
    .msg-row__meta{ display:flex; flex-wrap:wrap; gap:.25rem 1rem; margin-top:.3rem;
                    font-size:.75rem; color:#6b7280 }
    .msg-row__excerpt{ color:#9ca3af }

    .msg-chip{ display:inline-flex; align-items:center; gap:.3rem; font-size:.68rem; font-weight:700;
               padding:.12rem .45rem; border:1px solid #e5e7eb; color:#6b7280 }
    .msg-chip--new{ color:#3730a3; background:#eef2ff; border-color:#c7d2fe }
    .msg-star{ color:#f59e0b }

    .msg-row__actions{ display:flex; align-items:center; gap:.15rem; flex-shrink:0 }
    .msg-btn{ display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem;
              color:#9ca3af; background:transparent; border:0; cursor:pointer; transition:.15s }
    .msg-btn:hover{ color:#4f46e5; background:#f1f5f9 }
    .msg-btn--danger:hover{ color:#dc2626 }

    @media (max-width: 640px){
        .msg-row__actions{ width:100%; justify-content:flex-end }
    }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) — это
       настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не оформление панели. При тёмной
       системе и светлой панели он перекрашивал текст в почти белый на
       белом фоне: сумма заказа пропадала совсем. Тему панели задают класс
       .dark и переменные --admin-*; перекрытие по настройке ОС их только
       ломало. */
</style>
@endpush
