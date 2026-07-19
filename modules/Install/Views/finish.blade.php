@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <div class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden">

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3 text-center">
            @include('Install::partials.steps', ['current' => 'finish'])
            <div>
                <div class="mx-auto w-12 h-12 rounded-full bg-gray-900 text-white grid place-items-center mb-2">
                    <i data-lucide="check" class="w-6 h-6"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Установка завершена!</h2>
                <p class="text-gray-500 text-xs">Ru CMS готова к работе — откройте сайт или перейдите в панель управления</p>
            </div>

            {{-- Авто-переход в админку --}}
            <div x-data="{ seconds: 5, cancelled: false }"
                 x-init="const t = setInterval(() => { if (cancelled) { clearInterval(t); return; } seconds--; if (seconds <= 0) { clearInterval(t); window.location.href = @js(url('/admin')); } }, 1000)"
                 x-show="!cancelled" x-cloak
                 class="rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-[11px] text-gray-500 flex items-center justify-center gap-2">
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                <span>Переходим в панель управления через <span class="font-semibold text-gray-900" x-text="seconds"></span> с…</span>
                <button type="button" class="text-gray-700 hover:text-gray-900 underline font-medium" x-on:click="cancelled = true">Остаться</button>
            </div>
        </div>

        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if (!empty($warnings))
                <div class="rounded-2xl border border-gray-300 bg-gray-50 p-3 text-left">
                    <div class="text-xs font-semibold text-gray-800 mb-1.5 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        Установка завершена, но кое-что стоит проверить
                    </div>
                    <ul class="text-[11px] text-gray-600 space-y-1 list-disc pl-5">
                        @foreach ($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedCountry)
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 text-left">
                    <div class="text-xs font-semibold text-gray-900 mb-1.5 flex items-center gap-1.5">
                        <span class="text-base leading-none">{{ $selectedCountry['flag'] ?? '🌍' }}</span>
                        <span>Локализация применена</span>
                    </div>
                    <div class="text-[11px] text-gray-600 grid grid-cols-2 gap-y-0.5">
                        <div class="flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['name'] }}</div>
                        <div class="flex items-center gap-1"><i data-lucide="languages" class="w-3 h-3 text-gray-400"></i> {{ strtoupper($selectedCountry['locale']) }}</div>
                        <div class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['timezone'] }}</div>
                        <div class="flex items-center gap-1"><i data-lucide="banknote" class="w-3 h-3 text-gray-400"></i> {{ $selectedCountry['currency_code'] }} ({{ $selectedCountry['currency_symbol'] }})</div>
                    </div>
                </div>
            @endif

            {{-- Рекомендации: компактные строки с иконками --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-3 text-left space-y-1.5">
                <h3 class="text-xs font-semibold text-gray-900 flex items-center gap-1.5">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5"></i> Рекомендации после установки
                </h3>
                <div class="grid sm:grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-gray-600">
                    <div class="flex items-center gap-1.5" title="APP_NAME, APP_URL, APP_TIMEZONE в .env">
                        <i data-lucide="file-cog" class="w-3 h-3 text-gray-400 shrink-0"></i> Проверьте <span class="font-mono">.env</span>
                    </div>
                    <div class="flex items-center gap-1.5" title="MAIL_MAILER, MAIL_HOST, MAIL_FROM_ADDRESS">
                        <i data-lucide="mail" class="w-3 h-3 text-gray-400 shrink-0"></i> Настройте почту
                    </div>
                    <div class="flex items-center gap-1.5" title="CACHE_STORE, QUEUE_CONNECTION — рекомендуется Redis">
                        <i data-lucide="zap" class="w-3 h-3 text-gray-400 shrink-0"></i> Кэш и очередь
                    </div>
                    <div class="flex items-center gap-1.5" title="* * * * * php artisan schedule:run">
                        <i data-lucide="clock" class="w-3 h-3 text-gray-400 shrink-0"></i> CRON-планировщик
                    </div>
                    <div class="flex items-center gap-1.5" title="storage/ и bootstrap/cache — права на запись; APP_DEBUG=false в продакшене">
                        <i data-lucide="shield" class="w-3 h-3 text-gray-400 shrink-0"></i> Права и режим
                    </div>
                    <div class="flex items-center gap-1.5" title="Админка → Темы: активная тема и набор иконок">
                        <i data-lucide="palette" class="w-3 h-3 text-gray-400 shrink-0"></i> Тема и иконки
                    </div>
                </div>
            </div>

            <p class="text-[10px] text-gray-400 text-center flex items-center justify-center gap-1">
                <i data-lucide="lock" class="w-3 h-3"></i>
                Мастер установки заблокирован (создан <span class="font-mono">storage/install.lock</span>)
            </p>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2">
                <a href="{{ url('/admin') }}"
                   class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-gray-900/25 transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> В панель управления
                </a>
                <a href="/"
                   class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-900 px-5 py-2.5 rounded-xl text-sm font-semibold border border-gray-300 transition-colors">
                    <i data-lucide="home" class="w-4 h-4"></i> На сайт
                </a>
                <button type="button"
                        id="copy-admin-url"
                        data-url="{{ url('/admin') }}"
                        title="Скопировать адрес панели управления в буфер обмена"
                        class="inline-flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-600 px-5 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 transition-colors">
                    <i data-lucide="clipboard" class="w-4 h-4"></i> Скопировать URL
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
            function done() {
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Скопировано';
                if (window.lucide) window.lucide.createIcons();
                setTimeout(function(){
                    btn.innerHTML = '<i data-lucide="clipboard" class="w-4 h-4"></i> Скопировать URL';
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
