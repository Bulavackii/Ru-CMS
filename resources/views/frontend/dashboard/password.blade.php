@extends('layouts.frontend')

@section('title', __('frontend.account.change_password'))

@section('content')
<div class="acc-head">
    <span class="fx-badge"><i class="fas fa-lock"></i></span>
    <div class="min-w-0">
        <h1 class="fx-section-title">{{ __('frontend.account.change_password') }}</h1>
        <p class="fx-section-sub">{{ __('frontend.account.pass_hint') }}</p>
    </div>
</div>

<div class="acc-form">
    <section class="fx-card p-5">
        @if (session('success'))
            <div class="acc-flash">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="acc-flash acc-flash--bad">{{ $errors->first() }}</div>
        @endif

        {{-- Поведение такое же, как на смене пароля в панели: полоса
             надёжности, сверка полей и кнопка «придумать пароль». Поля тут
             набраны напрямую, а не общим компонентом: ему нужно передать
             привязку к состоянию и переключение типа, а это уже не «поле с
             подписью», ради которого он создавался. --}}
        <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4"
              x-data="{
                  current: '',
                  pass: '',
                  repeat: '',
                  show: false,
                  get len() { return this.pass.length; },
                  get score() {
                      if (!this.pass) return 0;
                      let n = 0;
                      if (this.len >= 8) n++;
                      if (this.len >= 12) n++;
                      if (/[a-zа-я]/.test(this.pass) && /[A-ZА-Я]/.test(this.pass)) n++;
                      if (/\d/.test(this.pass)) n++;
                      if (/[^\w\s]/.test(this.pass)) n++;
                      return Math.min(n, 4);
                  },
                  get match() { return this.repeat !== '' && this.pass === this.repeat; },
                  get mismatch() { return this.repeat !== '' && this.pass !== this.repeat; },
                  get ready() { return this.current !== '' && this.len >= 8 && this.match; },
                  generate() {
                      // Набор без похожих знаков: ноль и буква O, единица и l
                      // при переписывании от руки путаются.
                      const abc = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!?*-_=+';
                      const out = Array.from(crypto.getRandomValues(new Uint32Array(16)))
                          .map((x) => abc[x % abc.length]).join('');
                      this.pass = out;
                      this.repeat = out;
                      this.show = true;
                  }
              }">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="pwf-label">
                    {{ __('frontend.account.current_pass') }} <span class="pwf-req">*</span>
                </label>
                <input :type="show ? 'text' : 'password'" x-model="current"
                       id="current_password" name="current_password" required
                       autocomplete="current-password" class="pwf-input">
            </div>

            <div>
                <label for="new_password" class="pwf-label">
                    {{ __('frontend.account.new_pass') }} <span class="pwf-req">*</span>
                </label>
                <input :type="show ? 'text' : 'password'" x-model="pass"
                       id="new_password" name="new_password" required minlength="8"
                       autocomplete="new-password" class="pwf-input">

                <div class="pwf-meter" x-show="pass" x-cloak>
                    <span :class="score >= 1 && 'is-on s1'"></span>
                    <span :class="score >= 2 && 'is-on s2'"></span>
                    <span :class="score >= 3 && 'is-on s3'"></span>
                    <span :class="score >= 4 && 'is-on s4'"></span>
                </div>
                <p class="pwf-note" x-show="pass" x-cloak
                   x-text="len < 8
                       ? @js(__('frontend.account.pw_short')).replace(':n', 8 - len)
                       : [@js(__('frontend.account.pw_weak')), @js(__('frontend.account.pw_weak')), @js(__('frontend.account.pw_fair')), @js(__('frontend.account.pw_good')), @js(__('frontend.account.pw_strong'))][score]"></p>
            </div>

            <div>
                <label for="new_password_confirmation" class="pwf-label">
                    {{ __('frontend.account.confirm_pass') }} <span class="pwf-req">*</span>
                </label>
                <input :type="show ? 'text' : 'password'" x-model="repeat"
                       id="new_password_confirmation" name="new_password_confirmation" required
                       autocomplete="new-password" :class="mismatch && 'pwf-bad'" class="pwf-input">

                <p class="pwf-note pwf-note--bad" x-show="mismatch" x-cloak>{{ __('frontend.account.pw_mismatch') }}</p>
                <p class="pwf-note pwf-note--ok" x-show="match" x-cloak>{{ __('frontend.account.pw_match') }}</p>
            </div>

            <div class="pwf-tools">
                <label class="pwf-show">
                    <input type="checkbox" x-model="show"> {{ __('frontend.account.pw_show') }}
                </label>

                <button type="button" class="pwf-gen" @click="generate()">
                    <i class="fas fa-wand-magic-sparkles"></i> {{ __('frontend.account.pw_generate') }}
                </button>
            </div>

            <div class="acc-actions">
                <button type="submit" class="fx-btn pwf-submit" :disabled="!ready">
                    <i class="fas fa-floppy-disk"></i> {{ __('frontend.account.change_password') }}
                </button>

                <a href="{{ route('dashboard') }}" class="acc-btn-ghost">
                    {{ __('frontend.common.back') }}
                </a>
            </div>
        </form>
    </section>
