@extends('layouts.frontend-install')

@section('accent', '#16a34a')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3 text-center">
            @include('Install::partials.steps', ['current' => 'finish'])
            <div>
                <div class="accent-badge mx-auto w-12 h-12 rounded-full text-white grid place-items-center mb-2">
                    <i data-lucide="check" class="w-6 h-6"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">{{ __('install.finish.title') }}</h2>
                <p class="text-gray-500 text-xs">{{ __('install.finish.subtitle') }}</p>
            </div>

            {{-- Авто-переход в админку --}}
            <div x-data="{ seconds: 5, cancelled: false }"
                 x-init="const t = setInterval(() => { if (cancelled) { clearInterval(t); return; } seconds--; if (seconds <= 0) { clearInterval(t); window.location.href = @js(url('/admin')); } }, 1000)"
                 x-show="!cancelled" x-cloak
                 class="hint rounded-xl px-3 py-2 text-[11px] text-gray-500 flex items-center justify-center gap-2">
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                <span>{{ __('install.finish.redirect_pre') }} <span class="font-semibold text-gray-900" x-text="seconds"></span> {{ __('install.finish.redirect_post') }}</span>
                <button type="button" class="text-gray-700 hover:text-gray-900 underline font-medium" x-on:click="cancelled = true">{{ __('install.finish.stay') }}</button>
            </div>
        </div>

        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if (!empty($warnings))
                <div class="hint rounded-2xl p-3 text-left">
                    <div class="text-xs font-semibold text-gray-800 mb-1.5 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 hint-ico"></i>
                        {{ __('install.finish.warnings') }}
                    </div>
                    <ul class="text-[11px] text-gray-600 space-y-1 list-disc pl-5">
                        @foreach ($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedCountry)
                <div class="hint rounded-2xl p-3 text-left">
                    <div class="text-xs font-semibold text-gray-900 mb-1.5 flex items-center gap-1.5">
                        <i data-lucide="globe" class="w-3.5 h-3.5 hint-ico"></i>
                        <span>{{ __('install.finish.localization', ['country' => $selectedCountry['native_name'] ?? $selectedCountry['name']]) }}</span>
                    </div>
                    <div class="text-[11px] text-gray-600 grid grid-cols-2 gap-y-0.5">
                        <div class="flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['native_name'] ?? $selectedCountry['name'] }}</div>
                        <div class="flex items-center gap-1"><i data-lucide="languages" class="w-3 h-3 text-gray-400"></i> {{ strtoupper($selectedCountry['locale']) }}</div>
                        <div class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['timezone'] }}</div>
                        <div class="flex items-center gap-1"><i data-lucide="banknote" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['currency_code'] }} ({{ $selectedCountry['currency_symbol'] }})</div>
                    </div>
                </div>
            @endif

            {{-- Рекомендации: компактные строки с иконками --}}
            <div class="hint rounded-2xl p-3 text-left space-y-1.5">
                <h3 class="text-xs font-semibold text-gray-900 flex items-center gap-1.5">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5 hint-ico"></i> {{ __('install.finish.recommend') }}
                </h3>
                <div class="grid sm:grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-gray-600">
                    <div class="flex items-center gap-1.5" data-tip="{{ __('install.finish.rec_env_tip') }}">
                        <i data-lucide="file-cog" class="w-3 h-3 text-gray-400 shrink-0"></i> {!! __('install.finish.rec_env') !!}
                    </div>
                    <div class="flex items-center gap-1.5" data-tip="{{ __('install.finish.rec_mail_tip') }}">
                        <i data-lucide="mail" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_mail') }}
                    </div>
                    <div class="flex items-center gap-1.5" data-tip="{{ __('install.finish.rec_cache_tip') }}">
                        <i data-lucide="zap" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_cache') }}
                    </div>
                    <div class="flex items-center gap-1.5" data-tip="{{ __('install.finish.rec_cron_tip') }}">
                        <i data-lucide="clock" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_cron') }}
                    </div>
                    <div class="flex items-center gap-1.5" data-tip="{{ __('install.finish.rec_perms_tip') }}">
                        <i data-lucide="shield" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_perms') }}
                    </div>
                    <div class="flex items-center gap-1.5" data-tip="{{ __('install.finish.rec_theme_tip') }}">
                        <i data-lucide="palette" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_theme') }}
                    </div>
                </div>
            </div>

            <p class="text-[10px] text-gray-400 text-center flex items-center justify-center gap-1">
                <i data-lucide="lock" class="w-3 h-3"></i>
                {!! __('install.finish.locked') !!}
            </p>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2">
                <a href="{{ url('/admin') }}"
                   class="ui-btn ui-btn-primary inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl text-sm font-semibold">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> {{ __('install.finish.to_admin') }}
                </a>
                <a href="/"
                   class="ui-btn inline-flex items-center justify-center gap-2 bg-white/70 hover:bg-white text-gray-900 px-5 py-2.5 rounded-xl text-sm font-semibold border border-white/70">
                    <i data-lucide="home" class="w-4 h-4"></i> {{ __('install.finish.to_site') }}
                </a>
                <button type="button"
                        id="copy-admin-url"
                        data-url="{{ url('/admin') }}"
                        data-copy-label="{{ __('install.finish.copy_url') }}"
                        data-copied-label="{{ __('install.finish.copied') }}"
                        data-tip="{{ __('install.finish.copy_tip') }}"
                        class="ui-btn inline-flex items-center justify-center gap-2 bg-black/5 hover:bg-black/10 text-gray-600 px-5 py-2.5 rounded-xl text-sm font-semibold border border-white/50">
                    <i data-lucide="clipboard" class="w-4 h-4"></i> {{ __('install.finish.copy_url') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function(){
        var btn = document.getElementById('copy-admin-url');
        if (!btn) return;
        btn.addEventListener('click', function(){
            var url = btn.getAttribute('data-url') || '{{ url('/admin') }}';
            // Подписи приходят из data-атрибутов — так они переводятся вместе
            // со всей страницей и не дублируются строками внутри скрипта.
            var copyLabel = btn.getAttribute('data-copy-label') || 'Copy URL';
            var copiedLabel = btn.getAttribute('data-copied-label') || 'Copied';
            function done() {
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> ' + copiedLabel;
                if (window.lucide) window.lucide.createIcons();
                setTimeout(function(){
                    btn.innerHTML = '<i data-lucide="clipboard" class="w-4 h-4"></i> ' + copyLabel;
                    if (window.lucide) window.lucide.createIcons();
                }, 1800);
            }
            function fallback(text){
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select(); document.execCommand('copy');
                document.body.removeChild(ta);
                done();
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done).catch(function(){ fallback(url); });
            } else {
                fallback(url);
            }
        });
    })();
</script>
@endpush
@endsection
