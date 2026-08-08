{{--
    Регистрация.

    Прежняя форма спрашивала реквизиты организации полями is_legal, org_name и
    kpp — таких колонок в users нет вовсе (там is_company, company_name, inn,
    ogrn, ceo), а обработчик складывал их в settings, тогда как профиль в
    кабинете читает колонки. Имена приведены к схеме, хранилище одно.

    Поля идут в две колонки: в одну форма не помещалась в окно 1280×720 и
    уезжала на полтысячи пикселей.
--}}
@extends('layouts.guest')

@section('title', __('frontend.auth.register_title'))
@section('heading', __('frontend.auth.register_title'))
@section('lead', __('frontend.auth.register_lead'))
@section('icon', 'fa-user-plus')
@section('card_class', 'au-card--wide')

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

        <div class="au-grid au-grid--2">
            <div class="au-field">
                <label class="au-label" for="name">{{ __('frontend.account.name') }}<span class="au-req">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       required autofocus autocomplete="name"
                       placeholder="{{ __('frontend.auth.name_ph') }}"
                       class="au-input @error('name') is-bad @enderror">
                @error('name')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            <div class="au-field">
                <label class="au-label" for="phone">{{ __('frontend.account.f_phone') }}</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                       autocomplete="tel" placeholder="+7 900 000-00-00"
                       class="au-input @error('phone') is-bad @enderror">
                @error('phone')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            {{-- Пока реквизиты скрыты, почта занимает строку целиком: иначе
                 рядом с ней зияет пустая половина. --}}
            <div class="au-field" :class="company ? '' : 'au-span-2'">
                <label class="au-label" for="email">{{ __('frontend.auth.email') }}<span class="au-req">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="email"
                       placeholder="{{ __('frontend.auth.email_ph') }}"
                       class="au-input @error('email') is-bad @enderror">
                @error('email')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            {{-- Реквизиты показываются только организации: физлицу они не нужны,
                 а форма без них короче на три поля. --}}
            <div class="au-field" x-show="company" x-cloak>
                <label class="au-label" for="company_name">{{ __('frontend.account.org_name') }}<span class="au-req">*</span></label>
                <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}"
                       :required="company" placeholder="{{ __('frontend.auth.company_ph') }}"
                       class="au-input @error('company_name') is-bad @enderror">
                @error('company_name')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            <div class="au-field" x-show="company" x-cloak>
                <label class="au-label" for="inn">{{ __('frontend.account.inn') }}<span class="au-req">*</span></label>
                <input id="inn" type="text" name="inn" value="{{ old('inn') }}"
                       :required="company" inputmode="numeric" maxlength="12"
                       class="au-input @error('inn') is-bad @enderror">
                @error('inn')<span class="au-err">{{ $message }}</span>@enderror
            </div>

            <div class="au-field" x-show="company" x-cloak>
                <label class="au-label" for="ogrn">{{ __('frontend.account.ogrn') }}</label>
                <input id="ogrn" type="text" name="ogrn" value="{{ old('ogrn') }}"
                       inputmode="numeric" maxlength="15"
                       class="au-input @error('ogrn') is-bad @enderror">
                @error('ogrn')<span class="au-err">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="au-split">{{ __('frontend.auth.g_access') }}</div>

        <div class="au-grid au-grid--2">
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
        </div>

        {{-- Каптча и «согласен + кнопка» стоят в одной строке: столбиком они
             занимали лишние восемьдесят пикселей, и форма организации
             переставала помещаться в невысокое окно. --}}
        <div class="au-finish">
            @if(config('captcha.enabled', true) && function_exists('captcha_field'))
                <div class="au-finish-captcha">
                    {!! captcha_field(config('captcha.default_type', 'image')) !!}
                    @error('captcha')<span class="au-err">{{ $message }}</span>@enderror
                </div>
            @endif

            <div class="au-finish-do">
                <label class="au-check">
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
            </div>
        </div>
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
    .au-switch { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px }
    .au-switch-item {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 9px 12px; font-size: .83rem; font-weight: 600; color: var(--au-muted);
        background: var(--au-soft);
        border: 1px solid var(--au-line); border-radius: calc(var(--au-radius) - 5px);
        cursor: pointer; transition: border-color .15s ease, color .15s ease, background .15s ease;
    }
    .au-switch-item input { position: absolute; opacity: 0; width: 0; height: 0 }
    .au-switch-item.is-on { color: var(--au-primary); background: #fff; border-color: var(--au-primary) }

    @media (min-width: 560px) { .au-span-2 { grid-column: 1 / -1 } }

    .au-finish { display: grid; gap: 10px }
    .au-finish-do { display: flex; flex-direction: column; justify-content: flex-end; gap: 9px }
    @media (min-width: 560px) {
        /* Каптча своей ширины, остальное занимает остаток строки. */
        .au-finish { grid-template-columns: auto minmax(0, 1fr); gap: 0 14px; align-items: end }
    }
    /* Картинка каптчи бывает шире колонки — не даём ей разорвать сетку. */
    .au-finish-captcha img { max-width: 100%; height: auto }
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
             * настоящую делает сервер правилом Password.
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
