@extends('layouts.frontend')

@section('title', __('frontend.cart.sbp_title'))

@section('content')
{{--
    Оплата по СБП: страница с QR-кодом.

    ⚠️ Вьюхи не существовало, и адрес отдавал 500 — маршрут был объявлен, а
    показывать было нечего.

    ⚠️ Драйвер СБП дописан НЕ до конца: подтверждение оплаты у него читает
    статус из нашей же базы, а не у банка (см. справочник разработчика,
    раздел «Торговля»). Поэтому страница честно говорит, что оплату
    подтвердит менеджер, и не делает вид, что деньги приняты.
--}}
<div class="container mx-auto px-4 py-8">
    <div class="mx-auto" style="max-width: 30rem">
        <div class="fx-card p-6 text-center">
            <span class="fx-badge mx-auto mb-4"><i class="fas fa-qrcode"></i></span>

            <h1 class="fx-section-title mb-1">{{ __('frontend.cart.sbp_title') }}</h1>
            <p class="fx-section-sub mb-5">
                {{ __('frontend.cart.sbp_order', ['number' => $order->id]) }}
            </p>

            @php
                // Ссылка на оплату приходит от банка и хранится у заказа.
                // Пока драйвер не дописан, её может не быть вовсе.
                $ссылка = $order->payment_id ?? null;
            @endphp

            @if ($ссылка)
                {{-- Свой генератор QR: без зависимостей, SVG, не мылится. --}}
                <div class="mx-auto mb-5" style="max-width: 16rem">
                    {!! qr_svg($ссылка) !!}
                </div>

                <p class="text-sm" style="color: var(--surface-mute, #64748b)">
                    {{ __('frontend.cart.sbp_hint') }}
                </p>
            @else
                <div class="pc-note text-left">
                    {{ __('frontend.cart.sbp_not_ready') }}
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2 justify-center">
                <a href="{{ route('cart.confirm', ['id' => $order->id]) }}" class="fx-btn">
                    <i class="fas fa-receipt"></i> {{ __('frontend.cart.sbp_to_order') }}
                </a>
                <a href="{{ url('/') }}" class="fx-chip">
                    <i class="fas fa-house"></i> {{ __('frontend.header.home') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
