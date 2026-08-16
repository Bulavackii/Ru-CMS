@extends('layouts.admin')

@section('title', 'Редактировать уведомление')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    {{-- Шапка в два ряда — общий класс `.mh` из лейаута панели.
         Прежняя компоновка была старого образца и на 360 распирала страницу
         на 50 пикселей: `flex-shrink-0` у ряда кнопок не давал ему сжаться, а
         название с подписью не сжимались ниже содержимого. --}}
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-bell"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                Редактировать уведомление
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400 truncate">
                {{ $notification->title }} · показов: {{ $notification->views_count }}
            </p>

            <span class="mh-back flex items-center gap-2">
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
            </span>
        </div>
    </div>

    @include('Notifications::admin._form', [
        'notification' => $notification,
        'action' => route('admin.notifications.update', $notification),
        'method' => 'PUT',
        'submitLabel' => 'Сохранить',
    ])
@endsection