</div>
@endsection

@push('styles')
<style>
    /* Общие для страниц кабинета классы продублированы здесь: общего
       партиала под них в проекте нет. */
    .acc-head{ display:flex; align-items:center; justify-content:center; gap:.9rem;
               margin:0 auto 1.25rem; max-width:38rem; text-align:left }
    .acc-form{ max-width:38rem; margin-inline:auto }
    .acc-flash{ border:1px solid #bbf7d0; background:#f0fdf4; color:#166534;
                padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem }
    .acc-flash--bad{ border-color:#fecaca; background:#fef2f2; color:#991b1b }
    /* Кнопки страницы. Общий .fx-btn рисовался под короткое «Подробнее»:
       длинная подпись вылезала за его фон, поэтому размеры задаём сами. */
    .acc-actions{ display:flex; flex-wrap:wrap; gap:.6rem; align-items:stretch;
                  justify-content:center; margin-top:.25rem }
    .acc-actions .fx-btn,
    .acc-actions .acc-btn-ghost{
        display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
        padding:.65rem 1.4rem; line-height:1.25; white-space:nowrap; width:auto;
        font-size:.88rem; font-weight:600;
    }
    .acc-btn-ghost{ border:1px solid var(--surface-bd,#e5e7eb); background:var(--surface,#fff); color:var(--surface-ink,#374151);
                    transition:border-color .15s, color .15s, background .15s }
    .acc-btn-ghost:hover{ border-color:#a5b4fc; color:#4f46e5; background:var(--surface-2,#f8fafc) }
    .acc-actions .fx-btn:active,
    .acc-actions .acc-btn-ghost:active{ transform:translateY(1px) }

    @media (max-width:520px){
        .acc-actions{ flex-direction:column; align-items:stretch }
        .acc-actions .fx-btn,
        .acc-actions .acc-btn-ghost{ white-space:normal; width:100% }
    }
</style>
@endpush


@push('styles')
<style>
    /* Литеральный CSS: в статической сборке проекта нет ни произвольных
       значений, ни прозрачности через дробь. Оформление повторяет такое же
       на странице смены пароля в панели — человек видит один и тот же
       приём в обоих местах. */
    .pwf-label{ display:block; margin-bottom:.35rem; font-size:.875rem; font-weight:600;
        color:var(--surface-ink,#374151) }
    .pwf-req{ color:#ef4444 }
    .pwf-input{ width:100%; padding:.6rem .75rem; font-size:.9rem; color:var(--surface-ink,#0f172a);
        background:var(--surface,#fff); border:1px solid #d1d5db; transition:border-color .15s, box-shadow .15s }
    .pwf-input:focus{ outline:none; border-color:var(--color-primary,#6366f1);
        box-shadow:0 0 0 3px rgba(99,102,241,.15) }
    .pwf-bad{ border-color:#dc2626 }

    .pwf-meter{ display:grid; grid-template-columns:repeat(4,1fr); gap:.25rem; margin-top:.5rem }
    .pwf-meter span{ height:4px; background:#e5e7eb }
    .pwf-meter span.is-on.s1{ background:#ef4444 }
    .pwf-meter span.is-on.s2{ background:#f59e0b }
    .pwf-meter span.is-on.s3{ background:#eab308 }
    .pwf-meter span.is-on.s4{ background:#22c55e }

    .pwf-note{ margin-top:.35rem; font-size:.78rem; color:var(--surface-mute,#6b7280) }
    .pwf-note--bad{ color:#dc2626; font-weight:600 }
    .pwf-note--ok{ color:#15803d; font-weight:600 }

    .pwf-tools{ display:flex; align-items:center; justify-content:space-between;
        gap:1rem; flex-wrap:wrap }
    .pwf-show{ display:inline-flex; align-items:center; gap:.45rem; font-size:.85rem;
        color:var(--surface-ink,#4b5563); cursor:pointer }
    .pwf-gen{ display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .8rem;
        font-size:.82rem; font-weight:600; color:#4338ca; background:rgba(99,102,241,.1);
        border:0; cursor:pointer; transition:background .15s }
    .pwf-gen:hover{ background:rgba(99,102,241,.2) }

    /* Отправлять нечего, пока не введён текущий пароль, новый короче
       восьми знаков или поля расходятся. */
    .pwf-submit:disabled{ opacity:.5; cursor:not-allowed }

    :root.dark .pwf-label{ color:#e5e7eb }
    :root.dark .pwf-input{ color:#f3f4f6; background:#111827; border-color:#374151 }
    :root.dark .pwf-show{ color:#9ca3af }
</style>
@endpush
