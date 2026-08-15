@extends('layouts.frontend-install')

@section('accent', '#a855f7')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка шага — полосой, как на остальных шагах. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="key-round" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 06 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">{{ __('install.license.title') }}</h1>
                <p class="ins-head__about">{{ __('install.about.license') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 pt-4 shrink-0">
            @include('Install::partials.steps', ['current' => 'license'])
        </div>

        <div class="px-5 sm:px-6 py-4 overflow-y-auto install-scroll min-h-0 space-y-4">
            <form method="POST" action="{{ route('install.license') }}"
                  x-data="{ type: 'license', submitting: false }"
                  x-on:submit="submitting=true"
                  id="license-form"
                  class="space-y-3">
                @csrf

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

                {{-- Выбор типа --}}
                <div class="grid grid-cols-2 gap-2.5">
                    <label class="ins-mode"
                           :class="type === 'license' ? '' : 'border-gray-200'"
                           :style="type === 'license' ? 'border-color:var(--accent); box-shadow:0 10px 22px -14px color-mix(in srgb, var(--accent) 55%, transparent)' : ''"
                           data-tip="{{ __('install.license.type_license_tip') }}">
                        <input type="radio" name="activation_type" value="license" x-model="type" class="sr-only">
                        <i data-lucide="key-round" class="w-4 h-4 shrink-0" :class="type === 'license' ? '' : 'text-gray-400'" :style="type === 'license' ? 'color:var(--accent)' : ''"></i>
                        <div>
                            <div class="ins-mode__t">{{ __('install.license.type_license') }}</div>
                            <div class="ins-mode__s">{{ __('install.license.type_license_sub') }}</div>
                        </div>
                    </label>
                    <label class="ins-mode"
                           :class="type === 'promo' ? '' : 'border-gray-200'"
                           :style="type === 'promo' ? 'border-color:var(--accent); box-shadow:0 10px 22px -14px color-mix(in srgb, var(--accent) 55%, transparent)' : ''"
                           data-tip="{{ __('install.license.type_promo_tip') }}">
                        <input type="radio" name="activation_type" value="promo" x-model="type" class="sr-only">
                        <i data-lucide="ticket-percent" class="w-4 h-4 shrink-0" :class="type === 'promo' ? '' : 'text-gray-400'" :style="type === 'promo' ? 'color:var(--accent)' : ''"></i>
                        <div>
                            <div class="ins-mode__t">{{ __('install.license.type_promo') }}</div>
                            <div class="ins-mode__s">{{ __('install.license.type_promo_sub') }}</div>
                        </div>
                    </label>
                </div>

                <div x-show="type === 'license'" x-cloak>
                    <label for="license_key" class="ins-label">
                        <i data-lucide="key" class="w-3 h-3 text-gray-400"></i> {{ __('install.license.key_label') }}
                    </label>
                    <input type="text"
                           name="license_key" id="license_key"
                           value="{{ old('license_key') }}"
                           placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX"
                           title="{{ __('install.license.key_tip') }}"
                           class="ins-input font-mono"
                           autocomplete="off">
                    <p class="ins-callout">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        <span>{{ __('install.license.key_note') }}</span>
                    </p>
                </div>

                <div x-show="type === 'promo'" x-cloak>
                    <label for="promo_code" class="ins-label">
                        <i data-lucide="ticket" class="w-3 h-3 text-gray-400"></i> {{ __('install.license.promo_label') }}
                    </label>
                    <input type="text"
                           name="promo_code" id="promo_code"
                           value="{{ old('promo_code') }}"
                           placeholder="{{ __('install.license.promo_placeholder') }}"
                           title="{{ __('install.license.promo_tip') }}"
                           class="ins-input font-mono uppercase"
                           autocomplete="off">
                    <p class="ins-callout">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        <span>{{ __('install.license.promo_note') }}</span>
                    </p>
                </div>

                <div class="ins-help">
                    <span class="ins-help__cap">
                        <i data-lucide="badge-info" class="w-3.5 h-3.5"></i> {{ __('install.license.hint_cap') }}
                    </span>
                    <span class="ins-help__text">{{ __('install.license.hint') }}</span>
                </div>
            </form>

            @if ($developerMode ?? false)
                <form method="POST" action="{{ route('install.license') }}">
                    @csrf
                    <input type="hidden" name="developer_skip" value="1">
                    {{-- Обход лицензии виден, только если ключ действительно
                         прописан в .env — проверяет сам файл, а не env().
                         Пояснений под кнопкой нет намеренно: её видит один
                         разработчик, и объяснять ему нечего. --}}
                    <button type="submit" class="ins-act ins-act--dev">
                        <i data-lucide="terminal" class="w-4 h-4"></i>
                        <span>{{ __('install.license.dev_skip') }}</span>
                    </button>
                </form>
            @endif
        </div>

        {{-- Кнопки --}}
        <div class="ins-foot shrink-0 flex items-center justify-between">
            <a href="{{ route('install.smtp') }}" class="ins-back">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <button type="submit" form="license-form"
                    class="ins-act ins-act--go">
                <i data-lucide="badge-check" class="w-4 h-4"></i>
                <span>{{ __('install.license.submit') }}</span>
            </button>
        </div>
    </div>
</div>
@endsection
