@extends('layouts.frontend')

@section('title', __('frontend.account.my_orders'))

@section('content')
{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  📋 МОИ ЗАКАЗЫ                                                   ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Раньше здесь была таблица на семь колонок с горизонтальной      ║
    ║  прокруткой на телефоне. Теперь карточки: номер и дата в шапке,  ║
    ║  ниже — сумма, состав, оплата и доставка со ЗНАКАМИ систем.      ║
    ║                                                                  ║
    ║  Знак и фирменный цвет берутся из brand() тех же моделей, что и  ║
    ║  в панели и в корзине — одно определение на весь проект.         ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    use Modules\Payments\Models\Order;

    // Подписи статусов строятся по каноническому набору из модели, а
    // тексты берутся из словаря покупателя: в панели те же статусы
    // называются иначе («Выполнен» против «Заказ выполнен»).
    $statusLabels = [];
    foreach (Order::STATUSES as $status) {
        $key = 'frontend.account.st_' . $status;
        $label = __($key);
        $statusLabels[$status] = $label === $key ? __('frontend.account.st_unknown') : $label;
    }
@endphp

<section class="ord">
    <header class="ord__head">
        <span class="ord__eyebrow">{{ __('frontend.account.eyebrow') }}</span>
        <h1 class="ord__title">{{ __('frontend.account.my_orders') }}</h1>
        <span class="ord__count">{{ __('frontend.account.orders_sub', ['count' => $orders->total()]) }}</span>
    </header>

    @if ($orders->count())
        <div class="ord-list">
            @foreach ($orders as $order)
                @php
                    $tone = match ($order->status) {
                        'completed', 'paid' => 'ok',
                        'cancelled', 'canceled' => 'bad',
                        default => 'wait',
                    };

                    // Состав заказа: у старых записей строк товаров может не
                    // быть вовсе — тогда честнее прочерк, а не «0».
                    $count = $order->qty ?? $order->items->sum('qty');

                    $payment = $order->paymentMethod?->brand();
                    $delivery = $order->deliveryMethod?->brand();
                @endphp

                <article class="ord-card">
                    <div class="ord-card__head">
                        <span class="ord-card__no">#{{ $order->id }}</span>
                        <span class="ord-card__date">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                        <span class="ord-status ord-status--{{ $tone }}">
                            {{ $statusLabels[$order->status] ?? __('frontend.account.st_unknown') }}
                        </span>
                    </div>

                    <div class="ord-card__body">
                        <div class="ord-fact">
                            <span class="ord-fact__label">{{ __('frontend.account.amount') }}</span>
                            <b class="ord-fact__sum">{{ number_format($order->total, 2, ',', ' ') }} ₽</b>
                        </div>

                        <div class="ord-fact">
                            <span class="ord-fact__label">{{ __('frontend.account.quantity') }}</span>
                            <b>{{ $count > 0 ? $count : '—' }}</b>
                        </div>

                        {{-- Знак платёжной системы вместо голого названия:
                             покупатель узнаёт его быстрее, чем читает. --}}
                        <div class="ord-fact">
                            <span class="ord-fact__label">{{ __('frontend.account.payment') }}</span>

                            @if ($payment)
                                <span class="ord-way" style="--pm:{{ $payment['color'] }}; --pm-ink:{{ $payment['ink'] }}">
                                    <span class="ord-way__mark {{ $payment['logo'] ? 'has-logo' : '' }}">
                                        @if($payment['logo'])
                                            <img src="{{ $payment['logo'] }}" alt="{{ $order->paymentMethod->title }}" loading="lazy">
                                        @else
                                            <i class="fas {{ $payment['icon'] }}"></i>
                                        @endif
                                    </span>
                                    <b>{{ $order->paymentMethod->title }}</b>
                                </span>
                            @else
                                <b class="ord-none">—</b>
                            @endif
                        </div>

                        <div class="ord-fact">
                            <span class="ord-fact__label">{{ __('frontend.account.delivery') }}</span>

                            @if ($delivery)
                                <span class="ord-way" style="--pm:{{ $delivery['color'] }}; --pm-ink:{{ $delivery['ink'] }}">
                                    <span class="ord-way__mark {{ $delivery['logo'] ? 'has-logo' : '' }}">
                                        @if($delivery['logo'])
                                            <img src="{{ $delivery['logo'] }}" alt="{{ $order->deliveryMethod->title }}" loading="lazy">
                                        @else
                                            <i class="fas {{ $delivery['icon'] }}"></i>
                                        @endif
                                    </span>
                                    <b>{{ $order->deliveryMethod->title }}</b>
                                    <span class="ord-way__price">{{ $order->deliveryMethod->formatted_price }}</span>
                                </span>
                            @else
                                <b class="ord-none">—</b>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Общий компонент постраничного вывода — тот же, что во всех
             списках проекта. Он прячется сам, когда страница одна, поэтому
             сводка выводится отдельно: иначе на одной странице покупатель
             не видит вообще ничего и решает, что список обрезан. --}}
        <div class="ord-foot">
            @unless ($orders->hasPages())
                <p class="ord-count">
                    {{ __('frontend.account.orders_showing', [
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                        'total' => $orders->total(),
                    ]) }}
                </p>
            @endunless

            {{ $orders->appends(request()->query())->links() }}
        </div>
    @else
        <div class="ord-empty">
            <i class="fas fa-receipt"></i>
            <p class="ord-empty__title">{{ __('frontend.account.orders_empty') }}</p>
            <p class="ord-empty__hint">{{ __('frontend.account.orders_empty_hint') }}</p>
            <a href="{{ url('/') }}" class="ord-empty__btn">
                {{ __('frontend.cart.to_catalogue') }}
            </a>
        </div>
    @endif
