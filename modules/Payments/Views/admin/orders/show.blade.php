@extends('layouts.admin')

@section('title', __('admin.orders.card_title') . ' #' . $order->id)

@section('content')
@php
    // Набор статусов — из модели, одним источником с валидацией.
    $statuses = \Modules\Payments\Models\Order::statusLabels();

    $label = $statuses[$order->status] ?? __('admin.orders.st_unknown');
    $tone = match ($order->status) {
        'completed', 'paid' => 'ok',
        'new' => 'wait',
        'cancelled', 'canceled' => 'bad',
        default => 'wait',
    };
@endphp

{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
{{-- Шапка в два ряда (.mh-*, общее определение в лейауте): ряд 1 —
     номер заказа и состояние, ряд 2 — дата и переходы. --}}
<div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
    <div class="mh-row">
        <span class="admin-icon-badge"><i class="fas fa-box"></i></span>

        <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
            {{ __('admin.orders.card_title') }} #{{ $order->id }}
        </h1>

        <span class="mh-status ord-status ord-status--{{ $tone }}">{{ $label }}</span>
    </div>

    <div class="mh-row mh-row--sub">
        <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.orders.f_created') }}: {{ $order->created_at->format('d.m.Y H:i') }}
        </p>

        <div class="mh-back flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="ord-print inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                           hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-print"></i> {{ __('admin.orders.print') }}
            </button>

            <a href="{{ route('admin.orders.index') }}"
               class="ord-print inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-arrow-left"></i> {{ __('admin.orders.back') }}
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- ── Покупатель: без этого блока заказ некуда отправлять ── --}}
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
            <i class="fas fa-user text-indigo-500"></i> {{ __('admin.orders.customer') }}
        </h2>

        <dl class="ord-list">
            <div><dt>{{ __('admin.orders.c_name') }}</dt>
                <dd>{{ $order->customer_name ?: __('admin.orders.c_none') }}</dd></div>

            <div><dt>{{ __('admin.orders.c_phone') }}</dt>
                <dd>@if($order->customer_phone)<a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a>@else{{ __('admin.orders.c_none') }}@endif</dd></div>

            <div><dt>{{ __('admin.orders.c_email') }}</dt>
                <dd>@if($order->customer_email)<a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a>@else{{ __('admin.orders.c_none') }}@endif</dd></div>

            @if($order->customer_city)
                <div><dt>{{ __('admin.orders.c_city') }}</dt>
                    <dd>{{ $order->customer_city }}</dd></div>
            @endif

            <div><dt>{{ __('admin.orders.c_address') }}</dt>
                <dd>{{ $order->customer_address ?: __('admin.orders.c_none') }}</dd></div>

            {{-- Вес показываем, только если его считали: пусто значит «не
                 взвешивали» (заказ из услуг), а не «ноль килограммов». --}}
            @if($order->total_weight !== null)
                <div><dt>{{ __('admin.orders.c_weight') }}</dt>
                    <dd>{{ rtrim(rtrim(number_format((float) $order->total_weight, 3, ',', ' '), '0'), ',') }} {{ __('admin.orders.kg') }}</dd></div>
            @endif

            <div><dt>{{ __('admin.orders.f_user') }}</dt>
                <dd>{{ $order->user->name ?? __('admin.orders.guest') }}</dd></div>

            @if($order->comment)
                <div><dt>{{ __('admin.orders.c_comment') }}</dt><dd>{{ $order->comment }}</dd></div>
            @endif
        </dl>
    </section>

    {{-- ── О заказе ── --}}
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
            <i class="fas fa-receipt text-indigo-500"></i> {{ __('admin.orders.g_order') }}
        </h2>

        @php
            // Знак и фирменный цвет — из тех же моделей, что в списках
            // «Оплата» и «Доставка» и в корзине покупателя.
            $payBrand = $order->paymentMethod?->brand();
            $shipBrand = $order->deliveryMethod?->brand();
        @endphp

        <dl class="ord-list">
            <div><dt>{{ __('admin.orders.f_payment') }}</dt>
                <dd>
                    @if($payBrand)
                        <span class="ord-way" style="--pm:{{ $payBrand['color'] }}; --pm-ink:{{ $payBrand['ink'] }}">
                            <span class="ord-way__mark {{ $payBrand['logo'] ? 'has-logo' : '' }}">
                                @if($payBrand['logo'])
                                    <img src="{{ $payBrand['logo'] }}" alt="{{ $order->paymentMethod->title }}" loading="lazy">
                                @else
                                    <i class="fas {{ $payBrand['icon'] }}"></i>
                                @endif
                            </span>
                            {{ $order->paymentMethod->title }}
                        </span>
                    @else
                        —
                    @endif
                </dd></div>

            <div><dt>{{ __('admin.orders.f_delivery') }}</dt>
                <dd>
                    @if($shipBrand)
                        <span class="ord-way" style="--pm:{{ $shipBrand['color'] }}; --pm-ink:{{ $shipBrand['ink'] }}">
                            <span class="ord-way__mark {{ $shipBrand['logo'] ? 'has-logo' : '' }}">
                                @if($shipBrand['logo'])
                                    <img src="{{ $shipBrand['logo'] }}" alt="{{ $order->deliveryMethod->title }}" loading="lazy">
                                @else
                                    <i class="fas {{ $shipBrand['icon'] }}"></i>
                                @endif
                            </span>
                            {{ $order->deliveryMethod->title }}
                            @if($order->delivery_price > 0)
                                <span class="ord-way__price">{{ number_format((float) $order->delivery_price, 2, ',', ' ') }} ₽</span>
                            @endif
                        </span>
                    @else
                        —
                    @endif
                </dd></div>

            @if($order->commission > 0)
                <div><dt>{{ __('admin.payments.th_commission') }}</dt>
                    <dd>{{ number_format((float) $order->commission, 2, ',', ' ') }} ₽</dd></div>
            @endif

            <div><dt>{{ __('admin.orders.th_sum') }}</dt>
                <dd class="ord-total">{{ number_format((float) $order->total, 2, ',', ' ') }} ₽</dd></div>
        </dl>

        {{-- Смена статуса прямо в карточке: раньше ради этого приходилось
             искать отдельную форму. --}}
        <form action="{{ route('admin.orders.update.status', $order->id) }}" method="POST"
              class="ord-status-form ord-print flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            @csrf
            @method('PUT')

            <label for="status" class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                {{ __('admin.orders.change_status') }}
            </label>

            <select id="status" name="status"
                    class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                @foreach($statuses as $value => $text)
                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $text }}</option>
                @endforeach
            </select>

            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-check"></i> {{ __('admin.payments.save') }}
            </button>
        </form>
    </section>
