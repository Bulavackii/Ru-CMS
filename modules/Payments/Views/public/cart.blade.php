@extends('layouts.frontend')

@section('title', __('frontend.cart.title'))

@section('content')
{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🛒 КОРЗИНА                                                      ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Слева товары, справа оформление: способ оплаты, доставка, итог.  ║
    ║                                                                  ║
    ║  СПОСОБЫ ОПЛАТЫ И ДОСТАВКИ                                       ║
    ║    Выпадающие списки. Подробности выбранного варианта — под       ║
    ║    списком: описание, срок, комиссия. Список берётся из разделов  ║
    ║    «Оплата» и «Доставка» панели, показываются только включённые.  ║
    ║                                                                  ║
    ║  ИТОГ                                                            ║
    ║    Сумма товаров считается на СЕРВЕРЕ и приходит в разметке —     ║
    ║    она видна, даже если скрипт не отработал. Доставку и комиссию  ║
    ║    добавляет скрипт при выборе способа.                           ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    $goodsTotal = collect($cart)->sum(fn ($item) => $item['qty'] * $item['price']);
@endphp

<section class="crt">
    <div class="crt__head">
        <span class="fx-badge"><i class="fas fa-cart-shopping"></i></span>
        <div>
            <h1 class="crt__title">{{ __('frontend.cart.title') }}</h1>
            <p class="crt__sub">{{ __('frontend.cart.subtitle', ['count' => count($cart)]) }}</p>
        </div>
    </div>

    @if (count($cart))
        <form action="{{ route('cart.checkout') }}" method="POST" class="crt-grid">
            @csrf

            {{-- ── Товары ── --}}
            <div class="crt-items">
                @foreach ($cart as $item)
                    @php $subtotal = $item['qty'] * $item['price']; @endphp

                    <article class="crt-item">
                        <div class="crt-item__main">
                            <h2 class="crt-item__title">{{ $item['title'] }}</h2>
                            <p class="crt-item__price">
                                <span class="price">{{ number_format($item['price'], 2, ',', ' ') }}</span> ₽
                                <span class="crt-item__per">{{ __('frontend.cart.per_item') }}</span>
                            </p>
                        </div>

                        <div class="crt-item__qty">
                            <button type="button" class="crt-qty__btn decrement" data-id="{{ $item['id'] }}"
                                    aria-label="{{ __('frontend.products.less') }}">−</button>
                            {{-- id обязателен: обработчики +/- ищут поле как #cart-qty-{id}. --}}
                            <input type="text" readonly value="{{ $item['qty'] }}" id="cart-qty-{{ $item['id'] }}"
                                   class="crt-qty__input qty-input" data-id="{{ $item['id'] }}"
                                   aria-label="{{ __('frontend.products.qty') }}">
                            <button type="button" class="crt-qty__btn increment" data-id="{{ $item['id'] }}"
                                    aria-label="{{ __('frontend.products.more') }}">+</button>
                        </div>

                        <div class="crt-item__sum">
                            <span class="subtotal">{{ number_format($subtotal, 2, ',', ' ') }}</span> ₽
                        </div>

                        {{-- formnovalidate обязателен: кнопка лежит внутри формы
                             оформления, где способ оплаты и доставки помечены
                             required. Без него браузер требовал выбрать их,
                             чтобы... удалить товар из корзины. --}}
                        <button formnovalidate formaction="{{ route('cart.remove') }}" formmethod="POST"
                                name="id" value="{{ $item['id'] }}" class="crt-item__del"
                                aria-label="{{ __('frontend.cart.remove') }}"
                                title="{{ __('frontend.cart.remove') }}">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </article>
                @endforeach

                <a href="{{ url('/') }}" class="crt-back">
                    <i class="fas fa-arrow-left"></i> {{ __('frontend.cart.continue') }}
                </a>
            </div>

            {{-- ── Оформление ── --}}
            <aside class="crt-side">
                <div class="crt-box">
                    <h2 class="crt-box__title">
                        <i class="fas fa-credit-card"></i> {{ __('frontend.cart.payment_method') }}
                    </h2>

                    @if ($paymentMethods->count())
                        <select name="payment_method_id" id="payment-method" required class="crt-select">
                            <option value="">{{ __('frontend.cart.choose') }}</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}"
                                        data-description="{{ $method->description ?? '' }}"
                                        data-commission="{{ $method->commission ?? 0 }}"
                                        data-commission-text="{{ $method->commission > 0 ? $method->formattedCommission : '' }}">
                                    {{ $method->title }}
                                </option>
                            @endforeach
                        </select>

                        <p id="payment-description" class="crt-hint"></p>
                    @else
                        <p class="crt-hint">{{ __('frontend.cart.no_payment') }}</p>
                    @endif
                </div>

                <div class="crt-box">
                    <h2 class="crt-box__title">
                        <i class="fas fa-truck"></i> {{ __('frontend.cart.delivery_method') }}
                    </h2>

                    @if ($deliveryMethods->count())
                        <select name="delivery_method_id" id="delivery-method" required class="crt-select">
                            <option value="">{{ __('frontend.cart.choose') }}</option>
                            @foreach ($deliveryMethods as $method)
                                <option value="{{ $method->id }}"
                                        data-price="{{ $method->price }}"
                                        data-days="{{ $method->delivery_days }}"
                                        data-description="{{ $method->description ?? '' }}"
                                        data-free-from="{{ $method->free_delivery_threshold ?? 0 }}">
                                    {{ $method->title }} — {{ $method->formatted_price }}
                                </option>
                            @endforeach
                        </select>

                        <p id="delivery-description" class="crt-hint"></p>
                    @else
                        <p class="crt-hint">{{ __('frontend.cart.no_delivery') }}</p>
                    @endif
                </div>

                {{-- ── Итог ── --}}
                <div class="crt-box crt-total">
                    <div class="crt-total__row">
                        <span>{{ __('frontend.cart.goods') }}</span>
                        <b><span id="cart-total">{{ number_format($goodsTotal, 2, ',', ' ') }}</span> ₽</b>
                    </div>

                    <div class="crt-total__row">
                        <span>{{ __('frontend.cart.delivery') }}</span>
                        <b><span id="delivery-cost">0,00</span> ₽</b>
                    </div>

                    <div class="crt-total__row hidden" id="commission-row">
                        <span>{{ __('frontend.cart.fee') }}</span>
                        <b><span id="commission-cost">0,00</span> ₽</b>
                    </div>

                    <div class="crt-total__grand">
                        <span>{{ __('frontend.cart.total') }}</span>
                        <b><span id="grand-total">{{ number_format($goodsTotal, 2, ',', ' ') }}</span> ₽</b>
                    </div>

                    <button type="submit" class="crt-submit">
                        <i class="fas fa-check"></i> {{ __('frontend.cart.checkout') }}
                    </button>

                    <p class="crt-note">{{ __('frontend.cart.note') }}</p>
                </div>
            </aside>
        </form>
    @else
        <div class="crt-empty">
            <i class="fas fa-cart-shopping"></i>
            <p class="crt-empty__title">{{ __('frontend.cart.empty') }}</p>
            <p class="crt-empty__hint">{{ __('frontend.cart.empty_hint') }}</p>
            <a href="{{ url('/') }}" class="crt-submit crt-submit--inline">
                {{ __('frontend.cart.to_catalogue') }}
            </a>
        </div>
    @endif
</section>

<div id="toast-container" class="crt-toasts"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentSelect  = document.getElementById('payment-method');
    const deliverySelect = document.getElementById('delivery-method');
    const deliveryCost   = document.getElementById('delivery-cost');
    const commissionCost = document.getElementById('commission-cost');
    const commissionRow  = document.getElementById('commission-row');
    const grandTotal     = document.getElementById('grand-total');
    const cartTotal      = document.getElementById('cart-total');

    const money = (n) => n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(message, type) {
        const el = document.createElement('div');
        el.className = 'crt-toast crt-toast--' + (type || 'success');
        el.textContent = message;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(function () { el.remove(); }, 2600);
    }

    // Сумма товаров считается по карточкам: число совпадает с тем, что
    // видит покупатель, даже пока запрос к серверу ещё в пути.
    function goodsTotal() {
        let total = 0;

        document.querySelectorAll('.crt-item').forEach(function (item) {
            const priceEl = item.querySelector('.price');
            const qtyEl = item.querySelector('.qty-input');
            if (!priceEl || !qtyEl) return;

            const price = parseFloat(priceEl.textContent.replace(/\s/g, '').replace(',', '.')) || 0;
            const qty = parseInt(qtyEl.value) || 0;
            const subtotal = price * qty;

            item.querySelector('.subtotal').textContent = money(subtotal);
            total += subtotal;
        });

        return total;
    }

    function recalc() {
        const goods = goodsTotal();
        if (cartTotal) cartTotal.textContent = money(goods);

        // Доставка: бесплатна, если сумма перевалила порог у выбранного способа.
        let delivery = 0;
        const dOpt = deliverySelect ? deliverySelect.selectedOptions[0] : null;

        if (dOpt && dOpt.value) {
            delivery = parseFloat(dOpt.dataset.price || 0) || 0;
            const freeFrom = parseFloat(dOpt.dataset.freeFrom || 0) || 0;
            if (freeFrom > 0 && goods >= freeFrom) delivery = 0;
        }

        if (deliveryCost) deliveryCost.textContent = money(delivery);

        // Комиссия — процент от суммы товаров.
        let fee = 0;
        const pOpt = paymentSelect ? paymentSelect.selectedOptions[0] : null;

        if (pOpt && pOpt.value) {
            const percent = parseFloat(pOpt.dataset.commission || 0) || 0;
            if (percent > 0) fee = goods * percent / 100;
        }

        if (commissionCost) commissionCost.textContent = money(fee);
        if (commissionRow) commissionRow.classList.toggle('hidden', fee <= 0);

        if (grandTotal) grandTotal.textContent = money(goods + delivery + fee);
    }

    function describe(select, targetId, build) {
        const target = document.getElementById(targetId);
        if (!select || !target) return;

        const opt = select.selectedOptions[0];
        target.textContent = opt && opt.value ? build(opt) : '';
    }

    if (paymentSelect) {
        paymentSelect.addEventListener('change', function () {
            describe(paymentSelect, 'payment-description', function (o) {
                return [o.dataset.description, o.dataset.commissionText].filter(Boolean).join(' · ');
            });
            recalc();
        });
    }

    if (deliverySelect) {
        deliverySelect.addEventListener('change', function () {
            describe(deliverySelect, 'delivery-description', function (o) {
                return [o.dataset.description, o.dataset.days].filter(Boolean).join(' · ');
            });
            recalc();
        });
    }

    // ── Количество ──
    function changeQty(id, delta) {
        const input = document.getElementById('cart-qty-' + id);
        if (!input) return;

        const next = (parseInt(input.value) || 1) + delta;
        if (next < 1) return;

        input.value = next;
        recalc();

        fetch(@js(route('cart.update')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
            body: JSON.stringify({ id: id, qty: next })
        }).then(function (res) {
            if (!res.ok) throw res;
            return res.json();
        }).catch(function (error) {
            // Сервер отказал — возвращаем прежнее значение, иначе на экране
            // осталось бы количество, которого в корзине нет.
            input.value = next - delta;
            recalc();

            const fallback = @js(__('frontend.cart.update_error'));

            if (error && typeof error.json === 'function') {
                error.json().then(function (e) { toast(e.message || fallback, 'error'); })
                    .catch(function () { toast(fallback, 'error'); });
            } else {
                toast(fallback, 'error');
            }
        });
    }

    document.querySelectorAll('.increment').forEach(function (b) {
        b.addEventListener('click', function () { changeQty(b.dataset.id, 1); });
    });

    document.querySelectorAll('.decrement').forEach(function (b) {
        b.addEventListener('click', function () { changeQty(b.dataset.id, -1); });
    });

    recalc();
});
</script>
@endpush

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. Цвета — из активной темы. */
    .crt{ max-width:76rem; margin:2.5rem auto; padding:0 1rem }

    .crt__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:#fff; border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .crt__title{ margin:0; font-size:1.5rem; font-weight:700; color:#111827; line-height:1.2 }
    .crt__sub{ margin:.1rem 0 0; font-size:.82rem; color:#6b7280 }

    .crt-grid{ display:grid; grid-template-columns:minmax(0,1fr) 21rem; gap:1rem; align-items:start }

    /* ── Товары ── */
    .crt-items{ display:grid; gap:.5rem; align-content:start }
    .crt-item{ display:grid; grid-template-columns:minmax(0,1fr) auto auto auto;
        align-items:center; gap:1rem; padding:1rem 1.1rem;
        background:#fff; border:1px solid #eef2f7 }
    .crt-item__title{ margin:0; font-size:1rem; font-weight:700; color:#111827; line-height:1.3 }
    .crt-item__price{ margin:.15rem 0 0; font-size:.82rem; color:#64748b }
    .crt-item__per{ opacity:.7 }

    .crt-item__qty{ display:flex; align-items:stretch; border:1px solid #e2e8f0 }
    .crt-qty__btn{ width:2rem; background:#f8fafc; color:#334155; font-size:1rem; font-weight:700;
        border:0; cursor:pointer; line-height:1 }
    .crt-qty__btn:hover{ background:#eef2ff; color:var(--color-primary,#6366f1) }
    .crt-qty__input{ width:2.6rem; text-align:center; border:0; border-left:1px solid #e2e8f0;
        border-right:1px solid #e2e8f0; font-size:.85rem; color:#111827; background:#fff }

    .crt-item__sum{ font-size:1.05rem; font-weight:800; color:#111827; white-space:nowrap;
        min-width:7rem; text-align:right }

    .crt-item__del{ width:2.2rem; height:2.2rem; display:inline-flex; align-items:center;
        justify-content:center; color:#cbd5e1; background:transparent; border:0; cursor:pointer }
    .crt-item__del:hover{ color:#dc2626 }

    .crt-back{ display:inline-flex; align-items:center; gap:.45rem; margin-top:.35rem;
        font-size:.85rem; font-weight:600; color:var(--color-primary,#6366f1) }

    /* ── Оформление ── */
    .crt-side{ display:grid; gap:.75rem; position:sticky; top:1rem }
    .crt-box{ padding:1.1rem 1.2rem; background:#fff; border:1px solid #eef2f7 }
    .crt-box__title{ display:flex; align-items:center; gap:.5rem; margin:0 0 .75rem;
        font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#9ca3af }
    .crt-box__title i{ color:var(--color-primary,#6366f1); font-size:.85rem }

    .crt-select{ width:100%; padding:.6rem .7rem; font-size:.9rem; color:#111827;
        background:#fff; border:1px solid #e2e8f0 }
    .crt-select:focus{ outline:2px solid var(--color-primary,#6366f1); outline-offset:-1px }
    .crt-hint{ margin:.5rem 0 0; font-size:.78rem; line-height:1.5; color:#64748b }

    .crt-total__row{ display:flex; align-items:baseline; justify-content:space-between; gap:1rem;
        padding:.35rem 0; font-size:.88rem; color:#475569 }
    .crt-total__row b{ color:#111827; white-space:nowrap }
    .crt-total__grand{ display:flex; align-items:baseline; justify-content:space-between; gap:1rem;
        margin-top:.5rem; padding-top:.75rem; border-top:1px solid #eef2f7;
        font-size:.95rem; font-weight:700; color:#111827 }
    .crt-total__grand b{ font-size:1.35rem; white-space:nowrap }

    .crt-submit{ display:flex; align-items:center; justify-content:center; gap:.5rem; width:100%;
        margin-top:1rem; padding:.8rem 1rem; font-size:.95rem; font-weight:700; color:#fff;
        border:0; cursor:pointer;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6));
        transition:filter .15s }
    .crt-submit:hover{ filter:brightness(1.08); color:#fff }
    .crt-submit--inline{ width:auto; display:inline-flex; margin-top:1.25rem; padding:.7rem 1.5rem }

    .crt-note{ margin:.6rem 0 0; font-size:.72rem; line-height:1.45; color:#94a3b8; text-align:center }

    /* ── Пусто ── */
    .crt-empty{ padding:3.5rem 1rem; text-align:center; background:#fff; border:1px solid #eef2f7 }
    .crt-empty i{ font-size:2.5rem; color:#c7d2fe; display:block; margin-bottom:1rem }
    .crt-empty__title{ margin:0; font-size:1.1rem; font-weight:700; color:#111827 }
    .crt-empty__hint{ margin:.35rem 0 0; font-size:.85rem; color:#64748b }

    /* ── Уведомления ── */
    .crt-toasts{ position:fixed; top:1.25rem; right:1.25rem; z-index:60; display:grid; gap:.5rem }
    .crt-toast{ padding:.7rem 1rem; font-size:.85rem; font-weight:600; color:#fff;
        box-shadow:0 8px 24px rgba(15,23,42,.25) }
    .crt-toast--success{ background:#16a34a }
    .crt-toast--error{ background:#dc2626 }

    @media (max-width: 960px){
        .crt-grid{ grid-template-columns:1fr }
        .crt-side{ position:static }
        .crt-item{ grid-template-columns:minmax(0,1fr) auto; row-gap:.75rem }
        .crt-item__sum{ text-align:left; min-width:0 }
    }

    @media (prefers-color-scheme: dark){
        .crt__head, .crt-item, .crt-box, .crt-empty{ background:#111827; border-color:#1f2937 }
        .crt__title, .crt-item__title, .crt-item__sum, .crt-total__row b,
        .crt-total__grand, .crt-empty__title{ color:#f3f4f6 }
        .crt-select, .crt-qty__input{ background:#111827; color:#f3f4f6; border-color:#1f2937 }
        .crt-qty__btn{ background:#1f2937; color:#cbd5e1 }
        .crt-total__grand{ border-color:#1f2937 }
    }
</style>
@endpush
