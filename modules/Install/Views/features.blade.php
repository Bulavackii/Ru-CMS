@extends('layouts.frontend-install')

@section('accent', '#8b5cf6')

@section('content')
<div class="w-full max-w-4xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка: цветной градиент под акцент шага --}}
        <div class="text-white px-6 py-5 text-center shrink-0" style="background:linear-gradient(135deg, var(--accent), #6d28d9)">
            <div class="mx-auto w-10 h-10 rounded-xl bg-white/15 grid place-items-center mb-2">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <h1 class="text-xl font-bold">{{ __('install.features.title') }}</h1>
            <p class="text-sm text-gray-300">{{ __('install.features.subtitle') }}</p>
        </div>

        {{-- Сетка возможностей: скроллится внутри --}}
        <div class="p-4 sm:p-6 overflow-y-auto install-scroll min-h-0">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($features as $feature)
                <div class="feature-card relative rounded-2xl p-4 border bg-white/55"
                    style="{{ ($feature['highlight'] ?? false)
                        ? 'border-color:var(--accent); box-shadow:0 14px 30px -18px color-mix(in srgb, var(--accent) 55%, transparent)'
                        : 'border-color:rgba(0,0,0,.08)' }}"
                    data-tip="{{ $feature['description'] }}">
                    @if($feature['highlight'] ?? false)
                        <span class="accent-badge absolute top-2.5 right-2.5 w-4 h-4 rounded-full text-white grid place-items-center" data-tip="{{ __('install.features.key_tip') }}" data-tip-pos="bottom">
                            <i data-lucide="star" class="w-2.5 h-2.5"></i>
                        </span>
                    @endif
                    <div class="w-8 h-8 rounded-lg text-white grid place-items-center mb-2 {{ ($feature['highlight'] ?? false) ? 'accent-badge' : 'bg-gray-900' }}">
                        <i data-lucide="{{ $feature['icon'] }}" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ $feature['title'] }}</h3>
                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-3">{{ $feature['description'] }}</p>
                </div>
                @endforeach
            </div>

            <div class="hint mt-3 rounded-2xl px-4 py-2.5 flex items-center gap-2 text-xs text-gray-600">
                <i data-lucide="lightbulb" class="w-4 h-4 shrink-0 hint-ico"></i>
                <span>{{ __('install.features.hint') }}</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 py-4 shrink-0 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-2">
            <a href="{{ route('install.requirements') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 font-medium transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>{{ __('install.features.back') }}</span>
            </a>
            <a href="{{ route('install.database') }}"
               class="ui-btn ui-btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl text-sm font-semibold">
                <span>{{ __('install.features.continue') }}</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</div>

@push('styles')
<style>
    .feature-card { transition: transform .18s ease, box-shadow .18s ease; }
    .feature-card:hover { transform: translateY(-3px); }
</style>
@endpush
@endsection