</section>
@endsection

@push('styles')
<style>
    /* ── Мои заказы ───────────────────────────────────────────────────
       Литеральный CSS: в сборке проекта нет ни прозрачности через дробь,
       ни произвольных значений. Цвета — из активной темы, поэтому
       страница следует оформлению сайта, а не настройке ОС. */

    .ord{ max-width:64rem; margin:2.5rem auto; padding:0 1rem }

    /* Подложка обязательна: у тем есть фоновая картинка, и текст без
       плашки на ней читается через силу. Всё в ОДНУ строку: столбиком
       шапка занимала три строки ради одного заголовка. */
    .ord__head{ display:inline-flex; flex-wrap:wrap; align-items:baseline; gap:.6rem;
        margin-bottom:1rem; padding:.5rem .9rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        box-shadow:0 2px 12px rgba(15,23,42,.06) }
    .ord__eyebrow{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.62rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 72%, var(--surface-ink,#111827)) }
    .ord__title{ margin:0; font-size:1.45rem; font-weight:800;
        letter-spacing:-.02em; line-height:1.15; color:var(--surface-ink,#111827) }
    /* Разделитель между надзаголовком и названием — чертой, а не
       переносом строки. */
    .ord__title::before{ content:''; display:inline-block; width:1px; height:.95em;
        margin-right:.6rem; vertical-align:-.1em;
        background:var(--surface-bd,#e2e8f0) }
    .ord__count{ font-size:.78rem; color:var(--surface-mute,#6b7280);
        font-variant-numeric:tabular-nums }

    .ord-list{ display:grid; gap:.6rem }

    .ord-card{ background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        transition:border-color .15s, box-shadow .15s }
    .ord-card:hover{ border-color:color-mix(in srgb, var(--color-primary,#6366f1) 45%, transparent);
        box-shadow:0 6px 18px rgba(15,23,42,.07) }

    .ord-card__head{ display:flex; flex-wrap:wrap; align-items:center; gap:.6rem;
        padding:.7rem .9rem; border-bottom:1px solid var(--surface-bd,#eef2f7) }
    .ord-card__no{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.9rem; font-weight:700; color:var(--surface-ink,#111827);
        font-variant-numeric:tabular-nums }
    .ord-card__date{ font-size:.76rem; color:var(--surface-mute,#64748b) }
    .ord-card__head .ord-status{ margin-left:auto }

    /* Четыре факта в ряд; на узком экране — по два, потом в столбец. */
    .ord-card__body{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr));
        gap:.75rem; padding:.8rem .9rem }
    .ord-fact{ display:flex; flex-direction:column; gap:.2rem; min-width:0 }
    .ord-fact__label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.6rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:var(--surface-mute,#94a3b8) }
    .ord-fact b{ font-size:.88rem; color:var(--surface-ink,#111827) }
    .ord-fact__sum{ font-variant-numeric:tabular-nums }
    .ord-none{ color:var(--surface-mute,#94a3b8) }

    /* Способ оплаты и доставки — со знаком системы. Фирменный цвет
       приходит переменной со стороны разметки: способов столько же,
       сколько цветов. */
    .ord-way{ display:flex; align-items:center; gap:.4rem; min-width:0 }
    .ord-way b{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .ord-way__mark{ display:flex; align-items:center; justify-content:center; flex:none;
        width:1.6rem; height:1.6rem; overflow:hidden; font-size:.7rem;
        color:var(--pm-ink,#fff); background:var(--pm,#6366f1) }
    .ord-way__mark.has-logo{ background:#fff; border:1px solid color-mix(in srgb, var(--pm) 35%, transparent) }
    .ord-way__mark img{ width:100%; height:100%; object-fit:cover; display:block }
    .ord-way__price{ font-size:.72rem; white-space:nowrap; color:var(--surface-mute,#64748b);
        font-variant-numeric:tabular-nums }

    .ord-status{ display:inline-block; padding:.15rem .5rem; font-size:.72rem; font-weight:700;
                 white-space:nowrap; border:1px solid }
    .ord-status--ok{ color:#15803d; background:#dcfce7; border-color:#86efac }
    .ord-status--bad{ color:#b91c1c; background:#fee2e2; border-color:#fca5a5 }
    .ord-status--wait{ color:#92400e; background:#fef3c7; border-color:#fcd34d }

    .ord-foot{ display:flex; flex-direction:column; align-items:center; gap:.5rem; margin-top:1rem }
    .ord-count{ margin:0; font-size:.78rem; color:var(--surface-mute,#64748b) }

    .ord-empty{ padding:3rem 1rem; text-align:center;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7) }
    .ord-empty i{ display:block; margin-bottom:1rem; font-size:2.25rem;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 45%, var(--surface,#fff)) }
    .ord-empty__title{ margin:0; font-size:1.05rem; font-weight:700; color:var(--surface-ink,#111827) }
    .ord-empty__hint{ margin:.35rem 0 1rem; font-size:.85rem; color:var(--surface-mute,#64748b) }
    .ord-empty__btn{ display:inline-flex; align-items:center; gap:.5rem; padding:.7rem 1.4rem;
        font-size:.9rem; font-weight:700; color:var(--on-accent,#fff);
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6));
        transition:filter .15s }
    .ord-empty__btn:hover{ filter:brightness(1.08); color:#fff }

    @media (max-width: 760px){
        .ord-card__body{ grid-template-columns:repeat(2, minmax(0,1fr)) }
    }
    @media (max-width: 420px){
        .ord-card__body{ grid-template-columns:1fr }
    }
</style>
@endpush
