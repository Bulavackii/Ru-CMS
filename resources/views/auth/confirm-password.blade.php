@extends('layouts.guest')

@section('title', __('frontend.auth.confirm_title'))
@section('heading', __('frontend.auth.confirm_title'))
@section('lead', __('frontend.auth.confirm_lead'))

@section('aside_title', __('frontend.auth.aside_confirm_title'))
@section('aside_text', __('frontend.auth.aside_confirm_text'))

@section('content')
    @error('password')
        <div class="au-note au-note--bad">
            <i class="fas fa-circle-exclamation"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="au-field">
            <label class="au-label" for="password">{{ __('frontend.auth.password') }}</label>
            <div class="au-with-btn">
                <input id="password" type="password" name="password" required autofocus
                       autocomplete="current-password"
                       class="au-input @error('password') is-bad @enderror">
                <button type="button" class="au-eye" aria-label="{{ __('frontend.account.pw_show') }}">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-shield-halved"></i> {{ __('frontend.auth.confirm_do') }}
        </button>
    </form>
@endsection
