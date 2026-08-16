@extends('layouts.admin')

@section('title', __('admin.updates.title'))

@section('content')
    {{-- Шапка в два ряда (.mh-*, общее определение в лейауте). --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-rotate"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ __('admin.updates.title') }}
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.updates.hint') }}
            </p>

            <a href="{{ route('admin.modules.index') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fas fa-puzzle-piece"></i> {{ __('admin.sections.modules') }}
            </a>
        </div>
    </div>

    @php
        // Служба отдаёт массив; при выключенной проверке обновлений он пуст —
        // это не ошибка, а осознанное умолчание (адрес сервера обновлений
        // пустой, наружу система не ходит).
        $сведения  = (array) ($updateInfo ?? []);
        $доступно  = (bool) ($сведения['available'] ?? false);
        $текущая   = $сведения['current_version'] ?? config('app.version', '1.0.0');
        $новая     = $сведения['latest_version'] ?? null;
        $ошибка    = $сведения['error'] ?? null;
    @endphp

    <div class="admin-card p-5">
        <div class="flex items-center gap-3 mb-4 min-w-0">
            <span class="admin-icon-badge">
                <i class="fas {{ $доступно ? 'fa-arrow-up' : 'fa-circle-check' }}"></i>
            </span>
            <div class="min-w-0">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                    @if ($ошибка)
                        {{ __('admin.updates.check_failed') }}
                    @elseif ($доступно)
                        {{ __('admin.updates.available') }}
                    @else
                        {{ __('admin.updates.up_to_date') }}
                    @endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.updates.current') }}: <b>{{ $текущая }}</b>
                    @if ($новая && $доступно)
                        · {{ __('admin.updates.latest') }}: <b>{{ $новая }}</b>
                    @endif
                </div>
            </div>
        </div>

        @if ($ошибка)
            <div class="admin-hint border-l-4 border-yellow-500 px-4 py-3 text-sm">
                {{ $ошибка }}
            </div>
        @elseif (! $доступно)
            <div class="admin-hint px-4 py-3 text-sm">
                {{ __('admin.updates.no_server') }}
            </div>
        @endif

        @if (! empty($сведения['changelog']))
            <div class="mt-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                    {{ __('admin.updates.changelog') }}
                </div>
                <pre class="text-sm whitespace-pre-wrap">{{ $сведения['changelog'] }}</pre>
            </div>
        @endif
    </div>
@endsection
