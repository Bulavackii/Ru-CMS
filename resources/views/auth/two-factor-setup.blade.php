@extends('layouts.guest')

@section('title', __('frontend.auth.tfa_setup_title'))
@section('eyebrow', __('frontend.auth.eyebrow_tfa_setup'))
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
         включения: второй раз их взять неоткуда, поэтому блок заметный.
         Оформление не «ошибка», а «важное»: красным здесь пугать нечем,
         ничего не сломалось — просто это единственный шанс их сохранить. --}}
    @if (session('recovery_codes'))
        <div class="tfa-rc">
            <div class="tfa-rc__head">
                <span class="tfa-rc__ico"><i class="fas fa-life-ring"></i></span>
                <strong>{{ __('frontend.auth.tfa_codes_title') }}</strong>
            </div>

            <div class="tfa-rc__grid" id="tfa-recovery">
                @foreach (session('recovery_codes') as $code)
                    <span>{{ $code }}</span>
                @endforeach
            </div>

            <div class="tfa-rc__foot">
                <small>{{ __('frontend.auth.tfa_codes_hint') }}</small>
                <button type="button" class="tfa-copy" data-copy="#tfa-recovery">
                    <i class="fas fa-copy"></i>
                    <span>{{ __('frontend.auth.tfa_codes_copy') }}</span>
                </button>
            </div>
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

    {{-- Шаги были обычным списком и читались сплошным текстом. Номер вынесен
         в кружок: по нему сразу видно, сколько всего действий и на каком вы. --}}
    <ol class="tfa-steps">
        <li><span class="tfa-steps__n">1</span><span>{{ __('frontend.auth.tfa_step1') }}</span></li>
        <li><span class="tfa-steps__n">2</span><span>{{ __('frontend.auth.tfa_step2') }}</span></li>
        <li><span class="tfa-steps__n">3</span><span>{{ __('frontend.auth.tfa_step3') }}</span></li>
    </ol>

    @if(!empty($qrCodeSvg))
        <div class="tfa-qr">
            {{-- Код вставлен разметкой, а не ссылкой на картинку: не зависит от
                 политики безопасности страницы и не мылится при увеличении.
                 Подложка остаётся белой при любой теме — сканеру нужны тёмные
                 модули на светлом, на тёмной подложке код не прочитается. --}}
            <div class="tfa-qr__tile" role="img" aria-label="{{ __('frontend.auth.tfa_qr_alt') }}">
                {!! $qrCodeSvg !!}
            </div>
            <p class="tfa-qr__cap"><i class="fas fa-camera"></i> {{ __('frontend.auth.tfa_qr_caption') }}</p>
        </div>
    @else
        <div class="au-note au-note--bad">
            <i class="fas fa-circle-exclamation"></i>
            <span>{{ __('frontend.auth.tfa_qr_missing') }}</span>
        </div>
    @endif

    <div class="au-field">
        <span class="au-label">{{ __('frontend.auth.tfa_manual') }}</span>

        {{-- Ключ длинный и набирается на телефоне — без кнопки копирования
             его переписывали бы посимвольно, а ошибка в одном знаке даёт
             коды, которые просто не подходят, без всякого объяснения. --}}
        <div class="tfa-key">
            <code class="tfa-key__val" id="tfa-secret">{{ $secret }}</code>
            <button type="button" class="tfa-copy" data-copy="#tfa-secret">
                <i class="fas fa-copy"></i>
                <span>{{ __('frontend.auth.tfa_copy') }}</span>
            </button>
        </div>

        <span class="au-hint">{{ __('frontend.auth.tfa_manual_note') }}</span>
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
            <span class="au-hint">{{ __('frontend.auth.tfa_hint') }}</span>
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
    /* ── Шаги ───────────────────────────────────────────────────────── */
    .tfa-steps { display: grid; gap: 7px; margin: 0 0 14px; padding: 0; list-style: none }
    .tfa-steps li { display: flex; gap: 9px; align-items: flex-start;
                    font-size: .83rem; line-height: 1.5; color: var(--au-muted) }
    .tfa-steps__n {
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        width: 19px; height: 19px; margin-top: 1px;
        font-size: .68rem; font-weight: 700; color: #fff;
        background: linear-gradient(135deg, var(--au-primary), var(--au-accent));
        border-radius: 50%;
    }

    /* ── Код ────────────────────────────────────────────────────────── */
    .tfa-qr { margin-bottom: 14px; text-align: center }
    .tfa-qr__tile {
        display: inline-block; padding: 10px; line-height: 0;
        background: #fff; border: 1px solid var(--au-line);
        border-radius: calc(var(--au-radius) - 4px);
        box-shadow: 0 2px 10px rgba(17, 24, 39, .06);
    }
    /* Ширину задаём здесь, а не в самой картинке: код квадратный, высота
       следует за шириной сама, и на узком экране он не вылезает за карточку. */
    .tfa-qr__tile svg { display: block; width: 186px; max-width: 62vw; height: auto }
    .tfa-qr__cap { display: flex; align-items: center; justify-content: center; gap: 6px;
                   margin: 8px 0 0; font-size: .74rem; color: var(--au-muted) }

    /* ── Ключ вручную ───────────────────────────────────────────────── */
    .tfa-key { display: flex; align-items: stretch; gap: 6px; min-width: 0 }
    .tfa-key__val {
        flex: 1; min-width: 0; padding: 9px 11px;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .82rem; letter-spacing: .08em; word-break: break-all;
        color: var(--au-text); background: var(--au-soft);
        border: 1px solid var(--au-line); border-radius: calc(var(--au-radius) - 5px);
    }

    /* Подпись обычного цвета, а акцент — только на значке. Индиго на светлой
       подложке даёт контраст 3.27 и для мелкого текста кнопки его не хватает;
       у обычного цвета текста — около 13. */
    .tfa-copy {
        display: inline-flex; align-items: center; gap: 6px; flex: 0 0 auto;
        padding: 0 11px; font: inherit; font-size: .76rem; font-weight: 600;
        color: var(--au-text); background: var(--au-soft); cursor: pointer;
        border: 1px solid var(--au-line); border-radius: calc(var(--au-radius) - 5px);
        transition: border-color .15s;
    }
    .tfa-copy i { color: var(--au-primary) }
    .tfa-copy:hover { border-color: var(--au-primary) }
    /* Подтверждение показываем рамкой и значком: зелёный текст пришлось бы
       подбирать отдельно под светлую и тёмную тему. */
    .tfa-copy.is-done { border-color: #86efac }
    .tfa-copy.is-done i { color: #16a34a }

    /* ── Коды восстановления ────────────────────────────────────────── */
    .tfa-rc { margin-bottom: 12px; padding: 11px 12px;
              background: var(--au-soft); border: 1px solid var(--au-line);
              border-left: 3px solid var(--au-primary);
              border-radius: calc(var(--au-radius) - 5px) }
    .tfa-rc__head { display: flex; align-items: center; gap: 8px; font-size: .84rem }
    .tfa-rc__ico { display: flex; align-items: center; justify-content: center;
                   width: 22px; height: 22px; font-size: .72rem; color: #fff;
                   background: linear-gradient(135deg, var(--au-primary), var(--au-accent));
                   border-radius: 50%; flex: 0 0 auto }
    .tfa-rc__grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px; margin: 9px 0;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem;
    }
    .tfa-rc__foot { display: flex; align-items: center; justify-content: space-between;
                    gap: 10px; flex-wrap: wrap }
    .tfa-rc__foot small { font-size: .72rem; line-height: 1.4; color: var(--au-muted) }

    .au-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
               font-size: 1.25rem; letter-spacing: .28em; text-align: center }

    /* На телефоне код чаще всего сканируют ВТОРЫМ устройством, поэтому там
       он крупнее: чем больше модуль, тем увереннее наводится камера. */
    @media (max-width: 480px) {
        .tfa-qr__tile svg { width: 216px }
    }

    @media (max-width: 380px) {
        .tfa-key { flex-direction: column }
        .tfa-copy { justify-content: center; padding: 8px }
    }
