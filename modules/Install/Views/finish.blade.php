@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-xl">
    <div class="rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] animate-fade-in overflow-hidden">
        <div class="px-6 sm:px-10 pt-6">
            @include('Install::partials.steps', ['current' => 'finish'])
        </div>

        <div class="p-6 sm:p-10 text-center space-y-6">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-500/10 text-green-600 grid place-items-center">
                <i data-lucide="check-circle-2" class="w-9 h-9"></i>
            </div>

            <h2 class="text-2xl font-bold text-gray-900">Установка завершена!</h2>

            <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                Поздравляем! Ru CMS успешно установлена и готова к работе.<br>
                Далее вы можете открыть сайт или перейти в админ-панель для продолжения настройки.
            </p>

            @if (!empty($warnings))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left">
                    <div class="text-sm font-semibold text-amber-800 mb-2 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        Установка завершена, но кое-что стоит проверить
                    </div>
                    <ul class="text-xs text-amber-800 space-y-1.5 list-disc pl-5">
                        @foreach ($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedCountry)
                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4 text-left">
                    <div class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
                        <span class="text-lg">{{ $selectedCountry['flag'] ?? '🌍' }}</span>
                        <span>Настройки локализации применены</span>
                    </div>
                    <div class="text-xs text-blue-800 grid grid-cols-2 gap-y-1">
                        <div><strong>Страна:</strong> {{ $selectedCountry['name'] }}</div>
                        <div><strong>Язык:</strong> {{ $selectedCountry['locale'] }}</div>
                        <div><strong>Часовой пояс:</strong> {{ $selectedCountry['timezone'] }}</div>
                        <div><strong>Валюта:</strong> {{ $selectedCountry['currency_code'] }} ({{ $selectedCountry['currency_symbol'] }})</div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
                <a href="/"
                   class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-green-500/25 transition-colors">
                    <i data-lucide="home" class="w-4 h-4"></i> На сайт
                </a>
                <a href="{{ url('/admin') }}"
                   class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/25 transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> В админ-панель
                </a>
                <button type="button"
                        id="copy-admin-url"
                        data-url="{{ url('/admin') }}"
                        class="inline-flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-3 rounded-xl text-sm font-semibold border border-gray-200 transition-colors">
                    <i data-lucide="clipboard" class="w-4 h-4"></i> Скопировать URL админки
                </button>
            </div>

            {{-- ✨ Краткий обзор возможностей --}}
            <div class="bg-blue-50/60 border border-blue-100 rounded-2xl p-6 mt-2">
                <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-blue-600"></i>
                    <span>Ключевые возможности системы</span>
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm text-left">
                    <div class="flex items-center gap-2"><i data-lucide="blocks" class="w-4 h-4 text-blue-600"></i><span class="text-gray-700">Модульная архитектура</span></div>
                    <div class="flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-blue-600"></i><span class="text-gray-700">Безопасность 2FA</span></div>
                    <div class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-blue-600"></i><span class="text-gray-700">Высокая производительность</span></div>
                    <div class="flex items-center gap-2"><i data-lucide="globe" class="w-4 h-4 text-blue-600"></i><span class="text-gray-700">Мультиязычность</span></div>
                    <div class="flex items-center gap-2"><i data-lucide="database-backup" class="w-4 h-4 text-blue-600"></i><span class="text-gray-700">Автоматические бэкапы</span></div>
                    <div class="flex items-center gap-2"><i data-lucide="plug" class="w-4 h-4 text-blue-600"></i><span class="text-gray-700">REST API + Swagger</span></div>
                </div>
            </div>

            {{-- 🧩 Следующие шаги (рекомендации) --}}
            <div class="text-left bg-gray-50 border border-gray-200 rounded-2xl p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <i data-lucide="list-checks" class="w-4 h-4 text-blue-500"></i> Рекомендации после установки
                </h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-start gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 mt-0.5 text-gray-400 shrink-0"></i>
                        Проверьте <span class="font-mono">.env</span>: <span class="font-mono">APP_NAME</span>, <span class="font-mono">APP_URL</span>, <span class="font-mono">APP_TIMEZONE</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="mail" class="w-4 h-4 mt-0.5 text-gray-400 shrink-0"></i>
                        Настройте почту (<span class="font-mono">MAIL_MAILER</span>, <span class="font-mono">MAIL_HOST</span>, <span class="font-mono">MAIL_FROM_ADDRESS</span>) для отправки уведомлений.
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mt-0.5 text-gray-400 shrink-0"></i>
                        Включите кэш и очередь (рекомендуется Redis) — см. <span class="font-mono">CACHE_STORE</span>, <span class="font-mono">QUEUE_CONNECTION</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="clock" class="w-4 h-4 mt-0.5 text-gray-400 shrink-0"></i>
                        Добавьте CRON: <span class="font-mono">* * * * * php /path/to/artisan schedule:run &gt;&gt; /dev/null 2&gt;&1</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="shield" class="w-4 h-4 mt-0.5 text-gray-400 shrink-0"></i>
                        Проверьте права на <span class="font-mono">storage/</span> и <span class="font-mono">bootstrap/cache</span>, а также режим <span class="font-mono">APP_ENV</span>/<span class="font-mono">APP_DEBUG</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="image" class="w-4 h-4 mt-0.5 text-gray-400 shrink-0"></i>
                        В админке откройте «Темы» и примените/настройте активную тему и набор иконок.
                    </li>
                </ul>
            </div>

            <p class="text-xs text-gray-400">
                Мастер установки автоматически заблокирован (создан <span class="font-mono">storage/install.lock</span>).
            </p>
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
                    btn.innerHTML = '<i data-lucide="clipboard" class="w-4 h-4"></i> Скопировать URL админки';
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
