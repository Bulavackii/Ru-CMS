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

        {{-- ── Основное ── --}}
        <section class="fx-card p-5 mb-4">
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
        <section class="fx-card p-5 mb-4">
            <h2 class="acc-h2"><i class="fas fa-address-book fx-ico"></i> {{ __('frontend.account.g_contacts') }}</h2>

            <div class="acc-fields">
                <x-dashboard.input name="phone" :label="__('frontend.account.f_phone')"
                    :value="old('phone', $user->phone)" />
                <x-dashboard.input name="telegram" label="Telegram"
                    :value="old('telegram', $user->telegram)" />
                <x-dashboard.input name="whatsapp" label="WhatsApp"
                    :value="old('whatsapp', $user->whatsapp)" />
                <x-dashboard.input name="vk" label="VK"
                    :value="old('vk', $user->vk)" />
            </div>
        </section>

        {{-- ── Реквизиты организации ──
             Блок раскрывается галочкой. Раньше переключение делал отдельный
             скрипт по id; теперь это Alpine, как в остальном проекте. --}}
        <section class="fx-card p-5 mb-4">
            <label class="acc-check">
                <input type="hidden" name="is_company" value="0">
                <input type="checkbox" name="is_company" value="1" x-model="company">
                <span>{{ __('frontend.account.legal_entity') }}</span>
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
               margin:0 auto 1.25rem; max-width:52rem; text-align:left }
    .acc-h2{ font-size:.78rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
             color:#9ca3af; margin-bottom:.9rem }
    .acc-form{ max-width:52rem; margin-inline:auto }

    .acc-flash{ border:1px solid #bbf7d0; background:#f0fdf4; color:#166534;
                padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem }
    .acc-flash--bad{ border-color:#fecaca; background:#fef2f2; color:#991b1b }

    /* Два поля в ряд на десктопе — форма из десяти полей в одну колонку
       вынуждала прокручивать страницу целиком. */
    .acc-fields{ display:grid; grid-template-columns:1fr; gap:.85rem }
    @media (min-width:680px){ .acc-fields{ grid-template-columns:1fr 1fr } }
    .acc-fields__wide{ grid-column:1 / -1 }

    .acc-check{ display:inline-flex; align-items:center; gap:.6rem; cursor:pointer;
                font-size:.92rem; color:#374151 }

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
    .acc-btn-ghost{ border:1px solid #e5e7eb; background:#fff; color:#374151;
                    transition:border-color .15s, color .15s, background .15s }
    .acc-btn-ghost:hover{ border-color:#a5b4fc; color:#4f46e5; background:#f8fafc }
    .acc-actions .fx-btn:active,
    .acc-actions .acc-btn-ghost:active{ transform:translateY(1px) }

    @media (max-width:520px){
        .acc-actions{ flex-direction:column; align-items:stretch }
        .acc-actions .fx-btn,
        .acc-actions .acc-btn-ghost{ white-space:normal; width:100% }
    }

    @media (prefers-color-scheme: dark){
        .acc-check{ color:#d1d5db }
        .acc-btn-ghost{ background:transparent; border-color:#374151; color:#d1d5db }
    }
</style>
@endpush
