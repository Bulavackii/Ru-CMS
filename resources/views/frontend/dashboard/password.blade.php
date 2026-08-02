@extends('layouts.frontend')

@section('title', __('frontend.account.change_password'))

@section('content')
<div class="acc-head">
    <span class="fx-badge"><i class="fas fa-lock"></i></span>
    <div class="min-w-0">
        <h1 class="fx-section-title">{{ __('frontend.account.change_password') }}</h1>
        <p class="fx-section-sub">{{ __('frontend.account.pass_hint') }}</p>
    </div>
</div>

<div class="acc-form">
    <section class="fx-card p-5">
        @if (session('success'))
            <div class="acc-flash">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="acc-flash acc-flash--bad">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-dashboard.input name="current_password" type="password"
                :label="__('frontend.account.current_pass')" required />
            <x-dashboard.input name="new_password" type="password"
                :label="__('frontend.account.new_pass')" required />
            <x-dashboard.input name="new_password_confirmation" type="password"
                :label="__('frontend.account.confirm_pass')" required />

            <div class="acc-actions">
                <button type="submit" class="fx-btn">
                    <i class="fas fa-floppy-disk"></i> {{ __('frontend.account.change_password') }}
                </button>

                <a href="{{ route('dashboard') }}" class="acc-btn-ghost">
                    {{ __('frontend.common.back') }}
                </a>
            </div>
        </form>
    </section>
</div>
@endsection

@push('styles')
<style>
    /* Общие для страниц кабинета классы продублированы здесь: общего
       партиала под них в проекте нет. */
    .acc-head{ display:flex; align-items:center; justify-content:center; gap:.9rem;
               margin:0 auto 1.25rem; max-width:38rem; text-align:left }
    .acc-form{ max-width:38rem; margin-inline:auto }
    .acc-flash{ border:1px solid #bbf7d0; background:#f0fdf4; color:#166534;
                padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem }
    .acc-flash--bad{ border-color:#fecaca; background:#fef2f2; color:#991b1b }
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
</style>
@endpush
