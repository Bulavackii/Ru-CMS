@extends('layouts.guest')

@section('title', __('frontend.auth.tfa_status_title'))
@section('heading', __('frontend.auth.tfa_status_title'))
@section('lead', __('frontend.auth.tfa_status_lead'))

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

    @if(auth()->user()->two_factor_enabled)
        <div class="au-note au-note--ok">
            <i class="fas fa-shield-halved"></i>
            <span>{{ __('frontend.auth.tfa_on') }}</span>
        </div>

        <form method="POST" action="{{ route('two-factor.disable') }}">
            @csrf

            <div class="au-field">
                <label class="au-label" for="password">{{ __('frontend.auth.tfa_confirm_pass') }}</label>
                <div class="au-with-btn">
                    <input id="password" type="password" name="password" required
                           autocomplete="current-password"
                           class="au-input @error('password') is-bad @enderror">
                    <button type="button" class="au-eye" aria-label="{{ __('frontend.account.pw_show') }}">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <span class="au-hint">{{ __('frontend.auth.tfa_off_hint') }}</span>
            </div>

            <button type="submit" class="au-btn au-btn--ghost">
                <i class="fas fa-shield-halved"></i> {{ __('frontend.auth.tfa_off') }}
            </button>
        </form>
    @else
        <div class="au-note au-note--info">
            <i class="fas fa-circle-info"></i>
            <span>{{ __('frontend.auth.tfa_off_now') }}</span>
        </div>

        <a href="{{ route('two-factor.setup') }}" class="au-btn">
            <i class="fas fa-shield-halved"></i> {{ __('frontend.auth.tfa_setup') }}
        </a>
    @endif
@endsection

@section('under')
    <a href="{{ route('dashboard') }}">{{ __('frontend.auth.to_dashboard') }}</a>
@endsection
