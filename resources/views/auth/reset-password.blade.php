@extends('layouts.guest')

@section('title', __('frontend.auth.reset_title'))
@section('heading', __('frontend.auth.reset_title'))
@section('lead', __('frontend.auth.reset_lead'))

@section('aside_title', __('frontend.auth.aside_reset_title'))
@section('aside_text', __('frontend.auth.aside_reset_text'))

@section('content')
    @if ($errors->any())
        <div class="au-note au-note--bad">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <strong>{{ __('admin.common.check_form') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" x-data="resetForm()">
        @csrf

        {{-- Токен из письма. Он одноразовый и живёт час — если человек открыл
             ссылку и ушёл, форма честно скажет об этом после отправки. --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="au-field">
            <label class="au-label" for="email">{{ __('frontend.auth.email') }}</label>
            <input id="email" type="email" name="email"
                   value="{{ old('email', $request->email) }}"
                   required readonly autocomplete="username"
                   class="au-input @error('email') is-bad @enderror">
            <span class="au-hint">{{ __('frontend.auth.reset_email_hint') }}</span>
        </div>

        <div class="au-field">
            <label class="au-label" for="password">{{ __('frontend.account.new_pass') }}<span class="au-req">*</span></label>
            <div class="au-with-btn">
                <input id="password" type="password" name="password" required autofocus
                       autocomplete="new-password" x-model="password"
                       class="au-input @error('password') is-bad @enderror">
                <button type="button" class="au-eye" aria-label="{{ __('frontend.account.pw_show') }}">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="au-strength" :class="'lv' + level"><span></span><span></span><span></span><span></span></div>
            <span class="au-hint" x-text="levelText"></span>
        </div>

        <div class="au-field">
            <label class="au-label" for="password_confirmation">{{ __('frontend.account.confirm_pass') }}<span class="au-req">*</span></label>
            <div class="au-with-btn">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       required autocomplete="new-password" x-model="confirmation" class="au-input">
                <button type="button" class="au-eye" aria-label="{{ __('frontend.account.pw_show') }}">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <span class="au-hint" x-show="confirmation.length > 0" x-cloak
                  :style="matches ? 'color:#16a34a' : 'color:#dc2626'"
                  x-text="matches ? @js(__('frontend.account.pw_match')) : @js(__('frontend.account.pw_mismatch'))"></span>
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-key"></i> {{ __('frontend.auth.reset_do') }}
        </button>
    </form>
@endsection

@section('under')
    <a href="{{ route('login') }}">{{ __('frontend.auth.back_to_login') }}</a>
@endsection

@push('scripts')
<script>
    function resetForm() {
        return {
            password: '',
            confirmation: '',

            get matches() {
                return this.confirmation.length > 0 && this.password === this.confirmation;
            },

            /** Подсказка человеку; настоящую проверку делает сервер. */
            get level() {
                var value = this.password;

                if (!value) {
                    return 0;
                }

                var score = 0;

                if (value.length >= 8) score++;
                if (value.length >= 12) score++;
                if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
                if (/[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value)) score++;

                return Math.min(score, 4);
            },

            get levelText() {
                return [
                    '',
                    @js(__('frontend.account.pw_weak')),
                    @js(__('frontend.account.pw_fair')),
                    @js(__('frontend.account.pw_good')),
                    @js(__('frontend.account.pw_strong'))
                ][this.level];
            }
        };
    }
</script>
@endpush
