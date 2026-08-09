@extends('layouts.guest')

@section('title', __('frontend.auth.tfa_setup_title'))
@section('heading', __('frontend.auth.tfa_setup_title'))
@section('lead', __('frontend.auth.tfa_setup_lead'))

@section('aside_title', __('frontend.auth.aside_tfa_title'))
@section('aside_text', __('frontend.auth.aside_tfa_text'))

@section('content')
    @if (session('success'))
        <div class="au-note au-note--ok">
            <i class="fas fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Коды восстановления показываются РОВНО ОДИН раз, сразу после
         включения: второй раз их взять неоткуда, поэтому блок заметный. --}}
    @if (session('recovery_codes'))
        <div class="au-note au-note--bad" style="flex-direction:column">
            <strong><i class="fas fa-triangle-exclamation"></i> {{ __('frontend.auth.tfa_codes_title') }}</strong>
            <div class="au-codes">
                @foreach (session('recovery_codes') as $code)
                    <span>{{ $code }}</span>
                @endforeach
            </div>
            <small>{{ __('frontend.auth.tfa_codes_hint') }}</small>
        </div>
    @endif

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

    <ol class="au-steps">
        <li>{{ __('frontend.auth.tfa_step1') }}</li>
        <li>{{ __('frontend.auth.tfa_step2') }}</li>
        <li>{{ __('frontend.auth.tfa_step3') }}</li>
    </ol>

    <div class="au-qr">
        @if(!empty($qrCodeSvg))
            {{-- Код вставлен разметкой, а не ссылкой на картинку: не зависит от
                 политики безопасности страницы и не мылится при увеличении. --}}
            <div class="au-qr__img" role="img" aria-label="{{ __('frontend.auth.tfa_qr_alt') }}">
                {!! $qrCodeSvg !!}
            </div>
        @else
            <p class="au-err">{{ __('frontend.auth.tfa_qr_missing') }}</p>
        @endif
    </div>

    <div class="au-field">
        <span class="au-label">{{ __('frontend.auth.tfa_manual') }}</span>
        <code class="au-secret">{{ $secret }}</code>
    </div>

    <form method="POST" action="{{ route('two-factor.enable') }}">
        @csrf

        <div class="au-field">
            <label class="au-label" for="code">{{ __('frontend.auth.tfa_code') }}</label>
            <input id="code" type="text" name="code" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                   class="au-input au-code @error('code') is-bad @enderror"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            @error('code')<span class="au-err">{{ $message }}</span>@enderror
        </div>

        <button type="submit" class="au-btn">
            <i class="fas fa-shield-halved"></i> {{ __('frontend.auth.tfa_enable') }}
        </button>
    </form>
@endsection

@section('under')
    <a href="{{ route('dashboard') }}">{{ __('frontend.auth.to_dashboard') }}</a>
@endsection

@push('styles')
<style>
    .au-steps { margin: 0 0 16px; padding-left: 20px; font-size: .84rem; line-height: 1.7; color: var(--au-muted) }
    .au-qr { display: flex; justify-content: center; margin-bottom: 16px; padding: 12px;
             background: #fff; border: 1px solid var(--au-line); border-radius: calc(var(--au-radius) - 4px) }
    /* Ширину задаём здесь, а не в самой картинке: код квадратный, высота
       следует за шириной сама, и на узком экране он не вылезает за карточку. */
    .au-qr__img { width: 192px; max-width: 100%; line-height: 0 }
    .au-qr__img svg { width: 100%; height: auto }
    .au-secret { display: block; padding: 9px 11px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                 font-size: .8rem; word-break: break-all; background: #f3f4f6; color: #374151;
                 border-radius: calc(var(--au-radius) - 6px) }
    .au-codes { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin: 8px 0;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .82rem }
    .au-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
               font-size: 1.25rem; letter-spacing: .28em; text-align: center }
</style>
@endpush
