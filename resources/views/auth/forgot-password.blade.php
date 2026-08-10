@extends('layouts.guest')

@section('title', __('frontend.auth.forgot_title'))
@section('eyebrow', __('frontend.auth.eyebrow_forgot'))
@section('heading', __('frontend.auth.forgot_title'))
@section('lead', __('frontend.auth.forgot_lead'))

@section('aside_title', __('frontend.auth.aside_forgot_title'))
@section('aside_text', __('frontend.auth.aside_forgot_text'))

@section('content')
    @if (session('status'))
        <div class="au-note au-note--ok">
            <i class="fas fa-paper-plane"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @error('email')
        <div class="au-note au-note--bad">
            <i class="fas fa-circle-exclamation"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="au-field">
            <label class="au-label" for="email">{{ __('frontend.auth.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   placeholder="{{ __('frontend.auth.email_ph') }}"
                   class="au-input @error('email') is-bad @enderror">
            <span class="au-hint">{{ __('frontend.auth.forgot_hint') }}</span>
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-paper-plane"></i> {{ __('frontend.auth.forgot_do') }}
        </button>
    </form>
@endsection

@section('under')
    {{ __('frontend.auth.remembered') }}
    <a href="{{ route('login') }}">{{ __('frontend.auth.login_do') }}</a>
@endsection
