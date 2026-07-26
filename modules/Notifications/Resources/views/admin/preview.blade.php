@extends('layouts.admin')

@section('title', 'Предпросмотр уведомления')

@section('content')
@php
    $positionLabels = ['top' => 'Сверху', 'bottom' => 'Снизу', 'fullscreen' => 'По центру экрана'];
    $targetLabels = ['all' => 'Все посетители', 'admin' => 'Только администраторы', 'user' => 'Только авторизованные'];
    $typeLabels = ['text' => 'Текст', 'html' => 'HTML', 'cookie' => 'Одноразовое'];

    $now = now();
    $notStarted = $notification->starts_at && $notification->starts_at->gt($now);
    $expired = $notification->ends_at && $notification->ends_at->lt($now);
    $visible = $notification->enabled && !$notStarted && !$expired;
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-eye"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Предпросмотр</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $notification->title }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('admin.notifications.edit', $notification) }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-pen"></i> Редактировать
        </a>
        <a href="{{ route('admin.notifications.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
            <i class="fas fa-arrow-left"></i> К списку
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    {{-- ── Как увидит посетитель ── --}}
    <div class="lg:col-span-2 admin-card p-5">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-desktop text-indigo-500"></i> Так это увидит посетитель
        </h2>

        {{-- Имитация окна браузера --}}
        <div class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="flex items-center gap-1 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="w-2 h-2 bg-gray-300"></span>
                <span class="w-2 h-2 bg-gray-300"></span>
                <span class="w-2 h-2 bg-gray-300"></span>
                <span class="ml-3 text-xs font-mono text-gray-400">
                    {{ $notification->route_filter ?: 'любая страница сайта' }}
                </span>
            </div>

            <div class="relative p-6 flex {{ $notification->position === 'bottom' ? 'items-end' : ($notification->position === 'fullscreen' ? 'items-center' : 'items-start') }} justify-center"
                 style="min-height: 320px;">
                <div style="width:100%; max-width:560px; padding:18px 44px 18px 20px; position:relative;
                            background:{{ $notification->bg_color ?: '#ffffff' }};
                            color:{{ $notification->text_color ?: '#111827' }};
                            border:1px solid rgba(17,24,39,.1);
                            box-shadow:0 18px 40px -18px rgba(17,24,39,.45);">
                    <span style="position:absolute; top:10px; right:12px; font-size:20px; line-height:1; opacity:.5;">&times;</span>

                    @if($notification->icon || $notification->title)
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                            @if($notification->icon)
                                <span style="font-size:1.15rem; line-height:1;">
                                    @if(str_starts_with(trim($notification->icon), 'fa'))
                                        <i class="{{ $notification->icon }}"></i>
                                    @else
                                        {{ $notification->icon }}
                                    @endif
                                </span>
                            @endif
                            <strong style="font-size:.95rem;">{{ $notification->title }}</strong>
                        </div>
                    @endif

                    <div style="font-size:.88rem; line-height:1.55;">
                        @if($notification->type === 'text')
                            {{ strip_tags((string) $notification->message) }}
                        @else
                            {!! $notification->message !!}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <p class="admin-hint mt-3">
            Предпросмотр показывает оформление и позицию. Реальный баннер появляется
            на сайте с учётом аудитории, страниц и периода показа — см. панель справа.
        </p>
    </div>

    {{-- ── Условия показа ── --}}
    <div class="space-y-5">
        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-circle-info text-indigo-500"></i> Показывается ли сейчас
            </h2>

            @if($visible)
                <p class="text-sm text-green-700 dark:text-green-400 font-semibold flex items-center gap-2">
                    <i class="fas fa-circle-check"></i> Да, уведомление активно
                </p>
            @else
                <p class="text-sm text-yellow-700 dark:text-yellow-500 font-semibold flex items-center gap-2">
                    <i class="fas fa-circle-exclamation"></i>
                    @if(!$notification->enabled)
                        Нет — уведомление отключено
                    @elseif($notStarted)
                        Нет — показ начнётся {{ $notification->starts_at->format('d.m.Y H:i') }}
                    @else
                        Нет — срок показа истёк {{ $notification->ends_at->format('d.m.Y H:i') }}
                    @endif
                </p>
            @endif

            <dl class="mt-4 space-y-2 text-sm">
                @foreach([
                    ['Аудитория', $targetLabels[$notification->target] ?? $notification->target],
                    ['Страницы', $notification->route_filter ?: 'все страницы сайта'],
                    ['Позиция', $positionLabels[$notification->position] ?? $notification->position],
                    ['Тип', $typeLabels[$notification->type] ?? $notification->type],
                    ['Скрыть через', $notification->duration ? $notification->duration . ' сек' : 'до закрытия вручную'],
                    ['Приоритет', $notification->priority],
                    ['Показов', $notification->views_count],
                ] as [$label, $value])
                    <div class="flex justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium text-right break-words">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <i class="fas fa-clock text-indigo-500"></i> Период
            </h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ optional($notification->starts_at)->format('d.m.Y H:i') ?: 'без даты начала' }}
                —
                {{ optional($notification->ends_at)->format('d.m.Y H:i') ?: 'без даты окончания' }}
            </p>
            <p class="admin-hint mt-2">
                Создано {{ optional($notification->created_at)->format('d.m.Y H:i') }}@if($notification->creator),
                {{ $notification->creator->name }}@endif.
            </p>
        </div>
    </div>
</div>
@endsection
