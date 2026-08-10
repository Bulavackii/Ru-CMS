@extends('layouts.frontend')

@section('title', __('frontend.account.edit_profile'))

@section('content')
<div class="acc-head">
    <span class="fx-badge"><i class="fas fa-pen"></i></span>
    <div class="min-w-0">
        <h1 class="fx-section-title">{{ __('frontend.account.edit_profile') }}</h1>
        <p class="fx-section-sub">{{ __('frontend.account.edit_hint') }}</p>
    </div>
</div>

<div class="acc-form" x-data="{ company: {{ old('is_company', $user->is_company) ? 'true' : 'false' }} }">
    @if ($errors->any())
        <div class="acc-flash acc-flash--bad">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('dashboard.update') }}">
        @csrf
        @method('PUT')

        {{-- Профиль и контакты — рядом: в них по два-три поля, и вытянутые
             друг под другом они гнали страницу вниз, оставляя половину
             ширины пустой. Реквизиты организации остаются во всю ширину —
             там шесть полей и блок раскрывается галочкой. --}}
        <div class="acc-cols">

        {{-- ── Основное ── --}}
        <section class="fx-card p-5">
            <h2 class="acc-h2"><i class="fas fa-id-card fx-ico"></i> {{ __('frontend.account.profile') }}</h2>

            <div class="acc-fields">
                <x-dashboard.input name="name" :label="__('frontend.account.name')" required
                    :value="old('name', $user->name)" />
                <x-dashboard.input name="zip" :label="__('frontend.account.f_zip')"
                    :value="old('zip', $user->zip)" />
                <div class="acc-fields__wide">
                    <x-dashboard.input name="address" :label="__('frontend.account.f_address')"
                        :value="old('address', $user->address)" />
                </div>
            </div>
        </section>

        {{-- ── Контакты ── --}}
        <section class="fx-card p-5">
            <h2 class="acc-h2"><i class="fas fa-address-book fx-ico"></i> {{ __('frontend.account.g_contacts') }}</h2>

            <div class="acc-fields">
                <x-dashboard.input name="phone" :label="__('frontend.account.f_phone')"
                    :value="old('phone', $user->phone)" />
                {{-- Адреса страниц в сетях. Подпись поясняет формат: без неё
                     вписывали короткое имя без адреса, и проверка отклоняла
                     ввод без внятной причины. --}}
                <x-dashboard.input name="vk" type="url" label="ВКонтакте"
                    :value="old('vk', $user->vk)"
                    :hint="__('frontend.account.vk_hint')" />
                <x-dashboard.input name="max" type="url" label="MAX"
                    :value="old('max', $user->max)"
                    :hint="__('frontend.account.max_hint')" />
            </div>
        </section>

        </div>

        {{-- ── Реквизиты организации ──
             Блок раскрывается галочкой. Раньше переключение делал отдельный
             скрипт по id; теперь это Alpine, как в остальном проекте. --}}
        <section class="fx-card p-5 mb-4">
            {{-- Тумблер вместо галочки: он раскрывает целый блок из шести
                 полей, а не отмечает признак, — и в остальных разделах
                 проекта такие переключатели уже тумблеры. --}}
            <label class="acc-switch">
                <input type="hidden" name="is_company" value="0">
                <span class="admin-toggle">
                    <input type="checkbox" name="is_company" value="1" x-model="company">
                    <span class="track"></span><span class="knob"></span>
                </span>
                <span class="acc-switch__body">
                    <span class="acc-switch__title">{{ __('frontend.account.legal_entity') }}</span>
                    <span class="acc-switch__note">{{ __('frontend.account.legal_entity_hint') }}</span>
                </span>
            </label>

            <div x-cloak x-show="company" class="mt-4">
                <h2 class="acc-h2"><i class="fas fa-building fx-ico"></i> {{ __('frontend.account.g_company') }}</h2>

                <div class="acc-fields">
                    <x-dashboard.input name="company_name" :label="__('frontend.account.org_name')"
                        :value="old('company_name', $user->company_name)" />
                    <x-dashboard.input name="ceo" :label="__('frontend.account.f_ceo')"
                        :value="old('ceo', $user->ceo)" />
                    <x-dashboard.input name="inn" :label="__('frontend.account.inn')"
                        :value="old('inn', $user->inn)" />
                    <x-dashboard.input name="ogrn" :label="__('frontend.account.ogrn')"
                        :value="old('ogrn', $user->ogrn)" />
                    <x-dashboard.input name="okato" :label="__('frontend.account.f_okato')"
                        :value="old('okato', $user->okato)" />
                    <div class="acc-fields__wide">
                        <x-dashboard.input name="address_legal" :label="__('frontend.account.f_legal_addr')"
                            :value="old('address_legal', $user->address_legal)" />
                    </div>
                    <div class="acc-fields__wide">
                        <x-dashboard.input name="address_actual" :label="__('frontend.account.f_actual_addr')"
                            :value="old('address_actual', $user->address_actual)" />
                    </div>
                </div>
            </div>
        </section>

        <div class="acc-actions">
            <button type="submit" class="fx-btn">
                <i class="fas fa-floppy-disk"></i> {{ __('frontend.account.save') }}
            </button>

            <a href="{{ route('dashboard') }}" class="acc-btn-ghost">
                {{ __('frontend.common.back') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    /* Литеральный CSS. Общего партиала под стили кабинета в проекте нет,
       поэтому каждая страница несёт свои. */
    .acc-head{ display:flex; align-items:center; justify-content:center; gap:.9rem;
               margin:0 auto 1.25rem; max-width:var(--acc-w); text-align:left }
    .acc-h2{ font-size:.78rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
             color:var(--surface-dim,#9ca3af); margin-bottom:.9rem }
    /* Ширина колонки. 52rem задавалось под ОДИН столбец карточек; когда
       карточек стало две, при них оставалось по 400 пикселей, а по краям
       экрана — пустота в треть ширины. Теперь ширина растёт вместе с
       окном, но не безгранично: строка ввода шире 700 пикселей читается
       хуже, а не лучше. */
    .acc-form{ max-width:var(--acc-w); margin-inline:auto }

    .acc-flash{ border:1px solid #bbf7d0; background:#f0fdf4; color:#166534;
                padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem }
    .acc-flash--bad{ border-color:#fecaca; background:#fef2f2; color:#991b1b }

    /* Два поля в ряд на десктопе — форма из десяти полей в одну колонку
       вынуждала прокручивать страницу целиком. */
    /* Две колонки карточек на широком экране; ниже — обычным столбцом.
       `align-items:start` обязателен: иначе карточки растягиваются до
       высоты соседней, и под короткими полями висит пустая рамка. */
/* Тумблер раскрытия блока реквизитов. Тумблер `.admin-toggle` объявлен
       в лейауте панели, на сайте его нет — повторяем правила здесь, цвет
       берём из темы. */
    .acc-switch{ display:flex; align-items:flex-start; gap:.7rem; cursor:pointer }
    .acc-switch__body{ display:flex; flex-direction:column; gap:.15rem; line-height:1.35 }
    .acc-switch__title{ font-size:.92rem; font-weight:600; color:var(--surface-ink,#111827) }
    .acc-switch__note{ font-size:.76rem; color:var(--surface-mute,#6b7280) }

    .admin-toggle{ position:relative; display:inline-block; width:2.5rem; height:1.4rem; flex:none }
    .admin-toggle input{ position:absolute; inset:0; width:100%; height:100%; opacity:0; margin:0; cursor:pointer; z-index:2 }
    .admin-toggle .track{ position:absolute; inset:0; background:var(--surface-bd,#cbd5e1); transition:background .2s }
    .admin-toggle .knob{ position:absolute; top:2px; left:2px; width:calc(1.4rem - 4px); height:calc(1.4rem - 4px);
                         background:var(--surface,#fff); transition:left .2s; box-shadow:0 1px 2px rgba(0,0,0,.25); pointer-events:none }
    .admin-toggle input:checked ~ .track{ background:var(--color-primary,#6366f1) }
    .admin-toggle input:checked ~ .knob{ left:calc(100% - 1.4rem + 2px) }

    /* Один источник ширины на шапку и форму: раньше 52rem стояло в двух
       местах и разъехалось бы при первой же правке. */
/* ── Типографика как на страницах входа ───────────────────────────
       Подписи полей и заголовки разделов — моноширинным, мелко, капсом,
       с крупным просветом. Второй «шрифт» бесплатный: системный
       моноширинный стек уже показывает в проекте ключи и коды, ничего
       дозагружать не нужно. Заголовок страницы — крупнее и с плотным
       трекингом, как «Вход». */

    .fx-section-title{ font-size:clamp(1.5rem, 3.4vw, 1.95rem); line-height:1.08; letter-spacing:-.03em }

    .acc-h2{ font-family:ui-monospace, SFMono-Regular, Menlo, monospace;
             font-size:.68rem; letter-spacing:.12em }

    .acc-fields label{ font-family:ui-monospace, SFMono-Regular, Menlo, monospace;
                       font-size:.66rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
                       color:color-mix(in srgb, var(--surface-ink,#111827) 74%, var(--surface,#fff)) }

    .acc-form, .acc-head{ --acc-w:52rem }
    @media (min-width:1280px){ .acc-form, .acc-head{ --acc-w:68rem } }
    @media (min-width:1600px){ .acc-form, .acc-head{ --acc-w:78rem } }

    .acc-cols{ display:grid; grid-template-columns:1fr; gap:1rem; margin-bottom:1rem; align-items:start }
    @media (min-width:900px){ .acc-cols{ grid-template-columns:1fr 1fr } }

    .acc-fields{ display:grid; grid-template-columns:1fr; gap:.85rem }
    @media (min-width:680px){ .acc-fields{ grid-template-columns:1fr 1fr } }

    /* ⚠️ Внутри двух колонок поля НЕ делятся надвое.
       Порог по ширине окна здесь не работает: карточка вдвое уже окна, и
       на широком экране «Телефон» с «ВКонтакте» вставали рядом по 180px —
       у ссылки обрезался заполнитель («https://vk.com/exa»), а подсказка
       уезжала в три строки. Ширину окна карточка не знает, поэтому проще
       сказать прямо: в этих карточках поля идут по одному в ряд. */
    .acc-cols .acc-fields{ grid-template-columns:1fr }
    .acc-fields__wide{ grid-column:1 / -1 }

    .acc-check{ display:inline-flex; align-items:center; gap:.6rem; cursor:pointer;
                font-size:.92rem; color:var(--surface-ink,#374151) }

    /* Кнопки страницы. Общий .fx-btn рисовался под короткое «Подробнее»:
       длинная подпись вылезала за его фон, поэтому размеры задаём сами. */
    .acc-actions{ display:flex; flex-wrap:wrap; gap:.6rem; align-items:stretch;
                  justify-content:center; margin-top:.25rem }
    .acc-actions .fx-btn,
    .acc-actions .acc-btn-ghost{
        display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
        padding:.65rem 1.4rem; line-height:1.25; white-space:nowrap; width:auto;
        font-size:.88rem; font-weight:600;
    }
    .acc-btn-ghost{ border:1px solid var(--surface-bd,#e5e7eb); background:var(--surface,#fff); color:var(--surface-ink,#374151);
                    transition:border-color .15s, color .15s, background .15s }
    .acc-btn-ghost:hover{ border-color:color-mix(in srgb, var(--color-primary,#6366f1) 55%, var(--surface,#fff)); color:var(--color-primary, #4f46e5); background:var(--surface-2,#f8fafc) }
    .acc-actions .fx-btn:active,
    .acc-actions .acc-btn-ghost:active{ transform:translateY(1px) }

    @media (max-width:520px){
        .acc-actions{ flex-direction:column; align-items:stretch }
        .acc-actions .fx-btn,
        .acc-actions .acc-btn-ghost{ white-space:normal; width:100% }
    }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) —
       это настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не тема сайта. При
       тёмной системе и светлой теме он перекрашивал кнопку «Назад»
       в #d1d5db на белой подложке, и надпись почти не читалась.
       Правила выше и так выведены из переменных темы. */
</style>
@endpush
