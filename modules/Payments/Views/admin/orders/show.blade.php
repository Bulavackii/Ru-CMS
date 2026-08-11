@extends('layouts.admin')

@section('title', __('admin.orders.card_title') . ' #' . $order->id)

@section('content')
@php
    // Набор статусов — из модели, одним источником с валидацией.
    $statuses = \Modules\Payments\Models\Order::statusLabels();

    $label = $statuses[$order->status] ?? __('admin.orders.st_unknown');
    $tone = match ($order->status) {
        'completed', 'paid' => 'ok',
        'cancelled', 'canceled' => 'bad',
        default => 'wait',
    };
@endphp

{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-box"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ __('admin.orders.card_title') }} #{{ $order->id }}
                <span class="ord-status ord-status--{{ $tone }} align-middle ml-1">{{ $label }}</span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.orders.f_created') }}: {{ $order->created_at->format('d.m.Y H:i') }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
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

            <div><dt>{{ __('admin.orders.c_address') }}</dt>
                <dd>{{ $order->customer_address ?: __('admin.orders.c_none') }}</dd></div>

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

        <dl class="ord-list">
            <div><dt>{{ __('admin.orders.f_payment') }}</dt>
                <dd>{{ $order->paymentMethod->title ?? '—' }}</dd></div>

            <div><dt>{{ __('admin.orders.f_delivery') }}</dt>
                <dd>{{ $order->deliveryMethod->title ?? '—' }}
                    @if($order->delivery_price > 0)
                        <span class="text-gray-500">({{ number_format((float) $order->delivery_price, 2, ',', ' ') }} ₽)</span>
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
              class="ord-print flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
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

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <th class="py-2 pr-3 font-semibold">{{ __('admin.orders.t_product') }}</th>
                    <th class="py-2 px-3 font-semibold text-right">{{ __('admin.orders.t_price') }}</th>
                    <th class="py-2 px-3 font-semibold text-right">{{ __('admin.orders.t_qty') }}</th>
                    <th class="py-2 pl-3 font-semibold text-right">{{ __('admin.orders.t_sum') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 pr-3 text-gray-900 dark:text-white">{{ $item->title }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format((float) $item->price, 2, ',', ' ') }} ₽</td>
                        <td class="py-2 px-3 text-right">{{ $item->qty }}</td>
                        <td class="py-2 pl-3 text-right font-semibold text-gray-900 dark:text-white">
                            {{ number_format((float) $item->price * $item->qty, 2, ',', ' ') }} ₽
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="py-3 pr-3 text-right font-semibold">{{ __('admin.orders.total') }}</td>
                    <td class="py-3 pl-3 text-right ord-total">
                        {{ number_format((float) $order->total, 2, ',', ' ') }} ₽
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: динамических классов bg-{$color}-100, на которых
       держался прежний бейдж статуса, в статической сборке Tailwind нет —
       он выводился бесцветным. */
    .ord-list{ display:grid; gap:.5rem; font-size:.92rem }
    .ord-list > div{ display:flex; gap:.6rem; flex-wrap:wrap; align-items:baseline }
    .ord-list dt{ color:#6b7280; min-width:11rem }
    .ord-list dd{ margin:0; font-weight:600; color:#111827 }
    .ord-list a{ color:#4f46e5 }
    .ord-total{ font-size:1.05rem; font-weight:700; color:#111827 }

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
