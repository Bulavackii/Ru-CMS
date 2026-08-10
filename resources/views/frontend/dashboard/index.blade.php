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
{{-- Шапка с обращением по имени: страница личная, безличный заголовок
     «Личный кабинет» этого не передавал. --}}
<div class="acc-head">
    <span class="acc-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    <div class="min-w-0">
        <h1 class="fx-section-title">{{ __('frontend.account.hello', ['name' => $user->name]) }}</h1>
        <p class="fx-section-sub">{{ __('frontend.account.subtitle') }}</p>
    </div>
</div>

@if (session('success'))
    <div class="acc-flash">{{ session('success') }}</div>
@endif

@php
    // Сводка считается по тем заказам, что уже отданы вьюхе: отдельный
    // запрос ради двух чисел здесь не нужен.
    $ordersTotal = $orders->sum('total');
    $lastOrder = $orders->sortByDesc('created_at')->first();
@endphp

{{-- Сводка: три плитки со значком. Прежние были высокими и почти
     пустыми — подпись сверху, число снизу и много воздуха между ними. --}}
<div class="acc-stats">
    <div class="acc-stat">
        <span class="acc-stat__ico"><i class="fas fa-box"></i></span>
        <span class="acc-stat__body">
            <b class="acc-stat__value">{{ $orders->count() }}</b>
            <span class="acc-stat__label">{{ __('frontend.account.orders_count') }}</span>
        </span>
    </div>
    <div class="acc-stat">
        <span class="acc-stat__ico"><i class="fas fa-ruble-sign"></i></span>
        <span class="acc-stat__body">
            <b class="acc-stat__value">{{ number_format((float) $ordersTotal, 0, ',', ' ') }} ₽</b>
            <span class="acc-stat__label">{{ __('frontend.account.spent') }}</span>
        </span>
    </div>
    <div class="acc-stat">
        <span class="acc-stat__ico"><i class="fas fa-calendar-day"></i></span>
        <span class="acc-stat__body">
            <b class="acc-stat__value">{{ $lastOrder ? $lastOrder->created_at->format('d.m.Y') : '—' }}</b>
            <span class="acc-stat__label">{{ __('frontend.account.last_order') }}</span>
        </span>
    </div>
</div>

