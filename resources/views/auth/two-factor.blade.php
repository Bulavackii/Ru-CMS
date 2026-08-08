@extends('layouts.guest')

@section('title', __('frontend.auth.tfa_title'))
@section('heading', __('frontend.auth.tfa_title'))
@section('lead', __('frontend.auth.tfa_lead'))

@section('aside_title', __('frontend.auth.aside_tfa_title'))
@section('aside_text', __('frontend.auth.aside_tfa_text'))

@section('content')
    @if ($errors->any())
        <div class="au-note au-note--bad">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div class="au-field">
            <label class="au-label" for="code">{{ __('frontend.auth.tfa_code') }}</label>
            {{-- Код цифровой: inputmode подсказывает телефону показать
                 цифровую клавиатуру, autocomplete — подставить код из СМС. --}}
            <input id="code" type="text" name="code" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="8" placeholder="000000"
                   class="au-input au-code @error('code') is-bad @enderror">
            <span class="au-hint">{{ __('frontend.auth.tfa_hint') }}</span>
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-shield-halved"></i> {{ __('frontend.auth.tfa_do') }}
        </button>
    </form>
@endsection

@section('under')
    <a href="{{ route('login') }}">{{ __('frontend.auth.back_to_login') }}</a>
@endsection

@push('styles')
<style>
    /* Код читается по цифрам — моноширинный шрифт и разрядка. */
    .au-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
               font-size: 1.25rem; letter-spacing: .28em; text-align: center }
</style>
@endpush
