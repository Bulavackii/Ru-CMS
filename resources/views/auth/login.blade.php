@extends('layouts.guest')

@section('title', __('frontend.auth.login_title'))
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
    {{-- Вход по коду из приложения. Код — ВТОРОЙ рубеж, а не замена паролю,
         поэтому ссылка ведёт на тот же шаг проверки, а не в обход формы:
         если пароль уже введён и мы ждём код, она возвращает на его ввод
         (иначе вернувшийся сюда набирал бы пароль заново); если нет —
         страница проверки сама скажет, что сперва нужны почта и пароль. --}}
    <a href="{{ route('two-factor.login') }}" class="au-tfa-entry">
        <i class="fas fa-shield-halved"></i>
        <span>
            {{ session()->has('login.id')
                ? __('frontend.auth.tfa_continue')
                : __('frontend.auth.tfa_entry') }}
        </span>
    </a>

    <span class="au-under-sep">
        {{ __('frontend.auth.no_account') }}
        <a href="{{ route('register') }}">{{ __('frontend.auth.register_do') }}</a>
    </span>
@endsection

@push('styles')
<style>
    /* Селектор с родителем не для красоты: у лейаута есть `.au-foot a`
       с индиго, и по специфичности оно перебивало одиночный класс —
       ссылка выходила индиговой с контрастом 4.27. */
    .au-foot .au-tfa-entry { display: flex; align-items: center; justify-content: center; gap: 7px;
                    margin-bottom: 9px; font-size: .8rem; text-decoration: none;
                    color: var(--au-text) }
    .au-tfa-entry i { color: var(--au-primary) }
    .au-tfa-entry:hover { text-decoration: underline }
    .au-under-sep { display: block }
</style>
@endpush
