@extends('layouts.admin')

@section('title', 'Создание уведомления')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    {{-- Шапка в два ряда — общий класс `.mh` из лейаута панели (см. edit). --}}
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-bell"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                Новое уведомление
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                Баннер поверх сайта: кому показать, где и как долго.
            </p>

            <a href="{{ route('admin.notifications.index') }}"
               class="mh-back inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
        </div>
    </div>

    @include('Notifications::admin._form', [
        'notification' => null,
        'action' => route('admin.notifications.store'),
        'method' => 'POST',
        'submitLabel' => 'Создать',
    ])
@endsection
