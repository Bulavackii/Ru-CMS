@extends('layouts.frontend-install')

@section('accent', '#16a34a')

@section('content')
<div class="w-full max-w-2xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        @php $hasErrors = collect($requirements ?? [])->contains(false); @endphp

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'requirements'])
            <div class="text-center">
                <div class="accent-badge mx-auto w-10 h-10 rounded-xl text-white grid place-items-center mb-2">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">{{ __('install.requirements.title') }}</h2>
                <p class="text-gray-500 text-xs flex items-center justify-center gap-1">
                    <i data-lucide="scan-search" class="w-3.5 h-3.5"></i>
                    {{ __('install.requirements.subtitle') }}
                </p>
            </div>
        </div>

        {{-- Список: две колонки, скроллится внутри при нехватке высоты --}}
        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0">
            <div class="rounded-2xl border border-gray-200 overflow-hidden">
                <div class="grid sm:grid-cols-2 divide-y sm:divide-y-0 divide-gray-100">
                    @foreach ($requirements as $label => $ok)
                        @php
                            // Ключи требований техничные и стабильные (их формирует
                            // контроллер), а вот расшифровка к ним — переводимая.
                            $reqTip = match ($label) {
                                'PHP >= 8.5'                  => __('install.requirements.tip_php', ['version' => PHP_VERSION]),
                                'PDO PostgreSQL (pdo_pgsql)'  => __('install.requirements.tip_pgsql'),
                                'Fileinfo'                    => __('install.requirements.tip_fileinfo'),
                                'Writable: storage/'          => __('install.requirements.tip_storage'),
                                'Writable: bootstrap/cache'   => __('install.requirements.tip_bootstrap'),
                                default                       => __('install.requirements.tip_default'),
                            };
                        @endphp
                        <div class="px-4 py-2.5 flex items-center justify-between gap-2 sm:odd:border-r sm:border-b sm:last:border-b-0 border-gray-100"
                             data-tip="{{ $reqTip }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="{{ $ok ? 'check-circle-2' : 'x-circle' }}"
                                   class="w-4 h-4 shrink-0 {{ $ok ? 'text-green-600' : 'text-red-500' }}"></i>
                                <span class="text-gray-800 text-xs font-medium truncate">{{ $label }}</span>
                            </div>
                            <span class="text-[10px] font-bold shrink-0 px-1.5 py-0.5 rounded-full {{ $ok ? 'bg-green-600 text-white' : 'bg-red-500 text-white' }}">
                                {{ $ok ? __('install.requirements.ok') : __('install.requirements.fail') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($hasErrors)
                <div class="hint mt-3 rounded-2xl p-3 text-gray-700 text-xs">
                    <div class="font-semibold mb-1 flex items-center gap-1.5">
                        <i data-lucide="wrench" class="w-3.5 h-3.5 hint-ico"></i> {{ __('install.requirements.fix_title') }}
                    </div>
                    <ul class="space-y-1 pl-5 list-disc">
                        <li>{!! __('install.requirements.fix_php') !!}</li>
                        <li>{!! __('install.requirements.fix_ext') !!}</li>
                        <li>{!! __('install.requirements.fix_perms') !!}</li>
                        <li>{{ __('install.requirements.fix_retry') }}</li>
                    </ul>
                </div>
            @else
                <div class="hint mt-3 rounded-2xl px-3 py-2 text-xs text-gray-600 flex items-center justify-center gap-2">
                    <i data-lucide="party-popper" class="w-3.5 h-3.5 hint-ico"></i>
                    {{ __('install.requirements.all_ok') }}
                </div>
            @endif
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                <a href="{{ route('install.database') }}"
                   class="ui-btn ui-btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-2 {{ $hasErrors ? 'pointer-events-none opacity-40' : '' }} bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl text-sm font-semibold">
                    <span>{{ __('install.common.continue') }}</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
                <a href="{{ url()->current() }}"
                   class="ui-btn w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/70 hover:bg-white text-gray-800 px-5 py-2.5 rounded-xl text-sm font-semibold border border-white/70">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i> {{ __('install.requirements.recheck') }}
                </a>
                <a href="{{ route('install.welcome') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 px-3 py-2.5 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
