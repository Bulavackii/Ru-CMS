@extends('layouts.admin')

@section('title', __('admin.sections.subscriptions'))

@section('content')
    {{-- Шапка в два ряда (.mh-*, общее определение в лейауте). --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-key"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ __('admin.subscriptions.title') }}
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.subscriptions.hint') }}
            </p>

            <a href="{{ route('admin.subscriptions.promo-codes') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fas fa-ticket"></i> {{ __('admin.subscriptions.promo_codes') }}
            </a>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold col-grow">{{ __('admin.subscriptions.owner') }}</th>
                        <th class="px-4 py-3 text-left font-semibold col-narrow">{{ __('admin.subscriptions.plan') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('admin.common.status') }}</th>
                        <th class="px-4 py-3 text-left font-semibold col-extra">{{ __('admin.subscriptions.until') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($subscriptions as $подписка)
                        @php
                            // Срок мог быть не задан вовсе — тогда подписка бессрочная.
                            $до = $подписка->expires_at ?? null;
                            $активна = ($подписка->status ?? '') === 'active'
                                && ($до === null || \Illuminate\Support\Carbon::parse($до)->isFuture());
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="col-grow px-4 py-3 align-top">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $подписка->user_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $подписка->user_email }}</div>
                            </td>

                            <td class="col-narrow px-4 py-3 align-top">{{ $подписка->plan ?? '—' }}</td>

                            <td class="px-4 py-3 align-top text-center">
                                @if ($активна)
                                    <span class="st-chip st-on inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold
                                                 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300"
                                          title="{{ __('admin.subscriptions.active') }}">
                                        <i class="fas fa-circle-check"></i>
                                        <span class="st-text">{{ __('admin.subscriptions.active') }}</span>
                                    </span>
                                @else
                                    <span class="st-chip st-off inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold
                                                 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                          title="{{ __('admin.subscriptions.expired') }}">
                                        <i class="fas fa-circle-minus"></i>
                                        <span class="st-text">{{ __('admin.subscriptions.expired') }}</span>
                                    </span>
                                @endif
                            </td>

                            <td class="col-extra px-4 py-3 align-top">
                                {{ $до ? \Illuminate\Support\Carbon::parse($до)->format('d.m.Y') : __('admin.subscriptions.unlimited') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="admin-icon-badge mx-auto mb-3"><i class="fas fa-key"></i></div>
                                <div class="font-semibold">{{ __('admin.subscriptions.empty') }}</div>
                                <div class="text-sm mt-1">{{ __('admin.subscriptions.empty_hint') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscriptions->hasPages())
            <div class="px-4 py-3">{{ $subscriptions->links() }}</div>
        @endif
    </div>
@endsection
