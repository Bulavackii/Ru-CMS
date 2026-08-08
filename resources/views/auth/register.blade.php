{{--
    Регистрация.

    Прежняя форма спрашивала реквизиты организации полями is_legal, org_name и
    kpp — таких колонок в users нет вовсе (там is_company, company_name, inn,
    ogrn, ceo), а контроллер сохранял только имя, почту и пароль. То есть всё,
    что человек вводил про компанию, молча выбрасывалось. Здесь имена полей
    приведены к схеме, а контроллер их принимает и сохраняет.
--}}
@extends('layouts.guest')

@section('title', __('frontend.auth.register_title'))
@section('heading', __('frontend.auth.register_title'))
@section('lead', __('frontend.auth.register_lead'))

@section('aside_title', __('frontend.auth.aside_reg_title'))
@section('aside_text', __('frontend.auth.aside_reg_text'))

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

    <form method="POST" action="{{ route('register') }}"
          x-data="registerForm({{ old('is_company') ? 'true' : 'false' }})">
        @csrf

        {{-- Кто регистрируется. Переключатель наверху: от него зависит, что
             спрашивать дальше, и узнать это надо до заполнения. --}}
        <div class="au-field">
            <span class="au-label">{{ __('frontend.account.user_type') }}</span>
            <div class="au-switch">
                <label class="au-switch-item" :class="{ 'is-on': !company }">
                    <input type="radio" name="is_company" value="0" x-model="companyRaw">
                    <i class="fas fa-user"></i> {{ __('frontend.account.individual') }}
                </label>
                <label class="au-switch-item" :class="{ 'is-on': company }">
                    <input type="radio" name="is_company" value="1" x-model="companyRaw">
                    <i class="fas fa-building"></i> {{ __('frontend.account.legal_entity_type') }}
                </label>
            </div>
        </div>

        <div class="au-field">
            <label class="au-label" for="name">{{ __('frontend.account.name') }}<span class="au-req">*</span></label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   required autofocus autocomplete="name"
                   placeholder="{{ __('frontend.auth.name_ph') }}"
                   class="au-input @error('name') is-bad @enderror">
            @error('name')<span class="au-err">{{ $message }}</span>@enderror
        </div>

        <div class="au-grid au-grid--2">
            <div class="au-field">
                <label class="au-label" for="email">{{ __('frontend.auth.email') }}<span class="au-req">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="email"
                       placeholder="{{ __('frontend.auth.email_ph') }}"
                       class="au-input @error('email') is-bad @enderror">
                @error('email')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            <div class="au-field">
                <label class="au-label" for="phone">{{ __('frontend.account.f_phone') }}</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                       autocomplete="tel" placeholder="+7 900 000-00-00"
                       class="au-input @error('phone') is-bad @enderror">
                @error('phone')<span class="au-err">{{ $message }}</span>@enderror
            </div>
        </div>

        {{-- Реквизиты. Показываются только юрлицу: физлицу они не нужны, а
             форма без них короче вдвое. --}}
        <div x-show="company" x-cloak>
            <div class="au-split">{{ __('frontend.account.g_company') }}</div>

            <div class="au-field">
                <label class="au-label" for="company_name">{{ __('frontend.account.org_name') }}<span class="au-req">*</span></label>
                <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}"
                       :required="company" placeholder="{{ __('frontend.auth.company_ph') }}"
                       class="au-input @error('company_name') is-bad @enderror">
                @error('company_name')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            <div class="au-grid au-grid--2">
                <div class="au-field">
                    <label class="au-label" for="inn">{{ __('frontend.account.inn') }}<span class="au-req">*</span></label>
                    <input id="inn" type="text" name="inn" value="{{ old('inn') }}"
                           :required="company" inputmode="numeric" maxlength="12"
                           class="au-input @error('inn') is-bad @enderror">
                    @error('inn')<span class="au-err">{{ $message }}</span>@enderror
                </div>

                <div class="au-field">
                    <label class="au-label" for="ogrn">{{ __('frontend.account.ogrn') }}</label>
                    <input id="ogrn" type="text" name="ogrn" value="{{ old('ogrn') }}"
                           inputmode="numeric" maxlength="15"
                           class="au-input @error('ogrn') is-bad @enderror">
                    @error('ogrn')<span class="au-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="au-split">{{ __('frontend.auth.g_access') }}</div>

        <div class="au-field">
            <label class="au-label" for="password">{{ __('frontend.auth.password') }}<span class="au-req">*</span></label>
            <div class="au-with-btn">
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       x-model="password"
                       class="au-input @error('password') is-bad @enderror">
                <button type="button" class="au-eye" aria-label="{{ __('frontend.account.pw_show') }}">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="au-strength" :class="'lv' + level"><span></span><span></span><span></span><span></span></div>
            <span class="au-hint" x-text="levelText"></span>
            @error('password')<span class="au-err">{{ $message }}</span>@enderror
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

        @if(config('captcha.enabled', true) && function_exists('captcha_field'))
            <div class="au-field">
                {!! captcha_field(config('captcha.default_type', 'image')) !!}
                @error('captcha')<span class="au-err">{{ $message }}</span>@enderror
            </div>
        @endif

        <label class="au-check" style="margin-bottom:16px">
            <input type="checkbox" name="terms_agree" value="1" required {{ old('terms_agree') ? 'checked' : '' }}>
            <span>
                {!! __('frontend.auth.terms', [
                    'terms'   => '<a class="au-link" href="' . url('/terms') . '" target="_blank" rel="noopener">' . __('frontend.auth.terms_link') . '</a>',
                    'privacy' => '<a class="au-link" href="' . url('/privacy') . '" target="_blank" rel="noopener">' . __('frontend.auth.privacy_link') . '</a>',
                ]) !!}
            </span>
        </label>

        <button type="submit" class="au-btn">
            <i class="fas fa-user-plus"></i> {{ __('frontend.auth.register_do') }}
        </button>
    </form>
@endsection

@section('under')
    {{ __('frontend.auth.have_account') }}
    <a href="{{ route('login') }}">{{ __('frontend.auth.login_do') }}</a>
@endsection

@push('styles')
<style>
    /* Переключатель «частное лицо / организация». Своим CSS, а не
       peer-checked: этого варианта в сборке Tailwind нет (см. CLAUDE.md). */
    .au-switch { display: grid; grid-template-columns: 1fr 1fr; gap: 8px }
    .au-switch-item {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 10px 12px; font-size: .85rem; font-weight: 600;
        border: 1px solid var(--au-line); border-radius: calc(var(--au-radius) - 4px);
        cursor: pointer; transition: border-color .15s ease, color .15s ease;
    }
    .au-switch-item input { position: absolute; opacity: 0; width: 0; height: 0 }
    .au-switch-item.is-on { color: var(--au-primary); border-color: var(--au-primary) }
</style>
@endpush

@push('scripts')
<script>
    function registerForm(company) {
        return {
            // Значение радиокнопки приезжает строкой, а показывать блок надо
            // по логическому признаку — держим оба и не путаем.
            companyRaw: company ? '1' : '0',
            password: '',
            confirmation: '',

            get company() {
                return this.companyRaw === '1';
            },

            get matches() {
                return this.confirmation.length > 0 && this.password === this.confirmation;
            },

            /**
             * Оценка пароля — та же, что в личном кабинете: длина плюс
             * разнообразие символов. Это подсказка человеку, а не проверка:
             * настоящую делает сервер правилом Password::defaults().
             */
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
