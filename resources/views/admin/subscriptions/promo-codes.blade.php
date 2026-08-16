@extends('layouts.admin')

@section('title', __('admin.subscriptions.promo_codes'))

@section('content')
    {{-- Шапка в два ряда (.mh-*, общее определение в лейауте). --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-ticket"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ __('admin.subscriptions.promo_codes') }}
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.subscriptions.promo_hint') }}
            </p>

            <a href="{{ route('admin.subscriptions.index') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fas fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold col-grow">{{ __('admin.subscriptions.code') }}</th>
                        <th class="px-4 py-3 text-left font-semibold col-narrow">{{ __('admin.subscriptions.discount') }}</th>
                        <th class="px-4 py-3 text-center font-semibold col-extra">{{ __('admin.subscriptions.used') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('admin.common.status') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($promoCodes as $промокод)
                        @php
                            $до = $промокод->expires_at ?? null;
                            $действует = (bool) ($промокод->is_active ?? true)
                                && ($до === null || \Illuminate\Support\Carbon::parse($до)->isFuture());
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="col-grow px-4 py-3 align-top">
                                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $промокод->code }}</span>
                                @if ($до)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('admin.subscriptions.until') }}:
                                        {{ \Illuminate\Support\Carbon::parse($до)->format('d.m.Y') }}
                                    </div>
                                @endif
                            </td>

                            <td class="col-narrow px-4 py-3 align-top">
                                {{ $промокод->discount_percent ?? $промокод->discount ?? '—' }}%
                            </td>

                            <td class="col-extra px-4 py-3 align-top text-center">
                                {{ $промокод->used_count ?? 0 }}@if(!empty($промокод->max_uses) )&nbsp;/&nbsp;{{ $промокод->max_uses }}@endif
                            </td>

                            <td class="px-4 py-3 align-top text-center">
                                @if ($действует)
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="admin-icon-badge mx-auto mb-3"><i class="fas fa-ticket"></i></div>
                                <div class="font-semibold">{{ __('admin.subscriptions.promo_empty') }}</div>
                                <div class="text-sm mt-1">{{ __('admin.subscriptions.promo_empty_hint') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($promoCodes->hasPages())
            <div class="px-4 py-3">{{ $promoCodes->links() }}</div>
        @endif
    </div>
@endsection
