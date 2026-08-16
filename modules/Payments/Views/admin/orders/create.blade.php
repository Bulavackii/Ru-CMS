@extends('layouts.admin')

@section('title', __('admin.orders.create_title'))

@section('content')
{{-- Шапка в два ряда (общее определение `.mh-*` живёт в лейауте). --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
    <div class="mh-row">
        <span class="admin-icon-badge"><i class="fas fa-cart-plus"></i></span>
        <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
            {{ __('admin.orders.create_title') }}
        </h1>
    </div>

    <div class="mh-row mh-row--sub">
        <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.orders.create_subtitle') }}
        </p>

        <a href="{{ route('admin.orders.index') }}"
           class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
            <i class="fas fa-arrow-left"></i> {{ __('admin.orders.back') }}
        </a>
    </div>
</div>

{{-- Флеш-сообщения рисует сам лейаут (`layouts/partials/flash`), здесь только
     ошибки формы: заказ отбивается по остатку, и владелец должен видеть, по
     какому именно товару. --}}
@if ($errors->any())
    <div class="admin-card p-4 mb-4 border-l-4 border-red-500">
        <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside">
            @foreach ($errors->all() as $ошибка)
                <li>{{ $ошибка }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($товары->isEmpty())
    <div class="admin-card p-10 text-center text-gray-500 dark:text-gray-400">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-box-open"></i></span>
        <div class="font-semibold text-gray-900 dark:text-white">{{ __('admin.orders.create_no_products') }}</div>
        <p class="text-sm mt-2 max-w-md mx-auto">{{ __('admin.orders.create_no_products_hint') }}</p>
    </div>
@else
<form method="POST" action="{{ route('admin.orders.store') }}" id="orderCreate">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        {{-- ── Состав заказа ── --}}
        <div class="admin-card p-4 lg:col-span-2 min-w-0">
            <h2 class="oc-h2"><i class="fas fa-boxes-stacked text-indigo-500"></i> {{ __('admin.orders.create_items') }}</h2>

            <p class="admin-hint mb-3">{{ __('admin.orders.create_items_hint') }}</p>

            <div id="ocRows" class="flex flex-col gap-2"></div>

            <button type="button" id="ocAdd"
                    class="mt-3 inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                           hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
                <i class="fas fa-plus"></i> {{ __('admin.orders.create_add_item') }}
            </button>
        </div>

        {{-- ── Покупатель и способы ── --}}
        <div class="flex flex-col gap-4 min-w-0">
            <div class="admin-card p-4 min-w-0">
                <h2 class="oc-h2"><i class="fas fa-user text-indigo-500"></i> {{ __('admin.orders.customer') }}</h2>

                <label class="oc-label" for="ocName">{{ __('admin.orders.c_name') }} <span class="text-red-500">*</span></label>
                <input id="ocName" name="customer_name" value="{{ old('customer_name') }}" required
                       class="oc-input" maxlength="255">

                <label class="oc-label" for="ocPhone">{{ __('admin.orders.c_phone') }}</label>
                <input id="ocPhone" name="customer_phone" value="{{ old('customer_phone') }}"
                       class="oc-input" maxlength="64" inputmode="tel">

                <label class="oc-label" for="ocMail">{{ __('admin.orders.c_email') }}</label>
                <input id="ocMail" name="customer_email" value="{{ old('customer_email') }}" type="email"
                       class="oc-input" maxlength="255">

                <label class="oc-label" for="ocAddr">{{ __('admin.orders.c_address') }}</label>
                <input id="ocAddr" name="customer_address" value="{{ old('customer_address') }}"
                       class="oc-input" maxlength="500">

                <label class="oc-label" for="ocComment">{{ __('admin.orders.c_comment') }}</label>
                <textarea id="ocComment" name="comment" rows="3" class="oc-input" maxlength="2000">{{ old('comment') }}</textarea>
            </div>

            <div class="admin-card p-4 min-w-0">
                <h2 class="oc-h2"><i class="fas fa-credit-card text-indigo-500"></i> {{ __('admin.orders.f_payment') }}</h2>

                <label class="oc-label" for="ocPay">{{ __('admin.orders.f_payment') }} <span class="text-red-500">*</span></label>
                <select id="ocPay" name="payment_method_id" required class="oc-input">
                    @foreach ($оплата as $способ)
                        <option value="{{ $способ->id }}" @selected(old('payment_method_id') == $способ->id)>
                            {{ $способ->title }}@if($способ->commission > 0) (+{{ rtrim(rtrim(number_format($способ->commission, 2, ',', ' '), '0'), ',') }}%)@endif
                        </option>
                    @endforeach
                </select>

                <label class="oc-label" for="ocShip">{{ __('admin.orders.f_delivery') }}</label>
                <select id="ocShip" name="delivery_method_id" class="oc-input">
                    <option value="">{{ __('admin.orders.create_no_delivery') }}</option>
                    @foreach ($доставка as $служба)
                        <option value="{{ $служба->id }}" @selected(old('delivery_method_id') == $служба->id)>
                            {{ $служба->title }} — {{ number_format((float) $служба->price, 2, ',', ' ') }} ₽
                        </option>
                    @endforeach
                </select>

                <p class="admin-hint mt-3">{{ __('admin.orders.create_status_hint') }}</p>

                <button type="submit"
                        class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 text-sm font-semibold transition">
                    <i class="fas fa-check"></i> {{ __('admin.orders.create_submit') }}
                </button>
            </div>
        </div>
    </div>
</form>

@push('styles')
<style>
    /* Литеральный CSS: в сборке Tailwind нет ни прозрачности через дробь,
       ни произвольных значений — см. «Особенности песочницы». */
    .oc-h2{ display:flex; align-items:center; gap:.4rem; margin-bottom:.75rem;
            font-size:.78rem; letter-spacing:.08em; text-transform:uppercase;
            font-weight:700; color:#374151 }

    .oc-label{ display:block; margin-top:.75rem; margin-bottom:.25rem;
               font-size:.72rem; letter-spacing:.06em; text-transform:uppercase;
               font-weight:700; color:#6b7280 }
    .oc-input{ width:100%; box-sizing:border-box; border:1px solid #d1d5db;
               background:#fff; color:#111827; padding:.55rem .7rem; font-size:.9rem }
    .oc-input:focus{ outline:none; border-color:#6366f1 }

    .oc-row{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap }
    .oc-row select{ flex:1 1 14rem; min-width:0 }
    .oc-row .oc-qty{ flex:0 0 5.5rem; text-align:center }
    .oc-del{ flex:0 0 auto; width:40px; height:40px; display:inline-flex;
             align-items:center; justify-content:center;
             border:1px solid #fca5a5; color:#b91c1c; background:#fff }
    .oc-del:hover{ background:#fee2e2 }

    /* Итог считается на клиенте — это подсказка, а не источник правды:
       сервер пересчитывает всё сам по ценам из базы. */
    .oc-total{ margin-top:.75rem; font-size:.9rem; color:#374151 }
    .oc-total b{ font-variant-numeric:tabular-nums }

    /* ⚠️ На сенсорных полям нужно РОВНО 16 пикселей.
       Safari на iPhone сам приближает страницу при фокусе в поле мельче 16, и
       обратно она не отъезжает — в форме из семи полей это прыжок вёрстки на
       каждом. Обойти можно только кеглем: `user-scalable=no` ломает
       доступность и Safari его всё равно игнорирует.

       Порог тот же, что во всём проекте. */
    @media (max-width: 1024px), (max-height: 500px){
        .oc-input{ font-size:16px }
        .oc-label{ font-size:.75rem }

        /* Строка товара: на телефоне список и количество встают друг под
           другом, кнопка удаления остаётся справа от количества. */
        .oc-row select{ flex:1 1 100% }
        .oc-row .oc-qty{ flex:1 1 auto }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const товары = @js($товары->map(fn ($т) => [
        'id'    => $т->id,
        'title' => $т->title,
        'price' => (float) $т->price,
        'stock' => $т->stock,
    ])->values());

    const подписи = {
        pick:   @js(__('admin.orders.create_pick_product')),
        remove: @js(__('admin.orders.create_remove_item')),
        total:  @js(__('admin.orders.create_total')),
        left:   @js(__('admin.orders.create_stock_left')),
    };

    const строки = document.getElementById('ocRows');
    const итог   = document.createElement('div');
    итог.className = 'oc-total';
    строки.after(итог);

    let счётчик = 0;

    function вариант(т) {
        const остаток = t_stock(т);
        return `<option value="${т.id}" data-price="${т.price}">${экран(т.title)} — ${т.price.toFixed(2)} ₽${остаток}</option>`;
    }

    function t_stock(т) {
        // Пустой остаток — это «не считаем» (услуга), а не ноль.
        return т.stock === null || т.stock === undefined ? '' : ` · ${подписи.left} ${т.stock}`;
    }

    function экран(s) {
        return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    }

    function добавить() {
        const i = счётчик++;
        const строка = document.createElement('div');
        строка.className = 'oc-row';
        строка.innerHTML =
            `<select name="items[${i}][id]" class="oc-input" required>` +
                товары.map(вариант).join('') +
            `</select>` +
            `<input type="number" name="items[${i}][qty]" value="1" min="1" step="1" required ` +
                   `class="oc-input oc-qty" aria-label="${подписи.pick}">` +
            `<button type="button" class="oc-del" title="${подписи.remove}" aria-label="${подписи.remove}">` +
                `<i class="fas fa-trash"></i></button>`;

        строка.querySelector('.oc-del').addEventListener('click', () => {
            // Последнюю строку не убираем: заказ без товаров сервер не примет.
            if (строки.children.length > 1) { строка.remove(); пересчитать(); }
        });
        строка.addEventListener('change', пересчитать);
        строка.addEventListener('input', пересчитать);

        строки.appendChild(строка);
        пересчитать();
    }

    function пересчитать() {
        let сумма = 0;

        строки.querySelectorAll('.oc-row').forEach(р => {
            const выбор = р.querySelector('select');
            const цена  = parseFloat(выбор.selectedOptions[0]?.dataset.price || 0);
            const кол   = parseInt(р.querySelector('.oc-qty').value || 0, 10);
            if (цена > 0 && кол > 0) сумма += цена * кол;
        });

        итог.innerHTML = подписи.total + ' <b>' + сумма.toFixed(2).replace('.', ',') + ' ₽</b>';
    }

    document.getElementById('ocAdd').addEventListener('click', добавить);
    добавить();
})();
</script>
@endpush
@endif
@endsection
