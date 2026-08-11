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

        {{-- Шапка шага — полосой, как на остальных шагах. Пометку
             «необязательный» ставим прямо у названия: чтобы её увидели
             раньше, чем начнут искать кнопку «Пропустить» внизу. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="mail" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 05 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">
                    {{ __('install.smtp.title') }}
                    <span class="ins-tag ins-tag--soft">
                        <i data-lucide="circle-dashed" class="w-3 h-3"></i> {{ __('install.smtp.optional') }}
                    </span>
                </h1>
                <p class="ins-head__about">{{ __('install.about.smtp') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 pt-4 shrink-0">
            @include('Install::partials.steps', ['current' => 'smtp'])
        </div>

        {{-- Поля --}}
        <div class="px-5 sm:px-6 py-4 overflow-y-auto install-scroll min-h-0 space-y-3">
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

            {{-- Что будет, если шаг пропустить: главное сообщение шага,
                 поэтому заметка с акцентной гранью, а не бледная полоса. --}}
            <p class="ins-callout">
                <i data-lucide="circle-dashed" class="w-3.5 h-3.5"></i>
                <span>{{ __('install.smtp.optional_note') }}</span>
            </p>

            {{-- Хост + порт --}}
            <div class="grid grid-cols-3 gap-2.5">
                <div class="col-span-2">
                    <label for="mail_host" class="ins-label">
                        <i data-lucide="server" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.host') }}
                    </label>
                    <input type="text" name="mail_host" id="mail_host"
                           value="{{ old('mail_host', $mail['host']) }}"
                           placeholder="smtp.example.com"
                           autocomplete="off"
                           title="{{ __('install.smtp.host_tip') }}"
                           class="ins-input"
                           required autofocus>
                </div>
                <div>
                    <label for="mail_port" class="ins-label">
                        <i data-lucide="plug" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.port') }}
                    </label>
                    <input type="text" name="mail_port" id="mail_port"
                           x-model="port"
                           inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                           title="{{ __('install.smtp.port_tip') }}"
                           class="ins-input"
                           required>
                </div>
            </div>

            {{-- Шифрование --}}
            <div>
                <label for="mail_encryption" class="ins-label">
                    <i data-lucide="shield-check" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.encryption') }}
                </label>
                <select name="mail_encryption" id="mail_encryption"
                        x-model="enc" x-on:change="syncPort()"
                        class="ins-input">
                    <option value="ssl">{{ __('install.smtp.enc_ssl') }}</option>
                    <option value="tls">{{ __('install.smtp.enc_tls') }}</option>
                    <option value="none">{{ __('install.smtp.enc_none') }}</option>
                </select>
            </div>

            {{-- Логин + пароль --}}
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="mail_username" class="ins-label">
                        <i data-lucide="user" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.username') }}
                    </label>
                    <input type="text" name="mail_username" id="mail_username"
                           value="{{ old('mail_username', $mail['username']) }}"
                           placeholder="you@example.com"
                           autocomplete="off"
                           title="{{ __('install.smtp.username_tip') }}"
                           class="ins-input">
                </div>
                <div>
                    <label for="mail_password" class="ins-label">
                        <i data-lucide="lock" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.password') }}
                    </label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'"
                               name="mail_password" id="mail_password"
                               value="{{ old('mail_password') }}"
                               placeholder="●●●●●●"
                               autocomplete="new-password"
                               title="{{ __('install.smtp.password_tip') }}"
                               class="ins-input pr-10">
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
                    <label for="mail_from_address" class="ins-label">
                        <i data-lucide="at-sign" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.from_address') }}
                    </label>
                    <input type="email" name="mail_from_address" id="mail_from_address"
                           value="{{ old('mail_from_address', $mail['from_address']) }}"
                           placeholder="noreply@example.com"
                           autocomplete="off"
                           title="{{ __('install.smtp.from_address_tip') }}"
                           class="ins-input"
                           required>
                </div>
                <div>
                    <label for="mail_from_name" class="ins-label">
                        <i data-lucide="type" class="w-3 h-3 text-gray-400"></i> {{ __('install.smtp.from_name') }}
                    </label>
                    <input type="text" name="mail_from_name" id="mail_from_name"
                           value="{{ old('mail_from_name', $mail['from_name']) }}"
                           placeholder="RU CMS"
                           autocomplete="off"
                           title="{{ __('install.smtp.from_name_tip') }}"
                           class="ins-input">
                </div>
            </div>

            {{-- Проверка соединения --}}
            <label class="smtp-verify ins-check">
                <input type="checkbox" name="smtp_verify" value="1" checked
                       class="w-4 h-4 border-gray-300" style="accent-color:var(--accent)">
                <div>
                    <span class="ins-check__title">{{ __('install.smtp.verify') }}</span>
                    <p class="ins-check__note">
                        <i data-lucide="plug-zap" class="w-3 h-3"></i>
                        {{ __('install.smtp.verify_note') }}
                    </p>
                </div>
            </label>

            {{-- Про порт: 465 у многих провайдеров закрыт наружу, и тогда
                 подключение просто упирается в таймаут — причина по виду
                 не отличается от неверного пароля. --}}
            <p class="ins-callout">
                <i data-lucide="plug" class="w-3.5 h-3.5"></i>
                <span>{{ __('install.smtp.port_note') }}</span>
            </p>

            {{-- Подсказки --}}
            <div class="ins-help">
                <span class="ins-help__cap">
                    <i data-lucide="life-buoy" class="w-3.5 h-3.5"></i> {{ __('install.smtp.help_cap') }}
                </span>
                <span class="ins-help__text">
                    @if (!empty($adminEmail))
                        {!! __('install.smtp.help_with_email', ['email' => e($adminEmail)]) !!}
                    @else
                        {!! __('install.smtp.help') !!}
                    @endif
                </span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="ins-foot shrink-0 flex items-center justify-between gap-2">
            <a href="{{ route('install.admin') }}" class="ins-back">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <div class="flex items-center gap-2">
                <button type="submit" name="smtp_skip" value="1" formnovalidate
                        data-tip="{{ __('install.smtp.skip_tip') }}"
                        class="ins-act ins-act--dim">
                    <i data-lucide="skip-forward" class="w-4 h-4"></i> {{ __('install.smtp.skip') }}
                </button>
                <button type="submit" class="ins-act ins-act--go" :disabled="submitting">
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
