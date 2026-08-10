@extends('layouts.guest')

@section('title', __('frontend.auth.login_title'))
@section('eyebrow', __('frontend.auth.eyebrow_login'))
@section('heading', __('frontend.auth.login_title'))
@section('lead', __('frontend.auth.login_lead'))

@section('aside_title', __('frontend.auth.aside_title'))
@section('aside_text', __('frontend.auth.aside_text'))

@section('content')
    @if (session('status'))
        <div class="au-note au-note--ok">
            <i class="fas fa-circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="au-note au-note--bad">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <strong>{{ __('frontend.auth.login_failed') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Полоса шагов. Второй шаг становится ссылкой, только когда он
         действительно начат: пароль принят и ждём код. Иначе вернувшийся
         на форму набирал бы пароль заново. --}}
    @include('auth.partials.sign-in-flow', [
        'step' => 1,
        'link' => session()->has('login.id') ? route('two-factor.login') : null,
    ])

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="au-field">
            <label class="au-label" for="email">{{ __('frontend.auth.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   placeholder="{{ __('frontend.auth.email_ph') }}"
                   class="au-input @error('email') is-bad @enderror">
            @error('email')<span class="au-err">{{ $message }}</span>@enderror
        </div>

        <div class="au-field">
            <label class="au-label" for="password">{{ __('frontend.auth.password') }}</label>
            <div class="au-with-btn">
                <input id="password" type="password" name="password"
                       required autocomplete="current-password"
                       placeholder="{{ __('frontend.auth.password_ph') }}"
                       class="au-input @error('password') is-bad @enderror">
                <button type="button" class="au-eye" aria-label="{{ __('frontend.account.pw_show') }}">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')<span class="au-err">{{ $message }}</span>@enderror
        </div>

        <div class="au-row">
            <label class="au-check">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <span>{{ __('frontend.auth.remember') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="au-link">{{ __('frontend.auth.forgot') }}</a>
            @endif
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-right-to-bracket"></i> {{ __('frontend.auth.login_do') }}
        </button>
    </form>
@endsection

@section('under')
    {{-- Пилюля «Вход по коду» отсюда убрана: тот же переход теперь живёт в
         полосе шагов над формой, где он на своём месте — вторым шагом
         последовательности, а не сноской рядом с регистрацией. --}}
    {{ __('frontend.auth.no_account') }}
    <a href="{{ route('register') }}">{{ __('frontend.auth.register_do') }}</a>
@endsection
