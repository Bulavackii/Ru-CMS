@extends('layouts.frontend')

@section('title', __('frontend.cart.title'))

@section('content')
{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🛒 КОРЗИНА                                                      ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Слева три шага: товары, оплата, доставка. Справа — липкий итог. ║
    ║                                                                  ║
    ║  СПОСОБЫ ОПЛАТЫ И ДОСТАВКИ                                       ║
    ║    Карточки с логотипом, названием, пояснением и ценой. Знак и    ║
    ║    фирменный цвет берутся из brand() тех же моделей, что и в      ║
    ║    панели. Показываются только включённые способы.                ║
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
    {{-- Только значок и название. Надзаголовок «Оформление заказа» и
         счётчик товаров отсюда убраны: строкой ниже стоит заголовок раздела
         «01 · Товары в корзине» с тем же числом, то есть счётчик дублировал
         сам себя, а шапка занимала три этажа на первом экране. --}}
    <header class="crt__head">
        <span class="crt__ico" aria-hidden="true"><i class="fas fa-cart-shopping"></i></span>
        {{-- Заголовком стоит «Оформление заказа»: «Ваша корзина» повторяло
             название вкладки, а счётчик — заголовок раздела строкой ниже. --}}
        <h1 class="crt__title">{{ __('frontend.cart.eyebrow') }}</h1>
    </header>

    @if (count($cart))
        <form action="{{ route('cart.checkout') }}" method="POST" class="crt-grid">
            @csrf

            <div class="crt-main">
                {{-- ── 01. Товары ── --}}
                <section class="crt-step">
                    <h2 class="crt-step__title">
                        <span class="crt-step__num">01</span>
                        {{ __('frontend.cart.step_goods') }}
                        <span class="crt-step__count">{{ count($cart) }}</span>
                    </h2>

                    <div class="crt-items">
                        @foreach ($cart as $item)
                            @php $subtotal = $item['qty'] * $item['price']; @endphp

                            <article class="crt-item">
                                <div class="crt-item__main">
                                    <h3 class="crt-item__title">{{ $item['title'] }}</h3>
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
                    </div>

                    <a href="{{ url('/') }}" class="crt-back">
                        <i class="fas fa-arrow-left"></i> {{ __('frontend.cart.continue') }}
                    </a>
                </section>

                {{-- ── 02. Оплата ── --}}
                <section class="crt-step">
                    <h2 class="crt-step__title">
                        <span class="crt-step__num">02</span>
                        {{ __('frontend.cart.payment_method') }}
                    </h2>

                    @if ($paymentMethods->count())
                        {{-- Карточки, а не выпадающий список: в списке покупатель
                             видел одни названия и открывал его ради каждого
                             сравнения. Знак и фирменный цвет берутся из той же
                             карты, что и в панели. --}}
                        <div class="crt-pick" role="radiogroup"
                             aria-label="{{ __('frontend.cart.payment_method') }}">
                            @foreach ($paymentMethods as $method)
                                @php $brand = $method->brand(); @endphp

                                <label class="crt-opt" style="--pm:{{ $brand['color'] }}; --pm-ink:{{ $brand['ink'] }}">
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" required
                                           data-title="{{ $method->title }}"
                                           data-description="{{ $method->description ?? '' }}"
                                           data-commission="{{ $method->commission ?? 0 }}"
                                           data-commission-text="{{ $method->commission > 0 ? $method->formattedCommission : '' }}">

                                    {{-- Знак, а не логотип. Логотипы служб нарисованы под
                                         крупную плитку со своим фоном и в кружке 40 пикселей
                                         превращаются в мутное пятно; знак читается всегда. --}}
                                    <span class="crt-opt__mark">
                                        <i class="fas {{ $brand['icon'] }}"></i>
                                    </span>

                                    <span class="crt-opt__body">
                                        <span class="crt-opt__name">{{ $method->title }}</span>
                                        @if($method->description)
                                            <span class="crt-opt__note">{{ $method->description }}</span>
                                        @endif
                                    </span>

                                    <span class="crt-opt__meta">
                                        {{ $method->commission > 0 ? $method->formattedCommission : __('frontend.cart.no_fee') }}
                                    </span>

                                    <i class="crt-opt__tick fas fa-circle-check" aria-hidden="true"></i>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="crt-hint">{{ __('frontend.cart.no_payment') }}</p>
                    @endif
                </section>

                {{-- ── 03. Доставка ── --}}
                <section class="crt-step">
                    <h2 class="crt-step__title">
                        <span class="crt-step__num">03</span>
                        {{ __('frontend.cart.delivery_method') }}
                    </h2>

                    @if ($deliveryMethods->count())
                        <div class="crt-pick" role="radiogroup"
                             aria-label="{{ __('frontend.cart.delivery_method') }}">
                            @foreach ($deliveryMethods as $method)
                                @php $brand = $method->brand(); @endphp

                                <label class="crt-opt" style="--pm:{{ $brand['color'] }}; --pm-ink:{{ $brand['ink'] }}">
                                    <input type="radio" name="delivery_method_id" value="{{ $method->id }}" required
                                           data-title="{{ $method->title }}"
                                           data-price="{{ $method->price }}"
                                           data-days="{{ $method->delivery_days }}"
                                           data-description="{{ $method->description ?? '' }}"
                                           data-free-from="{{ $method->free_delivery_threshold ?? 0 }}"
                                           {{-- Самовывозу адрес не нужен: покупатель приходит сам. --}}
                                           data-needs-address="{{ $method->type === 'pickup' ? '0' : '1' }}">

                                    {{-- Знак, а не логотип. Логотипы служб нарисованы под
                                         крупную плитку со своим фоном и в кружке 40 пикселей
                                         превращаются в мутное пятно; знак читается всегда. --}}
                                    <span class="crt-opt__mark">
                                        <i class="fas {{ $brand['icon'] }}"></i>
                                    </span>

                                    <span class="crt-opt__body">
                                        <span class="crt-opt__name">{{ $method->title }}</span>
                                        <span class="crt-opt__note">
                                            {{ collect([
                                                $method->delivery_days,
                                                $method->free_delivery_threshold > 0
                                                    ? __('frontend.cart.free_delivery_from', ['sum' => number_format((float) $method->free_delivery_threshold, 0, ',', ' ')])
                                                    : null,
                                                $method->description,
                                            ])->filter()->join(' · ') }}
                                        </span>
                                    </span>

                                    <span class="crt-opt__meta">{{ $method->formatted_price }}</span>

                                    <i class="crt-opt__tick fas fa-circle-check" aria-hidden="true"></i>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="crt-hint">{{ __('frontend.cart.no_delivery') }}</p>
                    @endif
                </section>

                {{-- ── 04. Покупатель ──

                     🔴 Этого шага не было ВООБЩЕ. Форма спрашивала только
                     способ оплаты, способ доставки и согласие — и заказ
                     приходил владельцу без имени, телефона и адреса.
                     То есть магазин принимал заказы, которые физически
                     некому доставить и не с кем уточнить.

                     Полям даны настоящие `autocomplete`: браузер подставляет
                     их сам, и это единственный способ сделать длинную форму
                     терпимой на телефоне. --}}
                <section class="crt-step">
                    <h2 class="crt-step__title">
                        <span class="crt-step__num">04</span>
                        {{ __('frontend.cart.buyer') }}
                    </h2>

                    <p class="crt-hint">{{ __('frontend.cart.buyer_hint') }}</p>

                    <div class="crt-fields">
                        <label class="crt-field">
                            <span class="crt-field__label">
                                {{ __('frontend.cart.f_name') }} <b aria-hidden="true">*</b>
                            </span>
                            <input type="text" name="customer_name" required maxlength="255"
                                   autocomplete="name" class="crt-input"
                                   value="{{ old('customer_name', auth()->user()->name ?? '') }}">
                            @error('customer_name')<span class="crt-field__err">{{ $message }}</span>@enderror
                        </label>

                        <label class="crt-field">
                            <span class="crt-field__label">
                                {{ __('frontend.cart.f_phone') }} <b aria-hidden="true">*</b>
                            </span>
                            <input type="tel" name="customer_phone" required maxlength="64"
                                   autocomplete="tel" inputmode="tel" class="crt-input"
                                   placeholder="+7 900 000-00-00"
                                   value="{{ old('customer_phone', auth()->user()->phone ?? '') }}">
                            @error('customer_phone')<span class="crt-field__err">{{ $message }}</span>@enderror
                        </label>

                        <label class="crt-field">
                            <span class="crt-field__label">{{ __('frontend.cart.f_email') }}</span>
                            <input type="email" name="customer_email" maxlength="255"
                                   autocomplete="email" inputmode="email" class="crt-input"
                                   value="{{ old('customer_email', auth()->user()->email ?? '') }}">
                            <span class="crt-field__note">{{ __('frontend.cart.f_email_note') }}</span>
                            @error('customer_email')<span class="crt-field__err">{{ $message }}</span>@enderror
                        </label>

                        {{-- Адрес спрашивается не всегда: самовывозу и цифровому
                             товару он не нужен, а лишнее обязательное поле —
                             это брошенная корзина. Показом управляет скрипт по
                             типу выбранной службы. --}}
                        <label class="crt-field crt-field--wide" id="crt-address-field">
                            <span class="crt-field__label">
                                {{ __('frontend.cart.f_address') }} <b aria-hidden="true">*</b>
                            </span>
                            <input type="text" name="customer_address" maxlength="500"
                                   autocomplete="street-address" class="crt-input"
                                   placeholder="{{ __('frontend.cart.f_address_ph') }}"
                                   value="{{ old('customer_address', auth()->user()->address ?? '') }}">
                            @error('customer_address')<span class="crt-field__err">{{ $message }}</span>@enderror
                        </label>

                        <label class="crt-field crt-field--wide">
                            <span class="crt-field__label">{{ __('frontend.cart.f_comment') }}</span>
                            <textarea name="comment" rows="2" maxlength="2000"
                                      class="crt-input">{{ old('comment') }}</textarea>
                        </label>
                    </div>
                </section>
            </div>

            {{-- ── Итог ── --}}
            <aside class="crt-side">
                <div class="crt-box crt-total">
                    <p class="crt-box__title">{{ __('frontend.cart.summary') }}</p>

                    {{-- Что именно выбрано — строкой, чтобы это не приходилось
                         искать глазами по карточкам выше. --}}
                    <div class="crt-chosen" id="chosen-payment" hidden>
                        <span class="crt-chosen__label">{{ __('frontend.cart.payment_method') }}</span>
                        <span class="crt-chosen__value"></span>
                    </div>

                    <div class="crt-chosen" id="chosen-delivery" hidden>
                        <span class="crt-chosen__label">{{ __('frontend.cart.delivery_method') }}</span>
                        <span class="crt-chosen__value"></span>
                    </div>

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

                    {{-- Согласие ставится РУКОЙ, а не выводится из нажатия
                         кнопки. Раньше под кнопкой стояла строка «Нажимая
                         кнопку, вы соглашаетесь…» — это не согласие, а
                         уведомление: покупатель ничего не отмечал, и
                         подтвердить его волю было нечем.

                         Формулировка и ссылки те же, что на регистрации
                         (frontend.auth.terms) — заводить вторую значило бы
                         разойтись при первой же правке. Обе страницы
                         правятся в редакторе панели: /terms → «Соглашение»,
                         /privacy → «Конфиденциальность». Открываются в
                         новой вкладке, чтобы не потерять корзину. --}}
                    <label class="crt-consent">
                        <input type="checkbox" name="terms_agree" value="1" required
                               id="cart-consent" {{ old('terms_agree') ? 'checked' : '' }}>
                        <span>
                            {!! __('frontend.auth.terms', [
                                'terms'   => '<a href="' . url('/terms') . '" target="_blank" rel="noopener">' . __('frontend.auth.terms_link') . '</a>',
                                'privacy' => '<a href="' . url('/privacy') . '" target="_blank" rel="noopener">' . __('frontend.auth.privacy_link') . '</a>',
                            ]) !!}
                        </span>
                    </label>
                    @error('terms_agree')<p class="crt-consent__err">{{ $message }}</p>@enderror

                    <button type="submit" class="crt-submit" id="cart-submit">
                        <i class="fas fa-check"></i> {{ __('frontend.cart.checkout') }}
                    </button>
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
    // Выбор переехал с выпадающих списков на карточки-переключатели.
    // Данные для пересчёта лежат там же, где лежали у option.
    const picked = (name) => document.querySelector('input[name="' + name + '"]:checked');
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
        const dOpt = picked('delivery_method_id');

        if (dOpt) {
            delivery = parseFloat(dOpt.dataset.price || 0) || 0;
            const freeFrom = parseFloat(dOpt.dataset.freeFrom || 0) || 0;
            if (freeFrom > 0 && goods >= freeFrom) delivery = 0;
        }

        if (deliveryCost) deliveryCost.textContent = money(delivery);

        // Комиссия — процент от суммы товаров.
        let fee = 0;
        const pOpt = picked('payment_method_id');

        if (pOpt) {
            const percent = parseFloat(pOpt.dataset.commission || 0) || 0;
            if (percent > 0) fee = goods * percent / 100;
        }

        if (commissionCost) commissionCost.textContent = money(fee);
        if (commissionRow) commissionRow.classList.toggle('hidden', fee <= 0);

        if (grandTotal) grandTotal.textContent = money(goods + delivery + fee);
    }

    // Класс на выбранной карточке ставит JS, а не CSS-селектор :has():
    // он есть не везде, а без подсветки выбор читается плохо.
    function markPicked(name) {
        let chosen = null;

        document.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
            input.closest('.crt-opt').classList.toggle('is-picked', input.checked);
            if (input.checked) chosen = input;
        });

        // Строка «что выбрано» в итоге: карточки остаются выше, а сводка
        // липкая — иначе выбор приходилось бы искать прокруткой.
        const box = document.getElementById(name === 'payment_method_id' ? 'chosen-payment' : 'chosen-delivery');
        if (!box) return;

        box.hidden = !chosen;
        if (chosen) box.querySelector('.crt-chosen__value').textContent = chosen.dataset.title || '';
    }

    // Адрес нужен не всякой доставке: самовывозу его спрашивать незачем, а
    // лишнее обязательное поле — это брошенная корзина. Обязательность
    // снимается ВМЕСТЕ со скрытием: `required` на скрытом поле не даёт
    // отправить форму, и браузер молча ругается на невидимый элемент.
    function syncAddress() {
        const поле = document.getElementById('crt-address-field');
        if (!поле) return;

        const выбрана = picked('delivery_method_id');
        const нужен = !выбрана || выбрана.dataset.needsAddress !== '0';

        поле.hidden = !нужен;
        поле.querySelector('input').required = нужен;
    }

    ['payment_method_id', 'delivery_method_id'].forEach(function (name) {
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
            input.addEventListener('change', function () {
                markPicked(name);
                recalc();
                if (name === 'delivery_method_id') syncAddress();
            });
        });

        markPicked(name);
    });

    syncAddress();

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

    // Согласие: пока не отмечено — кнопка погашена. Это подсказка, а не
    // защита (её обходит любой, кто снимет атрибут), поэтому настоящая
    // проверка живёт в CartController::checkout. Без JS остаётся родной
    // required у поля — форма всё равно не отправится.
    var consent = document.getElementById('cart-consent');
    var submit  = document.getElementById('cart-submit');
    if (consent && submit) {
        var syncConsent = function () { submit.disabled = !consent.checked; };
        consent.addEventListener('change', syncConsent);
        syncConsent();
    }

    recalc();
});
</script>
@endpush

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. Цвета — из активной темы. */
    .crt{ max-width:76rem; margin:2.5rem auto; padding:0 1rem }

    /* ── Типографика ──
       Тот же приём, что на страницах входа и в разделах панели: мелкий
       моноширинный надзаголовок капсом с крупным просветом плюс крупный
       заголовок с плотным трекингом. Второй шрифт не нужен — системный
       моноширинный стек уже используется в проекте. */
    /* Подложка обязательна: у тем есть фоновая картинка, и текст без
       плашки на ней читается через силу. Плашка обнимает содержимое
       (inline-block), а не растягивается на всю ширину. */
    /* Шапка одной строкой во ВСЕХ режимах. Три этажа (надзаголовок,
       название кеглем до 2.15rem, счётчик) занимали до 130 пикселей ради
       одной содержательной фразы — на первом экране это дорого и на
       десктопе тоже. Теперь всё стоит в строку и переносится только если
       не помещается. */
    .crt__head{ display:flex; align-items:center; gap:.7rem;
        width:max-content; max-width:100%;
        margin-bottom:1rem; padding:.5rem 1.1rem .5rem .75rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        border-left:3px solid var(--color-primary,#6366f1);
        box-shadow:0 2px 12px rgba(15,23,42,.06) }
    /* Цвет темы, подмешанный к тексту: чистый акцент у оранжевых тем даёт
       контраст 3.67, а это мелкий текст — нужно от 4.5. */
    /* Плитка значка — тем же градиентом, что кнопки и бейджи сайта. */
    .crt__ico{ display:inline-flex; align-items:center; justify-content:center;
        flex:0 0 auto; width:2.4rem; height:2.4rem;
        color:#fff; font-size:1.05rem;
        background:var(--fx-grad, linear-gradient(135deg, var(--color-primary,#6366f1), var(--color-accent,#8b5cf6)));
        box-shadow:0 8px 18px -10px color-mix(in srgb, var(--color-primary,#6366f1) 75%, transparent) }

    .crt__eyebrow{ margin:0; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.66rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase;
        color:color-mix(in srgb, var(--color-primary,#6366f1) 72%, var(--surface-ink,#111827)) }
    .crt__title{ margin:0; font-size:clamp(1.15rem, 2.1vw, 1.45rem); font-weight:800;
        letter-spacing:-.025em; line-height:1.1; color:var(--surface-ink,#111827) }
    .crt__sub{ margin:0; font-size:.8rem; color:var(--surface-mute,#6b7280) }

    .crt-grid{ display:grid; grid-template-columns:minmax(0,1fr) 21rem; gap:1.25rem; align-items:start }

    /* Шаги оформления. Выбор способов переехал сюда из боковой колонки:
       слева пустовало полстраницы, а подписи в карточках обрезались на
       140 пикселях ширины. */
    .crt-main{ display:grid; gap:1.5rem; min-width:0 }
    .crt-step{ min-width:0 }
    .crt-step__title{ display:inline-flex; align-items:center; gap:.55rem; margin:0 0 .7rem;
        padding:.3rem .7rem .3rem .3rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.72rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
        color:var(--surface-ink,#111827) }
    .crt-step__num{ display:inline-flex; align-items:center; justify-content:center;
        align-self:stretch; min-width:1.75rem; padding:.25rem .4rem; font-size:.7rem; letter-spacing:.06em;
        color:var(--on-accent,#fff); background:var(--color-primary,#6366f1) }
    .crt-step__count{ padding:.1rem .35rem; font-size:.68rem; font-weight:700;
        color:var(--surface-mute,#64748b); background:var(--surface-2,#f8fafc) }

    /* ── Товары ── */
    .crt-items{ display:grid; gap:.5rem; align-content:start }
    .crt-item{ display:grid; grid-template-columns:minmax(0,1fr) auto auto auto;
        align-items:center; gap:1rem; padding:1rem 1.1rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7) }
    .crt-item__title{ margin:0; font-size:1rem; font-weight:700; color:var(--surface-ink,#111827); line-height:1.3 }
    .crt-item__price{ margin:.15rem 0 0; font-size:.82rem; color:var(--surface-mute,#64748b) }
    .crt-item__per{ opacity:.7 }

    /* Тот же переключатель, что на странице товара (.buy-qty): высота
       одной переменной, кнопки квадратные, поле между ними. */
    /* box-sizing явно: рамка в 1 пиксель иначе считается поверх высоты. */
    .crt-item__qty, .crt-qty__btn, .crt-qty__input{ box-sizing:border-box }
    .crt-item__qty{ --qty-h:36px; display:inline-flex; align-items:stretch; height:var(--qty-h);
        border:1px solid var(--surface-bd,#e2e8f0); background:var(--surface,#fff); overflow:hidden }
    .crt-qty__btn{ display:flex; align-items:center; justify-content:center;
        width:var(--qty-h); height:100%; padding:0; line-height:1;
        background:var(--surface-2,#f8fafc); color:var(--surface-ink,#334155);
        font-size:1rem; font-weight:700; border:0; cursor:pointer;
        transition:background .15s, color .15s }
    .crt-qty__btn:hover{ background:color-mix(in srgb, var(--color-primary,#6366f1) 10%, var(--surface,#fff));
        color:var(--color-primary,#6366f1) }
    .crt-qty__input{ width:2.75rem; height:100%; padding:0; text-align:center; border:0;
        border-left:1px solid var(--surface-bd,#e2e8f0); border-right:1px solid var(--surface-bd,#e2e8f0);
        font-size:.85rem; font-variant-numeric:tabular-nums;
        color:var(--surface-ink,#111827); background:var(--surface,#fff) }

    .crt-item__sum{ font-size:1.05rem; font-weight:800; color:var(--surface-ink,#111827); white-space:nowrap;
        min-width:7rem; text-align:right; font-variant-numeric:tabular-nums }

    .crt-item__del{ width:2.2rem; height:2.2rem; display:inline-flex; align-items:center;
        justify-content:center; color:#cbd5e1; background:transparent; border:0; cursor:pointer }
    .crt-item__del:hover{ color:#dc2626 }

    /* Ссылка стоит у правого края во всех режимах: она вторична рядом с
       «Оформить заказ», и слева под списком товаров смотрелась как ещё
       один пункт списка. `margin-left:auto` работает и во flex-, и в
       grid-родителе, поэтому режим раскладки тут не важен. */
    /* ⚠️ display:flex, а НЕ inline-flex. У строчного элемента `margin-left:auto`
       не сдвигает ничего — авто-поля работают только у блочных боксов, и
       ссылка оставалась у левого края. Ширина по содержимому, чтобы кнопка
       не растянулась во всю строку. */
    .crt-back{ display:flex; width:max-content; align-items:center; gap:.45rem; margin-top:.6rem;
        margin-left:auto; justify-self:end;
        padding:.45rem .8rem; font-size:.85rem; font-weight:600;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        color:color-mix(in srgb, var(--color-primary,#6366f1) 72%, var(--surface-ink,#111827));
        transition:border-color .15s }
    .crt-back:hover{ border-color:var(--color-primary,#6366f1) }

    /* ── Оформление ── */
    .crt-side{ display:grid; gap:.75rem; position:sticky; top:1rem }
    .crt-box{ padding:1.1rem 1.2rem; background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7) }
    .crt-box__title{ margin:0 0 .75rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.14em;
        color:var(--surface-mute,#64748b) }

    /* Что выбрано — прямо в липкой сводке. */
    .crt-chosen{ display:flex; align-items:baseline; justify-content:space-between; gap:.75rem;
        padding:.35rem 0; border-bottom:1px dashed var(--surface-bd,#e2e8f0) }
    .crt-chosen__label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.6rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:var(--surface-mute,#94a3b8) }
    .crt-chosen__value{ font-size:.8rem; font-weight:700; color:var(--surface-ink,#111827);
        text-align:right; min-width:0 }

    /* ── Выбор способа: карточки вместо выпадающего списка ──
       Фирменный цвет приходит переменной --pm со стороны разметки:
       способов столько же, сколько цветов, и перечислять их правилами
       пришлось бы дважды — в CSS и в модели. */
    .crt-pick{ display:grid; gap:.5rem; grid-template-columns:repeat(auto-fill, minmax(min(100%, 19rem), 1fr)) }

    .crt-opt{ display:grid; grid-template-columns:2.5rem minmax(0,1fr) auto auto;
        align-items:center; gap:.7rem; padding:.65rem .75rem; cursor:pointer;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#e2e8f0);
        transition:border-color .15s, box-shadow .15s }
    .crt-opt:hover{ border-color:var(--pm,#6366f1) }
    .crt-opt.is-picked{ border-color:var(--pm,#6366f1);
        box-shadow:inset 0 0 0 1px var(--pm,#6366f1) }

    /* Сам переключатель не рисуем: роль «выбрано» несёт вся карточка, а
       рамка фокуса остаётся — с клавиатуры выбор виден. */
    .crt-opt input{ position:absolute; opacity:0; width:0; height:0 }
    .crt-opt:focus-within{ outline:2px solid var(--color-primary,#6366f1); outline-offset:1px }

    .crt-opt__mark{ display:flex; align-items:center; justify-content:center;
        flex:0 0 auto; width:2.75rem; height:2.75rem; overflow:hidden;
        color:var(--pm-ink,#fff); background:var(--pm,#6366f1) }
    /* Значок занимает плитку осмысленной долей: при 16px в круге 40 он
       выглядел потерянным. Ветка с логотипом-картинкой убрана — в корзине
       рисуем только знаки (см. разметку). */
    .crt-opt__mark i{ font-size:1.15rem; line-height:1 }

    .crt-opt__body{ display:flex; flex-direction:column; gap:.05rem; min-width:0 }
    .crt-opt__name{ font-size:.88rem; font-weight:700; color:var(--surface-ink,#111827);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .crt-opt__note{ font-size:.72rem; line-height:1.35; color:var(--surface-mute,#64748b);
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }

    .crt-opt__meta{ font-size:.82rem; font-weight:700; white-space:nowrap;
        color:var(--surface-ink,#111827); font-variant-numeric:tabular-nums }

    .crt-opt__tick{ font-size:.9rem; color:var(--surface-bd,#e2e8f0) }
    .crt-opt.is-picked .crt-opt__tick{ color:var(--pm,#6366f1) }
    .crt-hint{ margin:.5rem 0 0; font-size:.78rem; line-height:1.5; color:var(--surface-mute,#64748b) }

    /* ── Шаг «Покупатель» ──
       Сетка в две колонки: имя и телефон встают парой, адрес и комментарий
       занимают строку целиком. `minmax(min(100%, …))` вместо голого числа —
       иначе на 360 колонка не сжимается и распирает страницу вбок. */
    .crt-fields{ display:grid; gap:.7rem; margin-top:.6rem;
        grid-template-columns:repeat(auto-fit, minmax(min(100%, 14rem), 1fr)) }
    .crt-field{ display:flex; flex-direction:column; gap:.3rem; min-width:0 }
    .crt-field--wide{ grid-column:1 / -1 }
    .crt-field[hidden]{ display:none }

    /* Подписи — тем же моноширинным капсом, что на страницах входа и в
       панели: второй «шрифт» уже есть в проекте и ничего не догружает. */
    .crt-field__label{ font-family:ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:var(--surface-mute,#64748b) }
    .crt-field__label b{ color:#dc2626 }
    .crt-field__note{ font-size:.72rem; color:var(--surface-mute,#94a3b8) }
    .crt-field__err{ font-size:.74rem; color:#dc2626 }

    .crt-input{ width:100%; box-sizing:border-box; font:inherit; font-size:.88rem;
        padding:.6rem .7rem; color:var(--surface-ink,#111827);
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#e2e8f0) }
    .crt-input:focus{ outline:none; border-color:var(--color-primary,#6366f1) }
    textarea.crt-input{ resize:vertical; min-height:3.2rem }

    .crt-total__row{ display:flex; align-items:baseline; justify-content:space-between; gap:1rem;
        padding:.35rem 0; font-size:.88rem; color:var(--surface-mute,#475569) }
    .crt-total__row b{ color:var(--surface-ink,#111827); white-space:nowrap;
        font-variant-numeric:tabular-nums }
    .crt-total__grand{ display:flex; align-items:baseline; justify-content:space-between; gap:1rem;
        margin-top:.5rem; padding-top:.75rem; border-top:1px solid #eef2f7;
        font-size:.95rem; font-weight:700; color:var(--surface-ink,#111827) }
    .crt-total__grand b{ font-size:1.45rem; white-space:nowrap; letter-spacing:-.02em;
        font-variant-numeric:tabular-nums }

    .crt-submit{ display:flex; align-items:center; justify-content:center; gap:.5rem; width:100%;
        margin-top:1rem; padding:.8rem 1rem; font-size:.95rem; font-weight:700; color:var(--on-accent,#fff);
        border:0; cursor:pointer;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6));
        transition:filter .15s }
    .crt-submit:hover{ filter:brightness(1.08); color:#fff }
    .crt-submit--inline{ width:auto; display:inline-flex; margin-top:1.25rem; padding:.7rem 1.5rem }


    /* Согласие на обработку данных. Строка кликабельна целиком (label), а
       не только сама отметка: попасть в квадрат 18×18 пальцем тяжело. */
    .crt-consent{ display:flex; align-items:flex-start; gap:.6rem; margin:.85rem 0 .75rem;
        font-size:.78rem; line-height:1.45; color:var(--surface-ink,#334155); cursor:pointer }
    .crt-consent input{ flex:none; width:18px; height:18px; margin-top:.1rem; cursor:pointer;
        accent-color:var(--color-primary,#6366f1) }
    .crt-consent a{ color:color-mix(in srgb, var(--color-primary,#6366f1) 72%, var(--surface-ink,#111827));
        text-decoration:underline; text-underline-offset:2px }
    .crt-consent__err{ margin:0 0 .6rem; font-size:.75rem; color:#dc2626 }
    /* Кнопка при неотмеченном согласии гасится — видно, что шаг не пройден.
       Это подсказка, а НЕ защита: настоящая проверка на сервере. */
    .crt-submit:disabled{ opacity:.5; cursor:not-allowed; filter:grayscale(.35) }

    /* ── Пусто ── */
    .crt-empty{ padding:3.5rem 1rem; text-align:center; background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7) }
    .crt-empty i{ font-size:2.5rem; color:#c7d2fe; display:block; margin-bottom:1rem }
    .crt-empty__title{ margin:0; font-size:1.1rem; font-weight:700; color:var(--surface-ink,#111827) }
    .crt-empty__hint{ margin:.35rem 0 0; font-size:.85rem; color:var(--surface-mute,#64748b) }

    /* ── Уведомления ── */
    .crt-toasts{ position:fixed; top:1.25rem; right:1.25rem; z-index:60; display:grid; gap:.5rem }
    .crt-toast{ padding:.7rem 1rem; font-size:.85rem; font-weight:600; color:#fff;
        box-shadow:0 8px 24px rgba(15,23,42,.25) }
    .crt-toast--success{ background:#16a34a }
    .crt-toast--error{ background:#dc2626 }

    @media (max-width: 960px){
        .crt-grid{ grid-template-columns:1fr }
        .crt-main{ gap:1.25rem }
        .crt-pick{ grid-template-columns:1fr }
        .crt-side{ position:static }
        .crt-item{ grid-template-columns:minmax(0,1fr) auto; row-gap:.75rem }
        .crt-item__sum{ text-align:left; min-width:0 }
    }

    /* ⚠️ Здесь стоял блок по тёмному режиму ОПЕРАЦИОННОЙ СИСТЕМЫ. Тему
       сайта задаёт оформление (переменные --surface-*), и корзина уже
       следует ей; настройка ОС к ней отношения не имеет и только
       перекрашивала карточки в тёмные посреди светлой страницы. */

    /* ═════════ Телефоны и планшеты ═════════
       Шапка занимала 130 пикселей ради трёх строк: надзаголовок, крупное
       название и счётчик. На экране в 896 это заметная доля первого
       экрана, а полезного в ней — одна строка. Ужимаем отступы и кегль,
       смысл сохраняем. */
    @media (max-width: 1024px), (max-height: 500px){
        /* ── Шаг «Покупатель» на сенсорных ───────────────────────────
           Кегль подписей поднимается до 12: ниже браузер на телефоне
           предлагает увеличить страницу целиком и ломает вёрстку (общее
           правило проекта).

           ⚠️ А полям ввода нужно ровно 16 пикселей, и это не то же самое.
           Safari на iPhone САМ приближает страницу при фокусе в поле мельче
           16 — в форме оформления это выглядит как прыжок вёрстки на каждом
           поле, и обратно она уже не отъезжает. Обойти это можно только
           кеглем: `user-scalable=no` ломает доступность и Safari его всё
           равно игнорирует. */
        .crt-field__label{ font-size:.75rem }
        .crt-field__note{ font-size:.75rem }

        /* Кегль ниже порога был и у соседних подписей корзины — замер по всем
           ширинам показал 11.2, 10.9 и 9.6. Правим заодно: это тот же экран. */
        .crt-step__num{ font-size:.75rem }
        .crt-step__count{ font-size:.75rem }
        .crt-chosen__label{ font-size:.75rem }

        /* 16 — не круглое число, а порог Safari (см. пояснение выше).
           Счётчику количества он нужен ровно так же, как полям покупателя. */
        .crt-input,
        .crt-qty__input{ font-size:16px }
    }

    @media (max-width: 1024px){
        /* ── Карточка товара ───────────────────────────────────────────
           Было две колонки на два ряда: название с ценой и счётчик сверху,
           сумма и корзина снизу. Между ними зияла пустота, а кнопка
           удаления висела сама по себе у правого края.

           Теперь название занимает всю ширину (ему и нужна вся ширина —
           это самое длинное в карточке), а под ним одной строкой идут
           счётчик, сумма и удаление: слева то, чем управляют, справа —
           итог и действие. */
        .crt-item{
            grid-template-columns:auto minmax(0,1fr) auto;
            grid-template-areas:
                "name name name"
                "qty  sum  del";
            row-gap:.4rem; column-gap:.6rem; align-items:center;
            padding:.6rem .75rem;
        }
        /* Цена за штуку встаёт в строку с названием, а не под ним: это
           экономит целую строку в каждой карточке, а вместе они и
           читаются как одно целое — «что» и «почём». */
        .crt-item__main{ display:flex; align-items:baseline; flex-wrap:wrap; gap:.15rem .5rem }
        /* Счётчик был 42 в высоту — на два пикселя ниже порога нажатия.
           Высоту двигаем ОДНОЙ переменной: кнопки и поле следуют за ней. */
        .crt-item__qty{ --qty-h:44px }
        .crt-item__price{ margin:0 }
        .crt-item__main{ grid-area:name }
        .crt-item__qty{ grid-area:qty }
        .crt-item__sum{ grid-area:sum; text-align:right }
        .crt-item__del{ grid-area:del; width:44px; height:44px }

        .crt-item__title{ font-size:.95rem; line-height:1.25 }
        .crt-item__price{ font-size:.76rem }

        /* Шапка страницы — одной строкой. Три этажа (надзаголовок,
           крупное название, счётчик) занимали 130 пикселей первого экрана
           ради одной содержательной строки. Надзаголовок на телефоне
           убираем совсем: он повторяет то, что и так ясно из названия. */
        /* Подписи шагов и пояснения способов были 10.9–11.5px. */
        .crt-step__title, .crt-opt__note, .crt-box__title{ font-size:12px }
        /* Отметка согласия крупнее, и вся строка не ниже зоны нажатия. */
        .crt-consent{ min-height:44px; align-items:center; font-size:12px }
        .crt-consent input{ width:24px; height:24px; margin-top:0 }

        .crt__head{ margin-bottom:.75rem; padding:.4rem .85rem .4rem .6rem; gap:.55rem }
        .crt__ico{ width:2.1rem; height:2.1rem; font-size:.95rem }
    }
</style>
@endpush