</style>
@endpush

@push('scripts')
<script>
    // Копирование ключа и кодов восстановления. Одним обработчиком на всю
    // страницу: кнопок две, и обе делают одно и то же с разным источником.
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-copy]');

        if (!button) {
            return;
        }

        var source = document.querySelector(button.dataset.copy);

        if (!source) {
            return;
        }

        // У списка кодов каждый код лежит в своём теге: текст контейнера
        // склеил бы их в одну строку без разделителей.
        var parts = source.children.length
            ? Array.prototype.map.call(source.children, function (item) { return item.textContent.trim(); })
            : [source.textContent];

        var text = parts.join('\n').trim();
        var label = button.querySelector('span');
        var was = label ? label.textContent : '';

        var done = function () {
            button.classList.add('is-done');

            if (label) {
                label.textContent = @js(__('frontend.auth.tfa_copied'));
            }

            setTimeout(function () {
                button.classList.remove('is-done');

                if (label) {
                    label.textContent = was;
                }
            }, 1800);
        };

        // Запасной путь через скрытое поле. Нужен в двух случаях сразу:
        // современного clipboard нет по незащищённому http, а на защищённом
        // он всё равно может ОТКАЗАТЬ (запрет в настройках браузера) —
        // отказ приходит отклонённым обещанием, и без перехвата кнопка
        // молча не делает ничего.
        var fallback = function () {
            var probe = document.createElement('textarea');
            probe.value = text;
            probe.setAttribute('readonly', 'readonly');
            probe.style.position = 'fixed';
            probe.style.opacity = '0';
            document.body.appendChild(probe);
            probe.select();

            try {
                document.execCommand('copy');
                done();
            } catch (error) {
                // Копирование запрещено — текст остаётся на странице и его
                // можно выделить руками.
            }

            document.body.removeChild(probe);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, fallback);
            return;
        }

        fallback();
    });
</script>
@endpush