<div class="acc-grid">

    {{-- ── Профиль ── --}}
    <section class="fx-card p-5">
        <h2 class="acc-h2"><i class="fas fa-id-card fx-ico"></i> {{ __('frontend.account.profile') }}</h2>

        <dl class="acc-list">
            <div><dt>{{ __('frontend.account.name') }}</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>{{ __('frontend.account.email') }}</dt><dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd></div>
            <div><dt>{{ __('frontend.account.user_type') }}</dt>
                <dd>{{ $user->is_company ? __('frontend.account.legal_entity_type') : __('frontend.account.individual') }}</dd></div>

            {{-- Телефон и адрес показываем, только если заполнены: строка с
                 прочерком места занимает столько же, а сообщает ничего.
                 Карточка пустовала почти наполовину именно потому, что в ней
                 было три строки из десятка заполненных полей. --}}
            @if ($user->phone)
                <div><dt>{{ __('frontend.account.f_phone') }}</dt>
                    <dd><a href="tel:{{ preg_replace('~[^\d+]~', '', $user->phone) }}">{{ $user->phone }}</a></dd></div>
            @endif

            @if ($user->address)
                <div><dt>{{ __('frontend.account.f_address') }}</dt><dd>{{ $user->address }}</dd></div>
            @endif

            @if ($user->created_at)
                <div><dt>{{ __('frontend.account.member_since') }}</dt>
                    <dd>{{ $user->created_at->format('d.m.Y') }}</dd></div>
            @endif

            {{-- Строка появляется, только если ссылка задана: пустое поле с
                 прочерком в профиле ничего не сообщает. --}}
            @if ($user->vk || $user->max)
                <div>
                    <dt>{{ __('frontend.account.socials') }}</dt>
                    <dd>
                        <span class="acc-socials">
                            @if ($user->vk)
                                <a href="{{ $user->vk }}" target="_blank" rel="noopener"
                                   class="acc-social" style="--c:#0077FF" title="ВКонтакте">
                                    <x-icon.vk :size="15" /> <span>ВКонтакте</span>
                                </a>
                            @endif
                            @if ($user->max)
                                <a href="{{ $user->max }}" target="_blank" rel="noopener"
                                   class="acc-social" style="--c:#3B4BF5" title="MAX">
                                    <x-icon.max :size="15" /> <span>MAX</span>
                                </a>
                            @endif
                        </span>
                    </dd>
                </div>
            @endif

            @if ($user->is_company)
                <div><dt>{{ __('frontend.account.company') }}</dt><dd>{{ $user->company_name ?: '—' }}</dd></div>
                <div><dt>{{ __('frontend.account.inn') }}</dt><dd>{{ $user->inn ?: '—' }}</dd></div>
                <div><dt>{{ __('frontend.account.ogrn') }}</dt><dd>{{ $user->ogrn ?: '—' }}</dd></div>
            @endif
        </dl>

        @unless ($user->phone && $user->address)
            {{-- Мягкая подсказка вместо пустоты: половина полей профиля
                 обычно не заполнена, и карточка выглядела недоделанной. --}}
            <p class="acc-fill-hint">
                <i class="fas fa-circle-info"></i>
                {{ __('frontend.account.fill_hint') }}
                <a href="{{ route('dashboard.edit') }}">{{ __('frontend.account.fill_hint_link') }}</a>
            </p>
        @endunless
    </section>

    {{-- ── Действия ──
         Раньше этот блок лежал ВНУТРИ ветки «есть заказы»: у пользователя
         без заказов не было ни кнопки правки профиля, ни смены пароля. --}}
    <section class="fx-card p-5">
        <h2 class="acc-h2"><i class="fas fa-sliders fx-ico"></i> {{ __('frontend.account.actions') }}</h2>

        {{-- Строки с пояснением вместо ряда кнопок. Кнопки стояли в линию,
             занимали всю ширину карточки и не говорили, что будет после
             нажатия; под ними оставалась пустота в половину блока. --}}
        <div class="acc-links">
            <a href="{{ route('dashboard.edit') }}" class="acc-link">
                <span class="acc-link__ico"><i class="fas fa-pen"></i></span>
                <span class="acc-link__body">
                    <span class="acc-link__title">{{ __('frontend.account.edit') }}</span>
                    <span class="acc-link__note">{{ __('frontend.account.edit_note') }}</span>
                </span>
                <i class="fas fa-chevron-right acc-link__arrow"></i>
            </a>

            @if ($user->is_company)
                <a href="{{ route('organization.edit') }}" class="acc-link">
                    <span class="acc-link__ico"><i class="fas fa-building"></i></span>
                    <span class="acc-link__body">
                        <span class="acc-link__title">{{ __('frontend.account.edit_org') }}</span>
                        <span class="acc-link__note">{{ __('frontend.account.edit_org_note') }}</span>
                    </span>
                    <i class="fas fa-chevron-right acc-link__arrow"></i>
                </a>
            @endif

            <a href="{{ route('password.change.form') }}" class="acc-link">
                <span class="acc-link__ico"><i class="fas fa-lock"></i></span>
                <span class="acc-link__body">
                    <span class="acc-link__title">{{ __('frontend.account.change_pass') }}</span>
                    <span class="acc-link__note">{{ __('frontend.account.change_pass_note') }}</span>
                </span>
                <i class="fas fa-chevron-right acc-link__arrow"></i>
            </a>

            {{-- Двухфакторная проверка была доступна ТОЛЬКО из настроек в
                 админке: обычный покупатель включить её не мог никак, хотя
                 страница привязки работает для любого вошедшего. Маршрут
                 проверяется — часть проекта может быть отключена. --}}
            @if (Route::has('two-factor.setup'))
                <a href="{{ route('two-factor.setup') }}" class="acc-link">
                    <span class="acc-link__ico"><i class="fas fa-mobile-screen"></i></span>
                    <span class="acc-link__body">
                        <span class="acc-link__title">{{ __('frontend.account.two_factor') }}</span>
                        <span class="acc-link__note">{{ __('frontend.account.two_factor_note') }}</span>
                    </span>
                    {{-- hasTwoFactorEnabled(), а не голый флаг: флаг может
                         стоять при утраченном ключе, и плашка обещала бы
                         защиту, которой на входе уже нет. --}}
                    <span class="acc-state {{ $user->hasTwoFactorEnabled() ? 'is-on' : '' }}">
                        {{ $user->hasTwoFactorEnabled() ? __('frontend.account.two_factor_on') : __('frontend.account.two_factor_off') }}
                    </span>
                    <i class="fas fa-chevron-right acc-link__arrow"></i>
                </a>
            @endif

            @if (Route::has('dashboard.login-history'))
                <a href="{{ route('dashboard.login-history') }}" class="acc-link">
                    <span class="acc-link__ico"><i class="fas fa-clock-rotate-left"></i></span>
                    <span class="acc-link__body">
                        <span class="acc-link__title">{{ __('frontend.account.login_history') }}</span>
                        <span class="acc-link__note">{{ __('frontend.account.login_history_note') }}</span>
                    </span>
                    <i class="fas fa-chevron-right acc-link__arrow"></i>
                </a>
            @endif
        </div>
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
        {{-- Прежнее пустое состояние отправляло «к новостям»: человек ждёт
             товары, а попадает в ленту статей. Теперь кнопка ведёт в каталог,
             а рядом коротко сказано, что вообще будет храниться в этом
             разделе — иначе пустая карточка не сообщает ничего. --}}
        {{-- Пустой раздел занимал 357px по высоте ради трёх строк текста:
             значок, заголовок, подпись, список и кнопки шли столбиком по
             центру. Теперь всё в одну строку — значок и текст слева,
             действия справа, — а перечень идёт под ней в три колонки. --}}
        <div class="acc-empty">
            <div class="acc-empty__row">
                <span class="acc-empty__ico"><i class="fas fa-box-open"></i></span>

                <div class="acc-empty__text">
                    <p class="acc-empty__title">{{ __('frontend.account.orders_empty') }}</p>
                    <p class="acc-empty__sub">{{ __('frontend.account.orders_none_hint') }}</p>
                </div>

                <div class="acc-empty__actions">
                    <a href="{{ url('/news') }}" class="fx-btn">
                        <i class="fas fa-store"></i> {{ __('frontend.account.to_catalog') }}
                    </a>
                    <a href="{{ route('cart.index') }}" class="acc-btn-ghost">
                        <i class="fas fa-cart-shopping"></i> {{ __('frontend.account.to_cart') }}
                    </a>
                </div>
            </div>

            <ul class="acc-empty__facts">
                <li>{{ __('frontend.account.orders_fact_status') }}</li>
                <li>{{ __('frontend.account.orders_fact_docs') }}</li>
                <li>{{ __('frontend.account.orders_fact_repeat') }}</li>
            </ul>
        </div>
    @endforelse
