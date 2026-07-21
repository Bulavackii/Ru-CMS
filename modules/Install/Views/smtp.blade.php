@extends('layouts.frontend-install')

@section('accent', '#f97316')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.smtp') }}"
          class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden"
          x-data="{
              showPass: false,
              submitting: false,
              enc: '{{ old('mail_encryption', $mail['encryption']) }}',
              port: '{{ old('mail_port', $mail['port']) }}',
              syncPort() { this.port = this.enc === 'ssl' ? '465' : (this.enc === 'tls' ? '587' : '25'); }
          }"
          x-on:submit="submitting = true">
        @csrf

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'smtp'])
            <div class="text-center">
                <div class="accent-badge mx-auto w-10 h-10 rounded-xl text-white grid place-items-center mb-2">
                    <i data-lucide="mail" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900 inline-flex items-center gap-2 flex-wrap justify-center">
                    {{ __('install.smtp.title') }}
                    {{-- Шаг можно пропустить — говорим об этом сразу, у заголовка,
                         чтобы пользователь не искал кнопку «Пропустить» внизу. --}}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                          style="color:var(--accent); background-color:color-mix(in srgb, var(--accent) 12%, transparent); box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--accent) 30%, transparent)">
                        <i data-lucide="circle-dashed" class="w-3 h-3"></i> {{ __('install.smtp.optional') }}
                    </span>
                </h2>
                <p class="text-gray-500 text-xs flex items-center justify-center gap-1">
                    <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                    {{ __('install.smtp.subtitle') }}
                </p>
            </div>
        </div>

        {{-- Поля --}}
        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if ($errors->any())
                <div class="bg-gray-900 text-white text-xs rounded-2xl p-3">
                    <div class="flex items-center gap-1.5 font-semibold mb-1"><i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i> {{ __('install.common.error_title') }}</div>
                    <ul class="list-disc pl-5 space-y-0.5 text-gray-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Коротко о том, что будет, если шаг пропустить --}}
            <div class="hint rounded-2xl px-3 py-2 text-[11px] text-gray-600 flex items-start gap-1.5">
                <i data-lucide="circle-dashed" class="w-3.5 h-3.5 mt-0.5 shrink-0 hint-ico"></i>
                <span>{{ __('install.smtp.optional_note') }}</span>
            </div>

            {{-- Хост + порт --}}
            <div class="grid grid-cols-3 gap-2.5">
                <div class="col-span-2">
                    <label for="mail_host" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="server" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.host') }}
                    </label>
                    <input type="text" name="mail_host" id="mail_host"
                           value="{{ old('mail_host', $mail['host']) }}"
                           placeholder="smtp.example.com"
                           autocomplete="off"
                           title="{{ __('install.smtp.host_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900"
                           required autofocus>
                </div>
                <div>
                    <label for="mail_port" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="plug" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.port') }}
                    </label>
                    <input type="text" name="mail_port" id="mail_port"
                           x-model="port"
                           inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                           title="{{ __('install.smtp.port_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900"
                           required>
                </div>
            </div>

            {{-- Шифрование --}}
            <div>
                <label for="mail_encryption" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.encryption') }}
                </label>
                <select name="mail_encryption" id="mail_encryption"
                        x-model="enc" x-on:change="syncPort()"
                        class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900">
                    <option value="ssl">{{ __('install.smtp.enc_ssl') }}</option>
                    <option value="tls">{{ __('install.smtp.enc_tls') }}</option>
                    <option value="none">{{ __('install.smtp.enc_none') }}</option>
                </select>
            </div>

            {{-- Логин + пароль --}}
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="mail_username" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="user" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.username') }}
                    </label>
                    <input type="text" name="mail_username" id="mail_username"
                           value="{{ old('mail_username', $mail['username']) }}"
                           placeholder="you@example.com"
                           autocomplete="off"
                           title="{{ __('install.smtp.username_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900">
                </div>
                <div>
                    <label for="mail_password" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="lock" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.password') }}
                    </label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'"
                               name="mail_password" id="mail_password"
                               value="{{ old('mail_password') }}"
                               placeholder="●●●●●●"
                               autocomplete="new-password"
                               title="{{ __('install.smtp.password_tip') }}"
                               class="w-full pr-10 px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900">
                        <button type="button"
                                class="absolute right-1.5 inset-y-0 my-auto w-7 h-7 grid place-items-center rounded-lg text-gray-400 hover:text-gray-800 hover:bg-gray-100"
                                x-on:click="showPass = !showPass"
                                :title="showPass ? @js(__('install.common.hide_password')) : @js(__('install.common.show_password'))">
                            <span x-show="!showPass" class="grid place-items-center"><i data-lucide="eye" class="w-4 h-4"></i></span>
                            <span x-show="showPass" x-cloak class="grid place-items-center"><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Отправитель --}}
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="mail_from_address" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="at-sign" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.from_address') }}
                    </label>
                    <input type="email" name="mail_from_address" id="mail_from_address"
                           value="{{ old('mail_from_address', $mail['from_address']) }}"
                           placeholder="noreply@example.com"
                           autocomplete="off"
                           title="{{ __('install.smtp.from_address_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900"
                           required>
                </div>
                <div>
                    <label for="mail_from_name" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="type" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.from_name') }}
                    </label>
                    <input type="text" name="mail_from_name" id="mail_from_name"
                           value="{{ old('mail_from_name', $mail['from_name']) }}"
                           placeholder="RU CMS"
                           autocomplete="off"
                           title="{{ __('install.smtp.from_name_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900">
                </div>
            </div>

            {{-- Проверка соединения --}}
            <label class="smtp-verify flex items-center gap-2.5 cursor-pointer rounded-2xl border-2 border-gray-200 p-3 bg-white/60 transition-all">
                <input type="checkbox" name="smtp_verify" value="1" checked
                       class="w-4 h-4 border-gray-300" style="accent-color:var(--accent)">
                <div>
                    <span class="text-sm font-medium text-gray-900">{{ __('install.smtp.verify') }}</span>
                    <p class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1">
                        <i data-lucide="plug-zap" class="w-3 h-3"></i>
                        {{ __('install.smtp.verify_note') }}
                    </p>
                </div>
            </label>

            {{-- Подсказки --}}
            <div class="hint rounded-xl px-3 py-2 text-[11px] text-gray-600 flex items-start gap-1.5">
                <i data-lucide="life-buoy" class="w-3.5 h-3.5 mt-0.5 shrink-0 hint-ico"></i>
                <span>
                    @if (!empty($adminEmail))
                        {!! __('install.smtp.help_with_email', ['email' => e($adminEmail)]) !!}
                    @else
                        {!! __('install.smtp.help') !!}
                    @endif
                </span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-white/50 mt-3 flex items-center justify-between gap-2">
            <a href="{{ route('install.admin') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <div class="flex items-center gap-2">
                <button type="submit" name="smtp_skip" value="1" formnovalidate
                        data-tip="{{ __('install.smtp.skip_tip') }}"
                        class="ui-btn inline-flex items-center gap-1.5 bg-white/70 hover:bg-white text-gray-600 px-4 py-2.5 rounded-xl text-sm font-semibold border border-white/70">
                    <i data-lucide="skip-forward" class="w-4 h-4"></i> {{ __('install.smtp.skip') }}
                </button>
                <button type="submit"
                        class="ui-btn ui-btn-primary inline-flex items-center gap-2 bg-gray-900 hover:bg-black disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold"
                        :disabled="submitting">
                    <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                    </svg>
                    <i data-lucide="save" class="w-4 h-4" x-show="!submitting"></i>
                    <span x-text="submitting ? @js(__('install.smtp.submitting')) : @js(__('install.smtp.submit'))"></span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    .smtp-verify:has(input:checked) {
        border-color: var(--accent);
        box-shadow: 0 10px 22px -14px color-mix(in srgb, var(--accent) 55%, transparent);
    }
</style>
@endpush
@endsection
