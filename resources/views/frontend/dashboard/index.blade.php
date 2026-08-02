@extends('layouts.frontend')

@section('title', __('frontend.account.title'))

@section('content')
@php
    // Подписи статусов — из словаря. Раньше выводился сырой
    // ucfirst($order->status), то есть покупатель на любом языке видел
    // «Pending» и «Completed».
    $statusLabels = [
        'pending' => __('frontend.account.st_pending'),
        'paid' => __('frontend.account.st_paid'),
        'completed' => __('frontend.account.st_completed'),
        'cancelled' => __('frontend.account.st_cancelled'),
        'canceled' => __('frontend.account.st_cancelled'),
    ];
@endphp

{{-- ── Шапка раздела ── --}}
<div class="acc-head">
    <span class="fx-badge"><i class="fas fa-user"></i></span>
    <div class="min-w-0">
        <h1 class="fx-section-title">{{ __('frontend.account.title') }}</h1>
        <p class="fx-section-sub">{{ __('frontend.account.subtitle') }}</p>
    </div>
</div>

@if (session('success'))
    <div class="acc-flash">{{ session('success') }}</div>
@endif

<div class="acc-grid">

    {{-- ── Профиль ── --}}
    <section class="fx-card p-5">
        <h2 class="acc-h2"><i class="fas fa-id-card fx-ico"></i> {{ __('frontend.account.profile') }}</h2>

        <dl class="acc-list">
            <div><dt>{{ __('frontend.account.name') }}</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>{{ __('frontend.account.email') }}</dt><dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd></div>
            <div><dt>{{ __('frontend.account.user_type') }}</dt>
                <dd>{{ $user->is_company ? __('frontend.account.legal_entity_type') : __('frontend.account.individual') }}</dd></div>

            @if ($user->is_company)
                <div><dt>{{ __('frontend.account.company') }}</dt><dd>{{ $user->company_name ?: '—' }}</dd></div>
                <div><dt>{{ __('frontend.account.inn') }}</dt><dd>{{ $user->inn ?: '—' }}</dd></div>
                <div><dt>{{ __('frontend.account.ogrn') }}</dt><dd>{{ $user->ogrn ?: '—' }}</dd></div>
            @endif
        </dl>
    </section>

    {{-- ── Действия ──
         Раньше этот блок лежал ВНУТРИ ветки «есть заказы»: у пользователя
         без заказов не было ни кнопки правки профиля, ни смены пароля. --}}
    <section class="fx-card p-5">
        <h2 class="acc-h2"><i class="fas fa-sliders fx-ico"></i> {{ __('frontend.account.actions') }}</h2>

        <div class="acc-actions">
            <a href="{{ route('dashboard.edit') }}" class="fx-btn">
                <i class="fas fa-pen"></i> {{ __('frontend.account.edit') }}
            </a>

            @if ($user->is_company)
                <a href="{{ route('organization.edit') }}" class="acc-btn-ghost">
                    <i class="fas fa-building"></i> {{ __('frontend.account.edit_org') }}
                </a>
            @endif

            <a href="{{ route('password.change.form') }}" class="acc-btn-ghost">
                <i class="fas fa-lock"></i> {{ __('frontend.account.change_pass') }}
            </a>

            @if (Route::has('dashboard.login-history'))
                <a href="{{ route('dashboard.login-history') }}" class="acc-btn-ghost">
                    <i class="fas fa-clock-rotate-left"></i> {{ __('frontend.account.login_history') }}
                </a>
            @endif
        </div>

        <p class="acc-count">
            {{ __('frontend.account.orders_count') }}: <b>{{ $orders->count() }}</b>
        </p>
    </section>
</div>