</section>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни динамических классов вида bg-{$color}-100 — именно на
       них держались прежние бейджи статуса, и они выводились бесцветными. */
    /* Ссылки на страницы в сетях: фирменный цвет проступает при наведении,
       чтобы знак оставался узнаваемым при любом оформлении сайта. */
    .acc-socials{ display:inline-flex; flex-wrap:wrap; gap:.4rem; justify-content:flex-end }
    .acc-social{ display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .55rem;
        font-size:.78rem; font-weight:600; color:var(--surface-ink,#475569); background:color-mix(in srgb, var(--color-primary, #6366f1) 8%, transparent);
        text-decoration:none; transition:color .15s, background .15s }
    .acc-social:hover{ color:#fff; background:var(--c,#6366f1) }
    .acc-social svg{ flex:0 0 auto }

    /* Пустой раздел заказов: три строки о том, что тут будет, и два
       действия. Прежде была одна кнопка «к новостям» и ничего больше. */
    /* Пустой раздел — одной строкой, а не столбиком по центру. */
    .acc-empty__row{ display:flex; align-items:center; gap:1rem; flex-wrap:wrap }
    .acc-empty__ico{ display:inline-flex; align-items:center; justify-content:center;
        width:2.75rem; height:2.75rem; flex:0 0 auto; font-size:1.05rem; color:var(--on-accent,#fff);
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }
    .acc-empty__text{ flex:1 1 14rem; min-width:0; text-align:left }
    .acc-empty__sub{ margin:.15rem 0 0; font-size:.85rem; color:var(--surface-mute,#6b7280) }

    /* Перечень в три колонки: столбиком он растягивал блок по высоте. */
    .acc-empty__facts{ display:grid; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));
        gap:.4rem 1.25rem; margin:1rem 0 0; padding:.9rem 0 0; list-style:none;
        font-size:.82rem; color:var(--surface-mute,#64748b); text-align:left; border-top:1px solid #eef2f7 }
    .acc-empty__facts li{ position:relative; padding-left:1.35rem }
    .acc-empty__facts li::before{ content:'✓'; position:absolute; left:0; top:0;
        font-weight:700; color:var(--color-primary,#6366f1) }

    .acc-empty__actions{ display:flex; flex-wrap:wrap; gap:.5rem; margin-left:auto }

    /* Кнопки в паре должны быть одинаковыми во всём, кроме заливки.
       Замер показал расхождение: у залитой шрифт 16px и значок 18x16, у
       второй — 13.6px и 15x14. Рядом это читается как небрежность, хотя
       высота у обеих случайно совпадала. Задаём метрики явно. */
    .acc-empty__actions > a{ display:inline-flex; align-items:center; justify-content:center;
        gap:.45rem; height:40px; padding:0 1.1rem; font-size:.875rem; font-weight:600;
        line-height:1; white-space:nowrap }
    /* Ширина значка фиксирована: у разных глифов она своя, и подписи
       съезжали друг относительно друга на пиксель-другой. */
    .acc-empty__actions > a i{ width:1rem; font-size:.9rem; text-align:center; flex:0 0 auto }

    /* Подсказка о незаполненном профиле. */
    .acc-fill-hint{ display:flex; align-items:flex-start; gap:.5rem; margin:1rem 0 0;
        padding-top:.9rem; border-top:1px solid #eef2f7;
        font-size:.8rem; line-height:1.5; color:var(--surface-mute,#6b7280) }
    .acc-fill-hint i{ margin-top:.15rem; color:var(--color-primary,#6366f1) }
    .acc-fill-hint a{ color:var(--color-primary,#6366f1); font-weight:600 }

    :root.dark .acc-empty__facts, :root.dark .acc-fill-hint{ border-color:#374151 }

    .acc-head{ display:flex; align-items:center; gap:.9rem; margin-bottom:1.25rem }
    .acc-avatar{ width:3rem; height:3rem; flex:0 0 auto; display:inline-flex;
                 align-items:center; justify-content:center;
                 background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff;
                 font-weight:800; font-size:1.15rem }

    .acc-stats{ display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
                gap:.6rem; margin-bottom:1rem }
    .acc-stat{ display:flex; align-items:center; gap:.75rem;
               border:1px solid var(--surface-bd,#eef2f7); background:var(--surface,#fff); padding:.7rem .9rem }
    .acc-stat__ico{ display:inline-flex; align-items:center; justify-content:center;
                    width:2.2rem; height:2.2rem; flex:0 0 auto; font-size:.9rem;
                    color:var(--color-primary,#6366f1); background:color-mix(in srgb, var(--color-primary, #6366f1) 10%, transparent) }
    .acc-stat__body{ display:flex; flex-direction:column; min-width:0; line-height:1.2 }
    .acc-stat__value{ font-size:1.3rem; font-weight:800; color:var(--surface-ink,#111827) }
    .acc-stat__label{ font-size:.7rem; text-transform:uppercase;
                      letter-spacing:.06em; color:var(--surface-dim,#9ca3af) }

    /* Действия — строки со значком и пояснением. Ряд одинаковых кнопок не
       говорил, что будет после нажатия, и занимал всю ширину карточки. */
    .acc-links{ display:grid; gap:.4rem }
    .acc-link{ display:flex; align-items:center; gap:.7rem; padding:.6rem .65rem;
               text-decoration:none; color:inherit; border:1px solid var(--surface-bd,#eef2f7);
               transition:border-color .15s, background-color .15s }
    .acc-link:hover{ border-color:var(--color-primary,#6366f1); background:var(--surface-2,#f8fafc) }
    .acc-link__ico{ display:inline-flex; align-items:center; justify-content:center;
                    width:2rem; height:2rem; flex:0 0 auto; font-size:.82rem;
                    color:var(--color-primary,#6366f1); background:color-mix(in srgb, var(--color-primary, #6366f1) 10%, transparent) }
    .acc-link__body{ display:flex; flex-direction:column; min-width:0; line-height:1.3 }
    .acc-link__title{ font-size:.9rem; font-weight:600; color:var(--surface-ink,#111827) }
    .acc-link__note{ font-size:.75rem; color:var(--surface-mute,#6b7280) }
    .acc-link__arrow{ margin-left:auto; font-size:.7rem; color:#cbd5e1 }
    .acc-link:hover .acc-link__arrow{ color:var(--color-primary,#6366f1) }

    /* Состояние строки. Стоит ПЕРЕД стрелкой, поэтому отодвигается влево
       само (стрелка забирает весь свободный отступ через margin-left:auto). */
    /* Приглушённый цвет темы (--surface-mute) давал на этой подложке 4.41 —
       для полужирной подписи в 0.68rem этого мало. Смесь с основным цветом
       текста следует теме так же, но даёт 6.73. */
    /* Плашка состояния целиком выведена из темы. Раньше «Включена» была
       прибита светло-зелёными числами (#15803d на #dcfce7): на тёмной теме
       подложка оставалась светлой, а цвет текста приходил от темы — контраст
       падал до 1.12, надпись читалась как пустой прямоугольник.

       Селектор с родителем не для красоты: одиночный класс проигрывал по
       специфичности правилу темы, из-за чего цвет и не применялся. */
    .acc-link .acc-state{ flex:0 0 auto; margin-left:auto; padding:.12rem .45rem;
                font-size:.68rem; font-weight:700; border-radius:999px; white-space:nowrap;
                color:color-mix(in srgb, var(--surface-ink,#111827) 72%, var(--surface-2,#f1f5f9));
                background:var(--surface-2,#f1f5f9);
                border:1px solid var(--surface-bd,#e5e7eb) }
    .acc-state + .acc-link__arrow{ margin-left:.55rem }

    /* Зелёный подмешивается к цвету текста и к подложке ТЕМЫ, а не задаётся
       готовой парой: на светлой теме получается светло-зелёная плашка с
       тёмной надписью, на тёмной — тёмно-зелёная со светлой. */
    .acc-link .acc-state.is-on{
                color:color-mix(in srgb, #16a34a 45%, var(--surface-ink,#111827));
                background:color-mix(in srgb, #16a34a 16%, var(--surface,#ffffff));
                border-color:color-mix(in srgb, #16a34a 40%, var(--surface,#ffffff)) }

    :root.dark .acc-link{ border-color:#374151 }
    :root.dark .acc-link:hover{ background:#111827 }
    :root.dark .acc-link__title{ color:#f3f4f6 }
    .acc-h2{ font-size:.78rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
             color:var(--surface-dim,#9ca3af); margin-bottom:.9rem }

    .acc-flash{ border:1px solid #bbf7d0; background:#f0fdf4; color:#166534;
                padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem }

    .acc-grid{ display:grid; grid-template-columns:1fr; gap:1rem }
    @media (min-width:900px){ .acc-grid{ grid-template-columns:1.35fr 1fr } }

    .acc-list{ display:grid; gap:.55rem; font-size:.92rem }
    .acc-list > div{ display:flex; gap:.6rem; flex-wrap:wrap; align-items:baseline }
    .acc-list dt{ color:var(--surface-mute,#6b7280); min-width:10rem }
    .acc-list dd{ margin:0; font-weight:600; color:var(--surface-ink,#111827) }
    .acc-list a{ color:var(--color-primary, #4f46e5) }

    .acc-actions{ display:flex; flex-wrap:wrap; gap:.5rem; align-items:stretch }
    /* Кнопки раздела задаём сами: общий .fx-btn рисовался под короткое
       «Подробнее», и длинная подпись вылезала за его фон. */
    .acc-actions .fx-btn,
    .acc-actions .acc-btn-ghost{
        display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
        padding:.6rem 1.1rem; line-height:1.25; white-space:nowrap;
        width:auto; min-width:0; max-width:100%; text-align:center;
    }
    @media (max-width:520px){
        .acc-actions{ flex-direction:column; align-items:stretch }
        .acc-actions .fx-btn,
        .acc-actions .acc-btn-ghost{ white-space:normal; width:100% }
    }
    .acc-btn-ghost{ display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1rem;
                    border:1px solid var(--surface-bd,#e5e7eb); background:var(--surface,#fff); color:var(--surface-ink,#374151);
                    font-size:.85rem; font-weight:600; transition:border-color .15s, color .15s }
    .acc-btn-ghost:hover{ border-color:color-mix(in srgb, var(--color-primary,#6366f1) 55%, var(--surface,#fff)); color:var(--color-primary, #4f46e5) }
    .acc-count{ margin-top:.9rem; font-size:.85rem; color:var(--surface-mute,#6b7280) }

    .acc-orders-head{ display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.9rem }
    .acc-all{ font-size:.85rem; font-weight:600; color:var(--color-primary, #4f46e5) }

    .acc-order{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;
                border:1px solid var(--surface-bd,#eef2f7); padding:.85rem 1rem; margin-bottom:.6rem; background:var(--surface,#fff) }
    .acc-order:hover{ border-color:#c7d2fe }
    .acc-order__top{ display:flex; align-items:center; gap:.6rem; flex-wrap:wrap }
    .acc-order__meta{ display:flex; flex-wrap:wrap; gap:.25rem 1rem; margin-top:.35rem;
                      font-size:.8rem; color:var(--surface-mute,#6b7280) }
    .acc-order__sum{ text-align:right; white-space:nowrap }
    .acc-order__sum-label{ display:block; font-size:.72rem; color:var(--surface-dim,#9ca3af) }
    .acc-order__sum b{ font-size:1.05rem; color:var(--surface-ink,#111827) }

    .acc-status{ font-size:.72rem; font-weight:700; padding:.15rem .5rem; border:1px solid }
    .acc-status--ok{ color:color-mix(in srgb, #16a34a 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #16a34a 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #16a34a 34%, var(--surface,#fff)) }
    .acc-status--wait{ color:color-mix(in srgb, #d97706 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #d97706 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #d97706 34%, var(--surface,#fff)) }
    .acc-status--bad{ color:color-mix(in srgb, #dc2626 55%, var(--surface-ink,#111827));
        background:color-mix(in srgb, #dc2626 16%, var(--surface,#fff));
        border-color:color-mix(in srgb, #dc2626 34%, var(--surface,#fff)) }

    .acc-empty{ text-align:center; padding:2.5rem 1rem }
    .acc-empty__title{ font-weight:700; color:var(--surface-ink,#111827); margin:.9rem 0 .25rem }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) —
       это настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не тема сайта. При
       тёмной системе и светлой теме он перекрашивал кнопку «Назад»
       в #d1d5db на белой подложке, и надпись почти не читалась.
       Правила выше и так выведены из переменных темы. */
</style>
@endpush
