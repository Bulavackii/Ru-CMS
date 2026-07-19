@extends('layouts.frontend-install')

@section('content')
<div class="max-w-3xl mx-auto px-2">
    <div class="text-center space-y-8">

        {{-- Лого + заголовок --}}
        <div class="inline-flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-500 shadow-xl shadow-blue-500/25 grid place-items-center text-2xl font-bold text-white">
                RU
            </div>
            <div class="text-left">
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900">
                    Ru&nbsp;CMS <span class="text-blue-600">· Установка</span>
                </h1>
                <p class="text-sm text-gray-500">Быстрый старт. Минимум шагов. Готово к продакшену.</p>
            </div>
        </div>

        {{-- Основная карточка --}}
        <div class="relative rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl px-6 py-10 md:px-12 md:py-12 shadow-[0_24px_60px_-24px_rgba(59,130,246,.35)]">

            <div class="flex flex-col items-center gap-4">
                <div class="grid place-items-center w-14 h-14 rounded-2xl bg-blue-600/10 text-blue-600">
                    <i data-lucide="rocket" class="w-7 h-7"></i>
                </div>

                <h2 class="text-xl md:text-2xl font-semibold text-gray-900">Добро пожаловать! До запуска — всего пару шагов</h2>
                <p class="text-gray-600 max-w-2xl">
                    Мы проведём вас через проверку окружения, подключение базы данных и создание администратора.
                    Это займёт всего пару минут.
                </p>

                {{-- 🌍 Выбор страны и языка --}}
                <div class="w-full mt-4">
                    <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-4">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3">
                            <i data-lucide="globe" class="w-4 h-4 text-blue-600"></i>
                            Выберите страну и язык
                        </label>
                        <form method="GET" action="{{ route('install.welcome') }}">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach($presetCountries as $code => $country)
                                    <button type="submit"
                                            name="country_code"
                                            value="{{ $code }}"
                                            class="country-select-btn p-3 rounded-xl border-2 transition-all text-left
                                                   {{ ($currentCountry ?? 'RU') === $code
                                                       ? 'border-blue-500 bg-blue-50 shadow-sm'
                                                       : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/60' }}">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xl">{{ $country['flag'] ?? '🌍' }}</span>
                                            <span class="font-semibold text-xs text-gray-900">{{ $code }}</span>
                                        </div>
                                        <div class="text-xs text-gray-600">{{ $country['name'] ?? $code }}</div>
                                        @if(($currentCountry ?? 'RU') === $code)
                                            <div class="mt-1.5 text-[11px] text-blue-600 font-medium flex items-center gap-1">
                                                <i data-lucide="check" class="w-3 h-3"></i> Выбрано
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-3 text-center">
                                Выбранная страна определит язык интерфейса, формат дат, валюту и часовой пояс системы.
                            </p>
                        </form>
                    </div>
                </div>

                {{-- Шаги установки --}}
                <div class="mt-2 w-full">
                    @include('Install::partials.steps', ['current' => 'welcome'])
                </div>

                {{-- «Фичи» --}}
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3 text-left w-full">
                    <div class="rounded-2xl bg-white border border-gray-200 p-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-600 grid place-items-center mb-2">
                            <i data-lucide="gauge" class="w-4 h-4"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Лёгкий мастер</div>
                        <div class="text-xs text-gray-500 mt-0.5">Минимально нужные поля без перегруза.</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-gray-200 p-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-600 grid place-items-center mb-2">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Безопасность</div>
                        <div class="text-xs text-gray-500 mt-0.5">Корректная и бережная генерация .env.</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-gray-200 p-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-600 grid place-items-center mb-2">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Скорость</div>
                        <div class="text-xs text-gray-500 mt-0.5">Установка проходит буквально за минуты.</div>
                    </div>
                </div>

                {{-- CTA-кнопки --}}
                <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('install.requirements') }}"
                       class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-colors">
                        <span>Начать установку</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5"></i>
                    </a>
                    <a href="{{ route('install.features') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-100 transition-colors">
                        <i data-lucide="star" class="w-4 h-4"></i><span>Возможности</span>
                    </a>
                    <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 transition-colors">
                        <i data-lucide="github" class="w-4 h-4"></i><span>GitHub</span>
                    </a>
                </div>

                {{-- Подсказка --}}
                <div class="mt-2 text-xs">
                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-100">
                        <i data-lucide="keyboard" class="w-3.5 h-3.5"></i>
                        <span>Держите под рукой реквизиты БД: хост, порт, база, пользователь, пароль.</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Мини-футер --}}
        <div class="text-center text-xs text-gray-400">
            © {{ date('Y') }} Ru CMS. Сделано с любовью к скорости и аккуратности.
        </div>
    </div>
</div>

@push('styles')
<style>
    .country-select-btn { transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background-color .15s ease; }
    .country-select-btn:hover { transform: translateY(-2px); }
    .country-select-btn:active { transform: translateY(0); }
</style>
@endpush
@endsection
