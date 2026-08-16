@extends('layouts.admin')

@section('title', __('admin.notif_center.title'))

@section('content')
    {{-- ⚠️ Это НЕ раздел «Уведомления» (сообщения посетителям) — тот живёт в
         модуле Notifications. Здесь центр служебных уведомлений панели:
         новые заказы, сообщения, отзывы. Вьюха отсутствовала, и адрес
         `/admin/notification-center` годами отдавал 500. --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-bell"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ __('admin.notif_center.title') }}
            </h1>

            @if ($unreadCount > 0)
                <span class="mh-status st-chip st-on inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold
                             bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300"
                      title="{{ __('admin.notif_center.unread') }}">
                    <i class="fas fa-circle-exclamation"></i>
                    <span class="st-text">{{ $unreadCount }}</span>
                </span>
            @endif
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.notif_center.hint') }}
            </p>

            <a href="{{ route('admin.dashboard') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fas fa-arrow-left"></i> {{ __('admin.sections.dashboard') }}
            </a>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        @forelse ($notifications as $уведомление)
            @php
                $прочитано = (bool) ($уведомление->read ?? false);
                $когда = $уведомление->created_at ?? null;
            @endphp

            <div class="flex items-start gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800 min-w-0
                        {{ $прочитано ? '' : 'bg-indigo-50 dark:bg-gray-800' }}">
                <span class="admin-icon-badge flex-none"><i class="fas fa-bell"></i></span>

                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-gray-900 dark:text-white break-words">
                        {{ $уведомление->title ?? __('admin.notif_center.no_title') }}
                    </div>

                    @if (! empty($уведомление->message))
                        <div class="text-sm text-gray-600 dark:text-gray-300 break-words">{{ $уведомление->message }}</div>
                    @endif

                    @if ($когда)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ \Illuminate\Support\Carbon::parse($когда)->format('d.m.Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-gray-500 dark:text-gray-400">
                <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-bell-slash"></i></span>
                <div class="font-semibold text-gray-900 dark:text-white">{{ __('admin.notif_center.empty') }}</div>
                <p class="text-sm mt-2">{{ __('admin.notif_center.empty_hint') }}</p>
            </div>
        @endforelse
    </div>
@endsection
