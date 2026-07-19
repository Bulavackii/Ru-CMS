@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <div class="bg-white shadow-xl rounded-2xl w-full max-w-xl border border-gray-200 animate-fade-in">
        {{-- 🧭 Шаги мастера --}}
        <div class="px-6 sm:px-10 pt-6">
            <ol class="flex items-center justify-center gap-2 text-xs text-gray-500">
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">1. Приветствие</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">2. Требования/БД</li>
                <li class="px-2 py-1 rounded bg-gray-100 font-medium">3. Администратор</li>
                <li class="px-2 py-1 rounded bg-green-600 text-white font-semibold">4. Готово</li>
            </ol>
        </div>

        {{-- ✅ Экран успеха --}}
        <div class="p-6 sm:p-10 text-center space-y-6">
            <div class="text-green-500 text-5xl">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 class="text-2xl font-bold text-gray-900">Установка завершена!</h2>

            <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                Поздравляем! Ru CMS успешно установлена и готова к работе.<br>
                Далее вы можете открыть сайт или перейти в админ-панель для продолжения настройки.
            </p>

            @php
                $countryCode = session('install_country_code', 'RU');
                $presetCountries = config('localization.preset_countries', []);
                $selectedCountry = $presetCountries[$countryCode] ?? null;
            @endphp
            @if($selectedCountry)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                    <div class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
                        <span class="text-xl">{{ $selectedCountry['flag'] ?? '🌍' }}</span>
                        <span>Настройки локализации применены</span>
                    </div>
                    <div class="text-xs text-blue-800 space-y-1">
                        <div><strong>Страна:</strong> {{ $selectedCountry['name'] ?? $countryCode }}</div>
                        <div><strong>Язык:</strong> {{ $selectedCountry['locale'] ?? 'ru' }}</div>
                        <div><strong>Часовой пояс:</strong> {{ $selectedCountry['timezone'] ?? 'UTC' }}</div>
                        <div><strong>Валюта:</strong> {{ $selectedCountry['currency_code'] ?? '' }} ({{ $selectedCountry['currency_symbol'] ?? '' }})</div>
                    </div>
                    <div class="text-xs text-blue-600 mt-2">
                        ✓ Эти настройки применены к системе и администратору
                    </div>
                </div>
            @endif

            {{-- 🔘 Основные действия --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
                <a href="/"
                   class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg text-sm font-semibold shadow transition">
                    <i class="fas fa-house"></i> На сайт
                </a>
                <a href="{{ url('/admin') }}"
                   class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg text-sm font-semibold shadow transition">
                    <i class="fas fa-gauge-high"></i> В админ-панель
                </a>
                <button type="button"
                        id="copy-admin-url"
                        data-url="{{ url('/admin') }}"
                        class="inline-flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-3 rounded-lg text-sm font-semibold border border-gray-200 transition">
                    <i class="fas fa-clipboard"></i> Скопировать URL админки
                </button>
            </div>

            {{-- ✨ Краткий обзор возможностей --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-6 mt-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-2xl">✨</span>
                    <span>Ключевые возможности системы</span>
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🧩</span>
                        <span class="text-gray-700">Модульная архитектура</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🔒</span>
                        <span class="text-gray-700">Безопасность 2FA</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">⚡</span>
                        <span class="text-gray-700">Высокая производительность</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🌍</span>
                        <span class="text-gray-700">Мультиязычность</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">💾</span>
                        <span class="text-gray-700">Автоматические бэкапы</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🔌</span>
                        <span class="text-gray-700">REST API + Swagger</span>
                    </div>
                </div>
            </div>

            {{-- 🧩 Следующие шаги (рекомендации) --}}
            <div class="text-left bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-list-check text-blue-500"></i> Рекомендации после установки
                </h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-file-lines mt-0.5 text-gray-400"></i>
                        Проверьте <span class="font-mono">.env</span>: <span class="font-mono">APP_NAME</span>, <span class="font-mono">APP_URL</span>, <span class="font-mono">TIMEZONE</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-envelope mt-0.5 text-gray-400"></i>
                        Настройте почту (<span class="font-mono">MAIL_MAILER</span>, <span class="font-mono">MAIL_HOST</span>, <span class="font-mono">MAIL_FROM_ADDRESS</span>) для отправки уведомлений.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-broom mt-0.5 text-gray-400"></i>
                        Включите кэш и очередь (рекомендуется Redis) — см. <span class="font-mono">CACHE_DRIVER</span>, <span class="font-mono">QUEUE_CONNECTION</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-clock mt-0.5 text-gray-400"></i>
                        Добавьте CRON: <span class="font-mono">* * * * * php /path/to/artisan schedule:run &gt;&gt; /dev/null 2&gt;&1</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-shield-halved mt-0.5 text-gray-400"></i>
                        Проверьте права на <span class="font-mono">storage/</span> и <span class="font-mono">bootstrap/cache</span>, а также режим <span class="font-mono">APP_ENV</span>/<span class="font-mono">APP_DEBUG</span>.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-image mt-0.5 text-gray-400"></i>
                        В админке откройте «Темы» и примените/настройте активную тему и набор иконок.
                    </li>
                </ul>
            </div>

            {{-- 🔒 Примечание про блокировку установщика --}}
            <p class="text-xs text-gray-500">
                Мастер установки автоматически заблокирован (создан <span class="font-mono">storage/install.lock</span>).
            </p>
        </div>
    </div>
</div>

{{-- 📋 Копирование ссылки админки --}}
<script>
    (function(){
        var btn = document.getElementById('copy-admin-url');
        if (!btn) return;
        btn.addEventListener('click', function(){
            var url = btn.getAttribute('data-url') || '{{ url('/admin') }}';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function(){
                    btn.innerHTML = '<i class="fas fa-check"></i> Скопировано';
                    setTimeout(function(){
                        btn.innerHTML = '<i class="fas fa-clipboard"></i> Скопировать URL админки';
                    }, 1800);
                }).catch(function(){
                    fallback(url);
                });
            } else {
                fallback(url);
            }
            function fallback(text){
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select(); document.execCommand('copy');
                document.body.removeChild(ta);
                btn.innerHTML = '<i class="fas fa-check"></i> Скопировано';
                setTimeout(function(){
                    btn.innerHTML = '<i class="fas fa-clipboard"></i> Скопировать URL админки';
                }, 1800);
            }
        });
    })();
</script>
@endsection
