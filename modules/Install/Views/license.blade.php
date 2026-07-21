@extends('layouts.frontend-install')

@section('accent', '#a855f7')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'license'])
            <div class="text-center">
                <div class="accent-badge mx-auto w-10 h-10 rounded-xl text-white grid place-items-center mb-2">
                    <i data-lucide="key-round" class="w-5 h-5"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('install.license.title') }}</h1>
                <p class="text-gray-500 text-xs">{{ __('install.license.subtitle') }}</p>
            </div>
        </div>

        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-4">
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
                    <label class="flex items-center gap-2.5 p-3 border-2 rounded-2xl cursor-pointer transition-all bg-white/60"
                           :class="type === 'license' ? '' : 'border-gray-200 hover:border-gray-400'"
                           :style="type === 'license' ? 'border-color:var(--accent); box-shadow:0 10px 22px -14px color-mix(in srgb, var(--accent) 55%, transparent)' : ''"
                           data-tip="{{ __('install.license.type_license_tip') }}">
                        <input type="radio" name="activation_type" value="license" x-model="type" class="sr-only">
                        <i data-lucide="key-round" class="w-4 h-4 shrink-0" :class="type === 'license' ? '' : 'text-gray-400'" :style="type === 'license' ? 'color:var(--accent)' : ''"></i>
                        <div>
                            <div class="font-semibold text-xs text-gray-900">{{ __('install.license.type_license') }}</div>
                            <div class="text-[10px] text-gray-400">{{ __('install.license.type_license_sub') }}</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-2.5 p-3 border-2 rounded-2xl cursor-pointer transition-all bg-white/60"
                           :class="type === 'promo' ? '' : 'border-gray-200 hover:border-gray-400'"
                           :style="type === 'promo' ? 'border-color:var(--accent); box-shadow:0 10px 22px -14px color-mix(in srgb, var(--accent) 55%, transparent)' : ''"
                           data-tip="{{ __('install.license.type_promo_tip') }}">
                        <input type="radio" name="activation_type" value="promo" x-model="type" class="sr-only">
                        <i data-lucide="ticket-percent" class="w-4 h-4 shrink-0" :class="type === 'promo' ? '' : 'text-gray-400'" :style="type === 'promo' ? 'color:var(--accent)' : ''"></i>
                        <div>
                            <div class="font-semibold text-xs text-gray-900">{{ __('install.license.type_promo') }}</div>
                            <div class="text-[10px] text-gray-400">{{ __('install.license.type_promo_sub') }}</div>
                        </div>
                    </label>
                </div>

                <div x-show="type === 'license'" x-cloak>
                    <label for="license_key" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="key" class="w-3 h-3 text-gray-400"></i> {{ __('install.license.key_label') }}
                    </label>
                    <input type="text"
                           name="license_key" id="license_key"
                           value="{{ old('license_key') }}"
                           placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX"
                           title="{{ __('install.license.key_tip') }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900 text-gray-900 font-mono text-xs"
                           autocomplete="off">
                    <p class="mt-1 text-[11px] text-gray-400 flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3"></i> {{ __('install.license.key_note') }}
                    </p>
                </div>

                <div x-show="type === 'promo'" x-cloak>
                    <label for="promo_code" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="ticket" class="w-3 h-3 text-gray-400"></i> {{ __('install.license.promo_label') }}
                    </label>
                    <input type="text"
                           name="promo_code" id="promo_code"
                           value="{{ old('promo_code') }}"
                           placeholder="{{ __('install.license.promo_placeholder') }}"
                           title="{{ __('install.license.promo_tip') }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900 text-gray-900 font-mono text-xs uppercase"
                           autocomplete="off">
                    <p class="mt-1 text-[11px] text-gray-400 flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3"></i> {{ __('install.license.promo_note') }}
                    </p>
                </div>

                <div class="hint rounded-xl px-3 py-2 text-[11px] text-gray-500 flex items-start gap-1.5">
                    <i data-lucide="badge-info" class="w-3.5 h-3.5 mt-0.5 shrink-0 hint-ico"></i>
                    <span>{{ __('install.license.hint') }}</span>
                </div>
            </form>

            @if ($developerMode ?? false)
                <form method="POST" action="{{ route('install.license') }}">
                    @csrf
                    <input type="hidden" name="developer_skip" value="1">
                    <button type="submit"
                            class="ui-btn w-full inline-flex items-center justify-center gap-2 bg-white/70 hover:bg-white text-gray-900 px-5 py-2.5 rounded-xl text-sm font-semibold border-2 border-dashed border-gray-400"
                            data-tip="{{ __('install.license.dev_skip_tip') }}">
                        <i data-lucide="terminal" class="w-4 h-4"></i>
                        <span>{{ __('install.license.dev_skip') }}</span>
                    </button>
                    <p class="mt-1.5 text-center text-[10px] text-gray-400 flex items-center justify-center gap-1">
                        <i data-lucide="eye" class="w-3 h-3"></i>
                        {!! __('install.license.dev_note') !!}
                    </p>
                </form>
            @endif
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3 flex items-center justify-between">
            <a href="{{ route('install.smtp') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <button type="submit" form="license-form"
                    class="ui-btn ui-btn-primary inline-flex items-center gap-2 bg-gray-900 hover:bg-black disabled:opacity-60 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">
                <i data-lucide="badge-check" class="w-4 h-4"></i>
                <span>{{ __('install.license.submit') }}</span>
            </button>
        </div>
    </div>
</div>
@endsection
