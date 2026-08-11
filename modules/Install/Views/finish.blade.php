@extends('layouts.frontend-install')

@section('accent', '#16a34a')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка шага — полосой, как на остальных шагах. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 07 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">{{ __('install.finish.title') }}</h1>
                <p class="ins-head__about">{{ __('install.finish.subtitle') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 pt-4 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'finish'])

            {{-- Авто-переход в панель. На нуле строка меняется на
                 «переходим…»: раньше счётчик замирал на «через 0 с» всё
                 время, пока грузится следующая страница, и выглядело это
                 как зависший переход. --}}
            <div x-data="{ seconds: 5, cancelled: false, going: false }"
                 x-init="const t = setInterval(() => {
                            if (cancelled) { clearInterval(t); return; }
                            seconds = Math.max(0, seconds - 1);
                            if (seconds === 0) {
                                clearInterval(t);
                                going = true;
                                window.location.href = @js(url('/'));
                            }
                         }, 1000)"
                 x-show="!cancelled" x-cloak
                 class="ins-countdown">
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>

                <span x-show="!going">
                    {{ __('install.finish.redirect_pre') }}
                    <b x-text="seconds"></b>
                    {{ __('install.finish.redirect_post') }}
                </span>
                <span x-show="going" x-cloak>{{ __('install.finish.redirect_now') }}</span>

                <button type="button" class="ins-countdown__stay" x-on:click="cancelled = true">
                    {{ __('install.finish.stay') }}
                </button>
            </div>
        </div>

        <div class="px-5 sm:px-6 py-4 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if (!empty($warnings))
                <div class="ins-help">
                    <span class="ins-help__cap">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> {{ __('install.finish.warnings') }}
                    </span>
                    <ul class="ins-warn-list">
                        @foreach ($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedCountry)
                <div class="ins-help">
                    <span class="ins-help__cap">
                        <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                        {{ __('install.finish.localization', ['country' => $selectedCountry['native_name'] ?? $selectedCountry['name']]) }}
                    </span>
                    <div class="ins-facts">
                        <div class="ins-facts__row"><i data-lucide="map-pin" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['native_name'] ?? $selectedCountry['name'] }}</div>
                        <div class="ins-facts__row"><i data-lucide="languages" class="w-3 h-3 text-gray-400"></i> {{ strtoupper($selectedCountry['locale']) }}</div>
                        <div class="ins-facts__row"><i data-lucide="clock" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['timezone'] }}</div>
                        <div class="ins-facts__row"><i data-lucide="banknote" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['currency_code'] }} ({{ $selectedCountry['currency_symbol'] }})</div>
                    </div>
                </div>
            @endif

            {{-- Рекомендации: компактные строки с иконками --}}
            <div class="ins-help">
                <span class="ins-help__cap">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5"></i> {{ __('install.finish.recommend') }}
                </span>
                <div class="ins-facts ins-facts--wide">
                    <div class="ins-facts__row" data-tip="{{ __('install.finish.rec_env_tip') }}">
                        <i data-lucide="file-cog" class="w-3 h-3 text-gray-400 shrink-0"></i> {!! __('install.finish.rec_env') !!}
                    </div>
                    <div class="ins-facts__row" data-tip="{{ __('install.finish.rec_mail_tip') }}">
                        <i data-lucide="mail" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_mail') }}
                    </div>
                    <div class="ins-facts__row" data-tip="{{ __('install.finish.rec_cache_tip') }}">
                        <i data-lucide="zap" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_cache') }}
                    </div>
                    <div class="ins-facts__row" data-tip="{{ __('install.finish.rec_cron_tip') }}">
                        <i data-lucide="clock" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_cron') }}
                    </div>
                    <div class="ins-facts__row" data-tip="{{ __('install.finish.rec_perms_tip') }}">
                        <i data-lucide="shield" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_perms') }}
                    </div>
                    <div class="ins-facts__row" data-tip="{{ __('install.finish.rec_theme_tip') }}">
                        <i data-lucide="palette" class="w-3 h-3 text-gray-400 shrink-0"></i> {{ __('install.finish.rec_theme') }}
                    </div>
                </div>
            </div>

            <p class="ins-locked">
                <i data-lucide="lock" class="w-3 h-3"></i>
                {!! __('install.finish.locked') !!}
            </p>
        </div>

        {{-- Кнопки --}}
        <div class="ins-foot shrink-0">
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('admin.dashboard') }}" class="ins-act">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> {{ __('install.finish.to_admin') }}
                </a>
                <a href="/" class="ins-act ins-act--go">
                    <i data-lucide="home" class="w-4 h-4"></i> {{ __('install.finish.to_site') }}
                </a>
                <button type="button"
                        id="copy-admin-url"
                        data-url="{{ route('admin.dashboard') }}"
                        data-copy-label="{{ __('install.finish.copy_url') }}"
                        data-copied-label="{{ __('install.finish.copied') }}"
                        data-tip="{{ __('install.finish.copy_tip') }}"
                        class="ins-act ins-act--dim">
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
            var url = btn.getAttribute('data-url') || '{{ route('admin.dashboard') }}';
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