{{-- ── Заказы ── --}}
<section class="fx-card p-5 mt-5">
    <div class="acc-orders-head">
        <h2 class="acc-h2 mb-0"><i class="fas fa-box fx-ico"></i> {{ __('frontend.account.orders_last') }}</h2>

        @if ($orders->count())
            <a href="{{ route('dashboard.orders') }}" class="acc-all">{{ __('frontend.account.orders_all') }} →</a>
        @endif
    </div>

    @forelse ($orders as $order)
        @php
            $status = $order->status ?? '';
            $label = $statusLabels[$status] ?? __('frontend.account.st_unknown');
            $tone = match ($status) {
                'completed', 'paid' => 'ok',
                'cancelled', 'canceled' => 'bad',
                default => 'wait',
            };
        @endphp

        <article class="acc-order">
            <div class="acc-order__main">
                <div class="acc-order__top">
                    <b>{{ __('frontend.account.order_number') }}{{ $order->id }}</b>
                    <span class="acc-status acc-status--{{ $tone }}">{{ $label }}</span>
                </div>

                <div class="acc-order__meta">
                    <span>{{ $order->created_at->format('d.m.Y H:i') }}</span>
                    <span>{{ $order->items->sum('qty') ?: ($order->qty ?? 0) }} {{ __('frontend.account.items') }}</span>
                    <span>{{ __('frontend.account.payment') }}: <b>{{ $order->paymentMethod->title ?? '—' }}</b></span>
                    <span>{{ __('frontend.account.delivery') }}: <b>{{ $order->deliveryMethod->title ?? '—' }}</b></span>
                </div>
            </div>

            <div class="acc-order__sum">
                <span class="acc-order__sum-label">{{ __('frontend.account.order_sum') }}</span>
                <b>{{ number_format((float) $order->total, 2, ',', ' ') }} ₽</b>
            </div>
        </article>
    @empty
        <div class="acc-empty">
            <span class="fx-badge mx-auto"><i class="fas fa-box-open"></i></span>
            <p class="acc-empty__title">{{ __('frontend.account.orders_empty') }}</p>
            <p class="fx-section-sub">{{ __('frontend.account.orders_none_hint') }}</p>

            @if (Route::has('news.index'))
                <a href="{{ route('news.index') }}" class="fx-btn mt-4">
                    <i class="fas fa-newspaper"></i> {{ __('frontend.account.to_catalog') }}
                </a>
            @endif
        </div>
    @endforelse
</section>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни динамических классов вида bg-{$color}-100 — именно на
       них держались прежние бейджи статуса, и они выводились бесцветными. */
    .acc-head{ display:flex; align-items:center; gap:.9rem; margin-bottom:1.25rem }
    .acc-h2{ font-size:.78rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
             color:#9ca3af; margin-bottom:.9rem }

    .acc-flash{ border:1px solid #bbf7d0; background:#f0fdf4; color:#166534;
                padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem }

    .acc-grid{ display:grid; grid-template-columns:1fr; gap:1rem }
    @media (min-width:900px){ .acc-grid{ grid-template-columns:1.35fr 1fr } }

    .acc-list{ display:grid; gap:.55rem; font-size:.92rem }
    .acc-list > div{ display:flex; gap:.6rem; flex-wrap:wrap; align-items:baseline }
    .acc-list dt{ color:#6b7280; min-width:10rem }
    .acc-list dd{ margin:0; font-weight:600; color:#111827 }
    .acc-list a{ color:#4f46e5 }

    .acc-actions{ display:flex; flex-wrap:wrap; gap:.5rem }
    .acc-btn-ghost{ display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1rem;
                    border:1px solid #e5e7eb; background:#fff; color:#374151;
                    font-size:.85rem; font-weight:600; transition:border-color .15s, color .15s }
    .acc-btn-ghost:hover{ border-color:#a5b4fc; color:#4f46e5 }
    .acc-count{ margin-top:.9rem; font-size:.85rem; color:#6b7280 }

    .acc-orders-head{ display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.9rem }
    .acc-all{ font-size:.85rem; font-weight:600; color:#4f46e5 }

    .acc-order{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;
                border:1px solid #eef2f7; padding:.85rem 1rem; margin-bottom:.6rem; background:#fff }
    .acc-order:hover{ border-color:#c7d2fe }
    .acc-order__top{ display:flex; align-items:center; gap:.6rem; flex-wrap:wrap }
    .acc-order__meta{ display:flex; flex-wrap:wrap; gap:.25rem 1rem; margin-top:.35rem;
                      font-size:.8rem; color:#6b7280 }
    .acc-order__sum{ text-align:right; white-space:nowrap }
    .acc-order__sum-label{ display:block; font-size:.72rem; color:#9ca3af }
    .acc-order__sum b{ font-size:1.05rem; color:#111827 }

    .acc-status{ font-size:.72rem; font-weight:700; padding:.15rem .5rem; border:1px solid }
    .acc-status--ok{ color:#166534; background:#f0fdf4; border-color:#bbf7d0 }
    .acc-status--wait{ color:#92400e; background:#fffbeb; border-color:#fde68a }
    .acc-status--bad{ color:#991b1b; background:#fef2f2; border-color:#fecaca }

    .acc-empty{ text-align:center; padding:2.5rem 1rem }
    .acc-empty__title{ font-weight:700; color:#111827; margin:.9rem 0 .25rem }

    @media (prefers-color-scheme: dark){
        .acc-list dd, .acc-order__sum b, .acc-empty__title{ color:#f3f4f6 }
        .acc-order, .acc-btn-ghost{ background:transparent; border-color:#374151 }
        .acc-btn-ghost{ color:#d1d5db }
    }
</style>
@endpush
