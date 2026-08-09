@extends('layouts.frontend')

@section('title', __('frontend.account.login_history'))

@section('content')
<div class="acc-head">
    <span class="fx-badge"><i class="fas fa-clock-rotate-left"></i></span>
    <div class="min-w-0">
        <h1 class="fx-section-title">{{ __('frontend.account.login_history') }}</h1>
        <p class="fx-section-sub">{{ __('frontend.account.history_hint') }}</p>
    </div>
</div>

<section class="fx-card p-5">
    @if($loginHistory->isEmpty())
        <div class="lh-empty">
            <span class="fx-badge mx-auto"><i class="fas fa-clock-rotate-left"></i></span>
            <p class="lh-empty__title">{{ __('frontend.account.history_empty') }}</p>
            <p class="fx-section-sub">{{ __('frontend.account.history_empty_hint') }}</p>
        </div>
    @else
        <p class="lh-total">
            {{ __('frontend.account.history_total') }}: <b>{{ $loginHistory->total() }}</b>
        </p>

        {{-- Карточки вместо таблицы: на телефоне таблица из пяти колонок
             уезжала в горизонтальную прокрутку и читалась плохо. --}}
        @foreach($loginHistory as $login)
            @php
                $tone = match ($login->status) {
                    'success' => 'ok',
                    'failed', 'blocked' => 'bad',
                    default => 'wait',
                };
                $statusLabel = match ($login->status) {
                    'success' => __('frontend.account.h_success'),
                    'failed' => __('frontend.account.h_failed'),
                    'blocked' => __('frontend.account.h_blocked'),
                    default => $login->status,
                };
                $deviceIcon = match ($login->device_type) {
                    'mobile' => 'fa-mobile-screen',
                    'tablet' => 'fa-tablet-screen-button',
                    default => 'fa-desktop',
                };
            @endphp

            <article class="lh-row">
                <div class="lh-row__main">
                    <div class="lh-row__top">
                        <b>{{ $login->created_at->format('d.m.Y H:i:s') }}</b>

                        <span class="lh-status lh-status--{{ $tone }}">{{ $statusLabel }}</span>

                        @if($login->is_suspicious)
                            <span class="lh-status lh-status--warn" title="{{ $login->suspicious_reason }}">
                                <i class="fas fa-triangle-exclamation"></i> {{ __('frontend.account.h_suspicious') }}
                            </span>
                        @endif
                    </div>

                    <div class="lh-row__meta">
                        <span><i class="fas {{ $deviceIcon }} fx-ico"></i>
                            {{ $login->platform ?: __('frontend.account.h_unknown') }}@if($login->browser) · {{ $login->browser }}@endif
                        </span>
                        <span><i class="fas fa-location-dot fx-ico"></i>
                            {{ $login->location ?: __('frontend.account.h_unknown') }}</span>
                    </div>
                </div>

                <code class="lh-ip">{{ $login->ip_address ?: '—' }}</code>
            </article>
        @endforeach

        {{-- Ссылки страниц сохраняют строку запроса — иначе фильтры и
             язык слетали бы при переходе на вторую страницу. --}}
        <div class="lh-pager">
            {{ $loginHistory->withQueryString()->links() }}
        </div>
    @endif
</section>

<div class="mt-4">
    <a href="{{ route('dashboard') }}" class="acc-btn-ghost">
        <i class="fas fa-arrow-left"></i> {{ __('frontend.common.back') }}
    </a>
</div>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни части палитры, на которой держались прежние бейджи. */
    .acc-head{ display:flex; align-items:center; gap:.9rem; margin-bottom:1.25rem }
    .acc-btn-ghost{ display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1rem;
                    border:1px solid var(--surface-bd,#e5e7eb); background:var(--surface,#fff); color:var(--surface-ink,#374151);
                    font-size:.85rem; font-weight:600 }
    .acc-btn-ghost:hover{ border-color:#a5b4fc; color:#4f46e5 }

    .lh-total{ font-size:.85rem; color:var(--surface-mute,#6b7280); margin-bottom:.9rem }

    .lh-row{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
             gap:.75rem; border:1px solid var(--surface-bd,#eef2f7); background:var(--surface,#fff);
             padding:.75rem 1rem; margin-bottom:.5rem }
    .lh-row:hover{ border-color:#c7d2fe }
    .lh-row__top{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem }
    .lh-row__meta{ display:flex; flex-wrap:wrap; gap:.25rem 1rem; margin-top:.35rem;
                   font-size:.8rem; color:var(--surface-mute,#6b7280) }
    .lh-ip{ font-size:.8rem; color:var(--surface-ink,#4b5563); background:var(--surface-2,#f8fafc);
            border:1px solid var(--surface-bd,#eef2f7); padding:.15rem .45rem; white-space:nowrap }

    .lh-status{ font-size:.72rem; font-weight:700; padding:.15rem .5rem; border:1px solid }
    .lh-status--ok{ color:color-mix(in srgb, #16a34a 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #16a34a 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #16a34a 34%, var(--surface,#fff)) }
    .lh-status--bad{ color:color-mix(in srgb, #dc2626 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #dc2626 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #dc2626 34%, var(--surface,#fff)) }
    .lh-status--wait{ color:color-mix(in srgb, #4f46e5 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #4f46e5 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #4f46e5 34%, var(--surface,#fff)) }
    .lh-status--warn{ color:color-mix(in srgb, #d97706 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #d97706 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #d97706 34%, var(--surface,#fff)) }

    .lh-empty{ text-align:center; padding:2.5rem 1rem }
    .lh-empty__title{ font-weight:700; color:var(--surface-ink,#111827); margin:.9rem 0 .25rem }

    .lh-pager{ margin-top:1rem }

    @media (prefers-color-scheme: dark){
        .lh-row, .acc-btn-ghost{ background:transparent; border-color:#374151 }
        .lh-empty__title{ color:#f3f4f6 }
        .lh-ip{ background:transparent; border-color:#374151; color:#d1d5db }
        .acc-btn-ghost{ color:#d1d5db }
    }
</style>
@endpush
