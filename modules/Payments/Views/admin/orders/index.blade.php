@extends('layouts.admin')

@section('title', __('admin.orders.title'))

@section('content')
@php
    $freshIds = $freshIds ?? [];

    // Набор статусов — из модели, одним источником с валидацией.
    $statuses = \Modules\Payments\Models\Order::statusLabels();

    $filtered = request()->hasAny(['q', 'status', 'from', 'to']);
@endphp

{{-- ── Шапка раздела ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-box"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.orders.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.orders.subtitle') }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('admin.orders.export', request()->query()) }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-file-csv"></i> {{ __('admin.orders.export') }}
        </a>

        {{-- Заказ по телефону — обычное дело, и завести его из панели раньше
             было нечем: форма и маршрут отсутствовали вовсе. --}}
        <a href="{{ route('admin.orders.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                  px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-plus"></i> {{ __('admin.orders.create_button') }}
        </a>
    </div>
</div>

{{-- ── Сводка ──
     Тот же приём, что в «Оплате» и «Доставке»: главные числа раздела
     наверху. Считаются по ВСЕЙ выборке с учётом фильтров, а не по
     странице. --}}
<div class="ord-summary mb-4">
    <span class="ord-sum-chip"><i class="fas fa-box"></i> {{ __('admin.orders.sum_total') }}
        <b>{{ $summary['count'] }}</b></span>

    <span class="ord-sum-chip"><i class="fas fa-ruble-sign"></i> {{ __('admin.orders.sum_amount') }}
        <b>{{ number_format($summary['amount'], 2, ',', ' ') }} ₽</b></span>

    @if($summary['pending'])
        <span class="ord-sum-chip is-wait"><i class="fas fa-hourglass-half"></i> {{ __('admin.orders.sum_pending') }}
            <b>{{ $summary['pending'] }}</b></span>
    @endif

    @if($summary['done'])
        <span class="ord-sum-chip is-ok"><i class="fas fa-circle-check"></i> {{ __('admin.orders.sum_done') }}
            <b>{{ $summary['done'] }}</b></span>
    @endif
</div>

{{-- ── Фильтры ── --}}
<form method="GET" action="{{ route('admin.orders.index') }}" class="admin-card p-4 mb-4">
    <h2 class="ord-h2">
        <i class="fas fa-filter text-indigo-500"></i> {{ __('admin.orders.filters') }}
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <label for="q" class="ord-label block text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.orders.f_search') }}
            </label>
            <input type="search" id="q" name="q" value="{{ request('q') }}"
                   placeholder="{{ __('admin.orders.f_search_ph') }}"
                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="status" class="ord-label block text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.orders.f_status') }}
            </label>
            <select id="status" name="status"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">{{ __('admin.orders.f_all') }}</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="from" class="ord-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.orders.f_from') }}
                </label>
                <input type="date" id="from" name="from" value="{{ request('from') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-2 text-sm">
            </div>
            <div>
                <label for="to" class="ord-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.orders.f_to') }}
                </label>
                <input type="date" id="to" name="to" value="{{ request('to') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mt-3">
        <button type="submit"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-magnifying-glass"></i> {{ __('admin.orders.apply') }}
        </button>

        @if($filtered)
            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-xmark"></i> {{ __('admin.orders.reset') }}
            </a>
        @endif

        <span class="ord-count ml-auto">
            {{ __('admin.orders.shown', ['shown' => $orders->count(), 'total' => $orders->total()]) }}
        </span>
    </div>
</form>

@forelse($orders as $order)
    @php
        $tone = match ($order->status) {
            'completed', 'paid' => 'ok',
            'new' => 'wait',
            'cancelled', 'canceled' => 'bad',
            default => 'wait',
        };
        $label = $statuses[$order->status] ?? __('admin.orders.st_unknown');
    @endphp

    <a href="{{ route('admin.orders.show', $order->id) }}" class="ord-row">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <strong class="ord-no">#{{ $order->id }}</strong>

                <span class="ord-status ord-status--{{ $tone }}">{{ $label }}</span>

                @if(in_array($order->id, $freshIds, true))
                    <span class="ord-status ord-status--new">{{ __('admin.orders.is_new') }}</span>
                @endif
            </div>

            <div class="ord-meta flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5">
                <span><i class="fas fa-user"></i>
                    {{ $order->customer_name ?: ($order->user->name ?? __('admin.orders.guest')) }}</span>

                @if($order->customer_phone)
                    <span><i class="fas fa-phone"></i> {{ $order->customer_phone }}</span>
                @endif

                @php
                    $count = $order->items->sum('qty');
                    // Знак и фирменный цвет — из тех же моделей, что в
                    // разделах «Оплата» и «Доставка» и в корзине.
                    $payBrand = $order->paymentMethod?->brand();
                    $shipBrand = $order->deliveryMethod?->brand();
                @endphp

                <span><i class="fas fa-cube"></i>
                    {{ $count > 0 ? $count . ' ' . __('admin.orders.items') : '—' }}</span>

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
                @endif

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
                    </span>
                @endif

                <span><i class="fas fa-clock"></i> {{ $order->created_at->format('d.m.Y H:i') }}</span>
            </div>
        </div>

        <div class="ord-sum">
            <span class="ord-sum__label">{{ __('admin.orders.th_sum') }}</span>
            <b>{{ number_format((float) $order->total, 2, ',', ' ') }} ₽</b>
        </div>
    </a>
@empty
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-box-open"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
            {{ $filtered ? __('admin.orders.empty_filtered') : __('admin.orders.empty') }}
        </h2>
        <p class="admin-hint max-w-xl mx-auto">
            {{ $filtered ? __('admin.orders.empty_filtered_hint') : __('admin.orders.empty_hint') }}
        </p>
    </div>
@endforelse

{{-- Общий компонент прячется сам, когда страница одна: без своей
     сводки список выглядит обрезанным. Та же ловушка уже была в
     «Медиатеке» и в кабинете покупателя. --}}
@if($orders->hasPages())
    <div class="mt-4">{{ $orders->links() }}</div>
@elseif($orders->total() > 0)
    <p class="ord-count mt-3 text-center">
        {{ __('admin.orders.shown', ['shown' => $orders->count(), 'total' => $orders->total()]) }}
    </p>
@endif
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни части палитры, нужной для бейджей статуса. */
    /* Сводка над списком. */
    .ord-summary{ display:flex; flex-wrap:wrap; gap:.5rem }
    .ord-sum-chip{ display:inline-flex; align-items:center; gap:.45rem;
        padding:.4rem .7rem; font-size:.8rem; color:#4b5563;
        background:#f9fafb; border:1px solid #e5e7eb }
    .ord-sum-chip i{ color:#9ca3af }
    .ord-sum-chip b{ color:#111827; font-variant-numeric:tabular-nums }
    .ord-sum-chip.is-ok i{ color:#16a34a }
    .ord-sum-chip.is-wait{ color:#92400e; background:#fffbeb; border-color:#f0d9a8 }
    .ord-sum-chip.is-wait i, .ord-sum-chip.is-wait b{ color:#b45309 }
    .dark .ord-sum-chip{ color:#d1d5db; background:#111827; border-color:#374151 }
    .dark .ord-sum-chip b{ color:#f3f4f6 }

    /* Подписи полей фильтра — моноширинным капсом, как в «Оплате» и
       «Доставке». */
    .ord-label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase }

    .ord-row{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
              gap:1rem; border:1px solid var(--surface-bd,#eef2f7); background:var(--surface,#fff);
              padding:.85rem 1rem; margin-bottom:.5rem; transition:border-color .15s, box-shadow .15s }
    .ord-row:hover{ border-color:#c7d2fe; box-shadow:0 4px 14px rgba(15,23,42,.06) }

    .ord-no{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.95rem; font-weight:700; color:var(--surface-ink,#111827);
        font-variant-numeric:tabular-nums }

    /* Способ оплаты и доставки — со знаком системы. */
    .ord-way{ display:inline-flex; align-items:center; gap:.35rem }
    .ord-way__mark{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:1.35rem; height:1.35rem; overflow:hidden; font-size:.6rem;
        color:var(--pm-ink,#fff); background:var(--pm,#6366f1) }
    .ord-way__mark.has-logo{ background:#fff; border:1px solid color-mix(in srgb, var(--pm) 35%, transparent) }
    .ord-way__mark img{ width:100%; height:100%; object-fit:cover; display:block }

    .ord-sum{ text-align:right; white-space:nowrap }
    /* Подписи выведены из переменных панели, а не прибиты серым: при
       тёмных темах #9ca3af на тёмной подложке давал контраст около 2. */
    .ord-h2{ display:flex; align-items:center; gap:.4rem; margin-bottom:.75rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.7rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .ord-count{ font-size:.72rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 68%, var(--surface,#fff)) }
    .ord-meta{ font-size:.72rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 70%, var(--surface,#fff)) }
    .ord-sum__label{ display:block; font-size:.7rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .ord-sum b{ font-size:1.05rem; color:var(--surface-ink,#111827);
        font-variant-numeric:tabular-nums }

    .ord-status{ font-size:.72rem; font-weight:700; padding:.15rem .5rem; border:1px solid }
    .ord-status--ok{ color:#166534; background:#f0fdf4; border-color:#bbf7d0 }
    .ord-status--wait{ color:#92400e; background:#fffbeb; border-color:#fde68a }
    .ord-status--bad{ color:#991b1b; background:#fef2f2; border-color:#fecaca }
    .ord-status--new{ color:#3730a3; background:#eef2ff; border-color:#c7d2fe }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) — это
       настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не оформление панели. При тёмной
       системе и светлой панели он перекрашивал текст в почти белый на
       белом фоне: сумма заказа пропадала совсем. Тему панели задают класс
       .dark и переменные --admin-*; перекрытие по настройке ОС их только
       ломало. */
</style>
@endpush
