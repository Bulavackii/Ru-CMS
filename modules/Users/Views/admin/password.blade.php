@extends('layouts.admin')

@section('title', __('admin.users.password_change'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-key"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.users.password_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ $user->name }} · {{ $user->email }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.users.edit', $user) }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.users.to_user') }}
        </a>
    </div>

    @if($errors->any())
        <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
            <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.password.update', $user->id) }}" method="POST"
          class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start"
          x-data="{
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
              get ready() { return this.len >= 8 && this.match; },
              generate() {
                  // Пароль собирается из набора без похожих знаков: ноль и
                  // буква O, единица и l при передаче голосом или от руки
                  // путаются, и человек не может войти.
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

        <div class="lg:col-span-2 admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-lock text-indigo-500"></i> {{ __('admin.users.new_password') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('admin.users.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input :type="show ? 'text' : 'password'" x-model="pass"
                           name="password" id="password" required autocomplete="new-password" minlength="8"
                           placeholder="{{ __('admin.users.password_ph') }}"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    @error('password')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror

                    {{-- Полоса надёжности: требование «минимум 8» жило только
                         в подсказке поля и пропадало при первом же символе. --}}
                    <div class="pw-meter" x-show="pass" x-cloak>
                        <span :class="score >= 1 && 'is-on s1'"></span>
                        <span :class="score >= 2 && 'is-on s2'"></span>
                        <span :class="score >= 3 && 'is-on s3'"></span>
                        <span :class="score >= 4 && 'is-on s4'"></span>
                    </div>
                    <p class="pw-note" x-show="pass" x-cloak
                       x-text="len < 8
                           ? @js(__('admin.users.pw_short')).replace(':n', 8 - len)
                           : [@js(__('admin.users.pw_weak')), @js(__('admin.users.pw_weak')), @js(__('admin.users.pw_fair')), @js(__('admin.users.pw_good')), @js(__('admin.users.pw_strong'))][score]"></p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('admin.users.password_repeat') }} <span class="text-red-500">*</span>
                    </label>
                    <input :type="show ? 'text' : 'password'" x-model="repeat"
                           name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                           placeholder="{{ __('admin.users.password_repeat_ph') }}"
                           :class="mismatch && 'pw-bad'"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

                    {{-- Расхождение видно сразу, а не после отправки формы. --}}
                    <p class="pw-note pw-note--bad" x-show="mismatch" x-cloak>{{ __('admin.users.pw_mismatch') }}</p>
                    <p class="pw-note pw-note--ok" x-show="match" x-cloak>{{ __('admin.users.pw_match') }}</p>
                </div>
            </div>

            <div class="pw-tools">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                    <input type="checkbox" x-model="show" class="border-gray-400 dark:border-gray-600">
                    {{ __('admin.users.show_password') }}
                </label>

                {{-- Пароль для другого человека администратор обычно
                     придумывает на ходу и получается слабый. Кнопка сразу
                     заполняет оба поля и показывает результат — его надо
                     скопировать и передать. --}}
                <button type="button" class="pw-gen" @click="generate()">
                    <i class="fas fa-wand-magic-sparkles"></i> {{ __('admin.users.pw_generate') }}
                </button>
            </div>
        </div>

        <div class="space-y-5">
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                    <i class="fas fa-circle-info text-indigo-500"></i> {{ __('admin.users.what_happens') }}
                </h2>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-1"></i>
                        <span>{{ __('admin.users.pwd_note_1') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-1"></i>
                        <span>{{ __('admin.users.pwd_note_2') }}</span>
                    </li>
                </ul>
                <p class="pw-help">
                    {{ __('admin.users.pwd_note_3') }}
                </p>
            </div>

            <div class="admin-card p-4 flex items-center gap-2">
                <button type="submit" :disabled="!ready" class="pw-submit"
                        class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               px-4 py-2 text-sm font-semibold shadow-sm transition flex-1">
                    <i class="fas fa-save"></i> {{ __('admin.users.update_password') }}
                </button>
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800
                          px-4 py-2 text-sm font-semibold transition">
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </div>
    </form>
@endsection

{{-- Скрипт показа пароля удалён: он искал чекбокс по идентификатору и
     переключал тип полей вручную. Теперь тип привязан к состоянию формы,
     и та же галочка управляет обоими полями без единой строки скрипта. --}}

@push('styles')
<style>
    /* Полоса надёжности: четыре отрезка, зажигаются по мере усложнения. */
    .pw-meter{ display:grid; grid-template-columns:repeat(4,1fr); gap:.25rem; margin-top:.5rem }
    .pw-meter span{ height:4px; background:#e2e8f0 }
    .pw-meter span.is-on.s1{ background:#ef4444 }
    .pw-meter span.is-on.s2{ background:#f59e0b }
    .pw-meter span.is-on.s3{ background:#eab308 }
    .pw-meter span.is-on.s4{ background:#22c55e }

    .pw-note{ margin-top:.35rem; font-size:.78rem; color:#64748b }
    .pw-note--bad{ color:#dc2626; font-weight:600 }
    .pw-note--ok{ color:#15803d; font-weight:600 }
    .pw-bad{ border-color:#dc2626 !important }

    .pw-tools{ display:flex; align-items:center; justify-content:space-between;
        gap:1rem; flex-wrap:wrap; margin-top:.9rem }
    .pw-gen{ display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .8rem;
        font-size:.8rem; font-weight:600; color:#4338ca; background:rgba(99,102,241,.1);
        border:0; cursor:pointer; transition:background .15s }
    .pw-gen:hover{ background:rgba(99,102,241,.2) }

    /* Подсказка под блоком: раньше тут стоял класс врезки-примечания,
       он рисует заливку и полосу слева, и короткое пояснение читалось
       как выделенный текст. */
    .pw-help{ margin-top:.75rem; font-size:.8rem; line-height:1.5; color:#64748b }

    /* Пока пароль короче восьми знаков или поля расходятся, отправлять
       нечего: раньше об этом сообщал только ответ сервера. */
    .pw-submit:disabled{ opacity:.5; cursor:not-allowed }
</style>
@endpush
