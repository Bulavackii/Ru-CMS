@extends('layouts.frontend-install')

@section('accent', '#0d9488')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.demo') }}"
          class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">
        @csrf

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'demo'])
            <div class="text-center">
                <div class="accent-badge mx-auto w-10 h-10 rounded-xl text-white grid place-items-center mb-2">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('install.demo.title') }}</h1>
                <p class="text-gray-500 text-xs">{{ __('install.demo.subtitle') }}</p>
            </div>
        </div>

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

            {{-- Что входит: компактная сетка 2x2 --}}
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3">
                <h3 class="font-semibold text-xs text-gray-900 mb-2 flex items-center gap-1.5">
                    <i data-lucide="package-open" class="w-3.5 h-3.5"></i> {{ __('install.demo.included') }}
                </h3>
                <div class="grid grid-cols-2 gap-1.5 text-[11px] text-gray-600">
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" data-tip="{{ __('install.demo.categories_tip') }}">
                        <i data-lucide="folder-tree" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> {{ __('install.demo.categories') }}
                    </div>
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" data-tip="{{ __('install.demo.news_tip') }}">
                        <i data-lucide="newspaper" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> {{ __('install.demo.news') }}
                    </div>
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" data-tip="{{ __('install.demo.menu_tip') }}">
                        <i data-lucide="menu" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> {{ __('install.demo.menu') }}
                    </div>
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" data-tip="{{ __('install.demo.structure_tip') }}">
                        <i data-lucide="layout-template" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> {{ __('install.demo.structure') }}
                    </div>
                </div>
            </div>

            <label class="demo-toggle flex items-start gap-2.5 cursor-pointer rounded-2xl border-2 border-gray-200 p-3 bg-white/60 transition-all">
                <input type="checkbox" name="install_demo" value="1" {{ $installDemo ? 'checked' : '' }}
                       class="mt-0.5 w-4 h-4 border-gray-300" style="accent-color:var(--accent)">
                <div>
                    <span class="text-sm font-medium text-gray-900">{{ __('install.demo.toggle') }}</span>
                    <p class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                        {{ __('install.demo.toggle_note') }}
                    </p>
                </div>
            </label>

            <div class="hint rounded-xl px-3 py-2 text-[11px] text-gray-500 flex items-start gap-1.5">
                <i data-lucide="lightbulb" class="w-3.5 h-3.5 mt-0.5 shrink-0 hint-ico"></i>
                <span>{{ __('install.demo.hint') }}</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3 flex items-center justify-between">
            <a href="{{ route('install.license') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <button type="submit"
                    class="ui-btn ui-btn-primary inline-flex items-center gap-2 bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl text-sm font-semibold">
                <i data-lucide="flag" class="w-4 h-4"></i>
                <span>{{ __('install.demo.submit') }}</span>
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
    .demo-toggle:hover { border-color: color-mix(in srgb, var(--accent) 55%, #cbd5e1); }
    .demo-toggle:has(input:checked) {
        border-color: var(--accent);
        box-shadow: 0 10px 22px -14px color-mix(in srgb, var(--accent) 55%, transparent);
    }
</style>
@endpush
@endsection
