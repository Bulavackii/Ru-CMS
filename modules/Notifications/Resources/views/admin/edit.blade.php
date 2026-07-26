@extends('layouts.admin')

@section('title', 'Редактировать уведомление')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-bell"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Редактировать уведомление</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ $notification->title }} · показов: {{ $notification->views_count }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.notifications.preview', $notification) }}" target="_blank"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-eye"></i> Предпросмотр
            </a>
            <a href="{{ route('admin.notifications.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
        </div>
    </div>

    @include('Notifications::admin._form', [
        'notification' => $notification,
        'action' => route('admin.notifications.update', $notification),
        'method' => 'PUT',
        'submitLabel' => 'Сохранить',
    ])
@endsection