</div>

{{-- ── Товары ── --}}
<section class="admin-card p-5">
    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
        <i class="fas fa-cart-shopping text-indigo-500"></i> {{ __('admin.orders.g_items') }}
    </h2>

    @if($order->items->isEmpty())
        {{-- У заказа может не быть строк товаров: так создаются заказы,
             оформленные до появления позиций, и тестовые. Пустая шапка
             таблицы с одним «Итого» выглядела поломкой. --}}
        <div class="ord-noitems">
            <i class="fas fa-box-open"></i>
            <p class="ord-noitems__title">{{ __('admin.orders.items_empty') }}</p>
            <p class="admin-hint">{{ __('admin.orders.items_empty_hint') }}</p>
            <p class="ord-noitems__sum">
                {{ __('admin.orders.total') }}
                <b>{{ number_format((float) $order->total, 2, ',', ' ') }} ₽</b>
            </p>
        </div>
    @else
    <div class="overflow-x-auto">
        <table class="ord-items w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <th class="py-2 pr-3 font-semibold">{{ __('admin.orders.t_product') }}</th>
                    <th class="col-narrow py-2 px-3 font-semibold text-right">{{ __('admin.orders.t_price') }}</th>
                    <th class="col-narrow py-2 px-3 font-semibold text-right">{{ __('admin.orders.t_qty') }}</th>
                    <th class="py-2 pl-3 font-semibold text-right">{{ __('admin.orders.t_sum') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 pr-3 text-gray-900 dark:text-white">
                            {{ $item->title }}
                            {{-- На узком экране цена и количество прячутся
                                 отдельными колонками (иначе четыре столбца
                                 не помещаются и таблица едет вбок) и
                                 показываются строкой под названием. --}}
                            <span class="ord-item__mul only-narrow">
                                {{ number_format((float) $item->price, 2, ',', ' ') }} ₽ × {{ $item->qty }}
                            </span>
                        </td>
                        <td class="col-narrow py-2 px-3 text-right">{{ number_format((float) $item->price, 2, ',', ' ') }} ₽</td>
                        <td class="col-narrow py-2 px-3 text-right">{{ $item->qty }}</td>
                        <td class="py-2 pl-3 text-right font-semibold text-gray-900 dark:text-white">
                            {{ number_format((float) $item->price * $item->qty, 2, ',', ' ') }} ₽
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    {{-- colspan остаётся 3: скрытые колонки места в строке
                         не занимают, и подпись всё равно встаёт слева от
                         суммы. --}}
                    <td colspan="3" class="py-3 pr-3 text-right font-semibold">{{ __('admin.orders.total') }}</td>
                    <td class="py-3 pl-3 text-right ord-total">
                        {{ number_format((float) $order->total, 2, ',', ' ') }} ₽
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</section>
@endsection

@push('styles')
<style>
    /* ── Товары в заказе на узком экране ───────────────────────────
       Колонки «Цена» и «Кол-во» скрыты (их заменяет строка под
       названием), но подпись «Итого» объявлена через colspan=3 — и
       таблица продолжала считать четыре колонки, отчего вылезала за
       карточку на 52 пикселя. Строка-сетка решает это разом: скрытые
       ячейки места не занимают, а colspan в сетке роли не играет. */
    /* ── Смена статуса на сенсорных ────────────────────────────────
       Подпись, список и кнопка стояли в ряду с переносом: подпись и
       список делили строку, кнопка уезжала вниз своей ширины — три
       разных края подряд. Ставим их столбиком во всю ширину. */
    @media (max-width: 1024px), (max-height: 500px){
        body .ord-status-form{ display:grid; grid-template-columns:1fr; gap:.5rem }
        body .ord-status-form label{ margin:0 }
        body .ord-status-form select,
        body .ord-status-form button{ width:100%; min-height:44px; justify-content:center }
    }

    @media (max-width: 520px){
        body .ord-items tbody tr,
        body .ord-items tfoot tr{ display:grid; grid-template-columns:minmax(0,1fr) auto;
            align-items:baseline; column-gap:.5rem }
        body .ord-items thead{ display:none }
    }
</style>
@endpush

@push('styles')
<style>
    /* Литеральный CSS: динамических классов bg-{$color}-100, на которых
       держался прежний бейдж статуса, в статической сборке Tailwind нет —
       он выводился бесцветным. */
    /* Типографика как в «Оплате» и «Доставке»: подписи — моноширинным,
       мелко, капсом; суммы — табличными цифрами, иначе колонка «пляшет»
       по разрядам. */
    .ord-list{ display:grid; gap:.55rem; font-size:.92rem }
    .ord-list > div{ display:flex; gap:.6rem; flex-wrap:wrap; align-items:center }
    .ord-list dt{ min-width:11rem; flex:none;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .ord-list dd{ margin:0; font-weight:600; color:var(--surface-ink,#111827) }
    .ord-list a{ color:#4f46e5 }
    .ord-total{ font-size:1.05rem; font-weight:700; color:var(--surface-ink,#111827);
        font-variant-numeric:tabular-nums }

    /* Способ оплаты и доставки — со знаком системы. */
    .ord-way{ display:inline-flex; align-items:center; gap:.45rem }
    .ord-way__mark{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:1.6rem; height:1.6rem; overflow:hidden; font-size:.7rem;
        color:var(--pm-ink,#fff); background:var(--pm,#6366f1) }
    .ord-way__mark.has-logo{ background:#fff; border:1px solid color-mix(in srgb, var(--pm) 35%, transparent) }
    .ord-way__mark img{ width:100%; height:100%; object-fit:cover; display:block }
    .ord-way__price{ font-size:.78rem; font-weight:600;
        color:color-mix(in srgb, var(--surface-ink,#111827) 60%, var(--surface,#fff));
        font-variant-numeric:tabular-nums }

    /* Заказ без строк товаров. */
    .ord-noitems{ padding:1.75rem 1rem; text-align:center }
    .ord-noitems i{ display:block; margin-bottom:.6rem; font-size:1.75rem; color:#c7d2fe }
    .ord-noitems__title{ margin:0 0 .2rem; font-size:.95rem; font-weight:700;
        color:var(--surface-ink,#111827) }
    .ord-noitems__sum{ display:inline-flex; align-items:baseline; gap:.5rem; margin:1rem 0 0;
        padding:.45rem .8rem; background:#f9fafb; border:1px solid #eef2f7;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .ord-noitems__sum b{ font-family:inherit; font-size:1.05rem; letter-spacing:0;
        color:var(--surface-ink,#111827); font-variant-numeric:tabular-nums }

    .ord-status{ display:inline-block; font-size:.72rem; font-weight:700;
                 padding:.15rem .5rem; border:1px solid; vertical-align:middle }
    .ord-status--ok{ color:#166534; background:#f0fdf4; border-color:#bbf7d0 }
    .ord-status--wait{ color:#92400e; background:#fffbeb; border-color:#fde68a }
    .ord-status--bad{ color:#991b1b; background:#fef2f2; border-color:#fecaca }

    /* Печатная форма заказа: убираем всё, что на бумаге не нужно. */
    @media print{
        .ord-print, .admin-accent-bar{ display:none !important }
        .admin-card, .admin-glass{ border:0 !important; box-shadow:none !important }
        a[href]:after{ content:'' }
    }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) — это
       настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не оформление панели. При тёмной
       системе и светлой панели он перекрашивал текст в почти белый на
       белом фоне: сумма заказа пропадала совсем. Тему панели задают класс
       .dark и переменные --admin-*; перекрытие по настройке ОС их только
       ломало. */
</style>
@endpush
