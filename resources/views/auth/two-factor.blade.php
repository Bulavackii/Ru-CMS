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
            <label class="au-label" for="code" id="code-label">{{ __('frontend.auth.tfa_code') }}</label>
            {{-- Код цифровой: inputmode подсказывает телефону показать
                 цифровую клавиатуру, autocomplete — подставить код из СМС.
                 В режиме кода восстановления оба атрибута снимаются: там
                 есть буквы, и с цифровой клавиатуры их не набрать. --}}
            <input id="code" type="text" name="code" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" placeholder="000000"
                   class="au-input au-code @error('code') is-bad @enderror">
            <span class="au-hint" id="code-hint">{{ __('frontend.auth.tfa_hint') }}</span>
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-shield-halved"></i> {{ __('frontend.auth.tfa_do') }}
        </button>

        {{-- Запасной путь на случай потерянного телефона. Коды
             восстановления в проекте выдавались и хранились, но принимать их
             при входе было негде — без этой ссылки пропавший телефон означал
             потерю доступа навсегда. --}}
        <button type="button" class="au-swap" id="code-swap">
            <i class="fas fa-life-ring"></i>
            <span>{{ __('frontend.auth.tfa_use_recovery') }}</span>
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
    /* Код восстановления длиннее и с буквами — разрядка поменьше, иначе
       десять знаков не помещаются в поле на телефоне. */
    .au-code.is-recovery { font-size: 1.05rem; letter-spacing: .16em }

    .au-swap { display: flex; align-items: center; justify-content: center; gap: 7px;
               width: 100%; margin-top: 11px; padding: 8px; font: inherit; font-size: .78rem;
               color: var(--au-muted); background: none; border: 0; cursor: pointer }
    .au-swap:hover { color: var(--au-text) }
    .au-swap i { color: var(--au-primary) }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('code');
        var label = document.getElementById('code-label');
        var hint = document.getElementById('code-hint');
        var swap = document.getElementById('code-swap');

        if (!input || !swap) {
            return;
        }

        var modes = {
            app: {
                label: @js(__('frontend.auth.tfa_code')),
                hint: @js(__('frontend.auth.tfa_hint')),
                swap: @js(__('frontend.auth.tfa_use_recovery')),
                inputmode: 'numeric',
                autocomplete: 'one-time-code',
                maxlength: '6',
                placeholder: '000000'
            },
            recovery: {
                label: @js(__('frontend.auth.tfa_recovery_label')),
                hint: @js(__('frontend.auth.tfa_recovery_hint')),
                swap: @js(__('frontend.auth.tfa_use_app')),
                inputmode: 'text',
                autocomplete: 'off',
                maxlength: '16',
                placeholder: 'XXXXXXXXXX'
            }
        };

        var current = 'app';

        swap.addEventListener('click', function () {
            current = current === 'app' ? 'recovery' : 'app';

            var mode = modes[current];

            label.textContent = mode.label;
            hint.textContent = mode.hint;
            swap.querySelector('span').textContent = mode.swap;

            input.setAttribute('inputmode', mode.inputmode);
            input.setAttribute('autocomplete', mode.autocomplete);
            input.setAttribute('maxlength', mode.maxlength);
            input.setAttribute('placeholder', mode.placeholder);
            input.classList.toggle('is-recovery', current === 'recovery');
            input.value = '';
            input.focus();
        });
    })();
</script>
@endpush
