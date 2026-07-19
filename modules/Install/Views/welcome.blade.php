@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen relative overflow-hidden bg-white text-gray-900">

    {{-- 🔮 Фон: мягкие светлые пятна + тонкая сетка --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        {{-- голубой «блик» слева --}}
        <div class="absolute -top-32 -left-32 w-[40rem] h-[40rem] rounded-full blur-[120px] opacity-40"
             style="background: radial-gradient(50% 50% at 50% 50%, #3b82f6 0%, rgba(59,130,246,0) 70%);"></div>
        {{-- голубой «блик» справа снизу --}}
        <div class="absolute -bottom-40 -right-20 w-[42rem] h-[42rem] rounded-full blur-[140px] opacity-30"
             style="background: radial-gradient(50% 50% at 50% 50%, #60a5fa 0%, rgba(96,165,250,0) 70%);"></div>

        {{-- едва заметная сетка для глубины --}}
        <div class="absolute inset-0 opacity-[0.06] [background-image:linear-gradient(to_right,#000_1px,transparent_1px),linear-gradient(to_bottom,#000_1px,transparent_1px)] [background-size:28px_28px]"></div>
    </div>

    {{-- 🚀 Центральный блок --}}
    <div class="relative max-w-3xl mx-auto px-6 py-16 lg:py-24">
        <div class="text-center space-y-8">

            {{-- Лого + заголовок --}}
            <div class="inline-flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-500 shadow-2xl shadow-blue-500/25 grid place-items-center text-3xl font-extrabold text-white">
                    RU
                </div>
                <div class="text-left">
                    <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">
                        <span class="text-black">Ru&nbsp;CMS</span>
                        <span class="text-blue-600">· Установка</span>
                    </h1>
                    <p class="text-sm text-gray-600">Быстрый старт. Минимум шагов. Готово к продакшену.</p>
                </div>
            </div>

            {{-- Карточка со «стеклом» в светлой теме --}}
            <div class="relative rounded-3xl border border-gray-200 bg-white/70 backdrop-blur-xl px-8 py-10 md:px-12 md:py-12 shadow-[0_24px_60px_-24px_rgba(59,130,246,.35)]">
                {{-- декоративная «орбита» --}}
                <div aria-hidden="true" class="absolute -top-10 -right-10 w-24 h-24 rounded-full bg-blue-300/25 blur-2xl"></div>

                <div class="flex flex-col items-center gap-4">
                    <div class="relative">
                        <div class="absolute inset-0 animate-ping rounded-full bg-blue-400/25"></div>
                        <div class="relative grid place-items-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-600 to-blue-500 shadow-lg">
                            <i class="fas fa-rocket text-white text-2xl -rotate-12"></i>
                        </div>
                    </div>

                    <h2 class="text-xl md:text-2xl font-bold text-black">Добро пожаловать! До запуска — всего пару шагов</h2>
                    <p class="text-gray-700 max-w-2xl">
                        Мы проведём вас через проверку окружения, подключение базы данных и создание администратора.
                        Это займёт всего пару минут.
                    </p>

                    {{-- 🌍 Выбор страны и языка --}}
                    <div class="w-full mt-6">
                        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                🌍 Выберите страну и язык
                            </label>
                            <form method="GET" action="{{ route('install.welcome') }}" class="space-y-3">
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach($presetCountries as $code => $country)
                                        <button type="submit" 
                                                name="country_code" 
                                                value="{{ $code }}"
                                                class="country-select-btn p-3 rounded-lg border-2 transition-all text-left
                                                       {{ ($currentCountry ?? 'RU') === $code 
                                                           ? 'border-blue-500 bg-blue-50 shadow-md' 
                                                           : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50' }}">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-2xl">{{ $country['flag'] ?? '🌍' }}</span>
                                                <span class="font-semibold text-sm text-gray-900">{{ $code }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600">{{ $country['name'] ?? $code }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $country['native_name'] ?? '' }}
                                            </div>
                                            @if(($currentCountry ?? 'RU') === $code)
                                                <div class="mt-2 text-xs text-blue-600 font-medium">
                                                    ✓ Выбрано
                                                </div>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                <div class="text-xs text-gray-500 mt-3 text-center">
                                    Выбранная страна определит язык интерфейса, формат дат, валюту и часовой пояс системы.
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Шаги установки --}}
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 w-full">
                        @php
                            $stepClass = 'rounded-2xl border border-gray-200 bg-white px-4 py-3 text-left';
                            $dot = 'inline-flex w-2.5 h-2.5 rounded-full';
                        @endphp
                        <div class="{{ $stepClass }}">
                            <div class="flex items-center gap-2">
                                <span class="{{ $dot }} bg-blue-600"></span>
                                <span class="text-sm font-semibold text-black">1. Приветствие</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">Короткое знакомство и быстрый старт.</p>
                        </div>
                        <div class="{{ $stepClass }}">
                            <div class="flex items-center gap-2">
                                <span class="{{ $dot }} bg-blue-500"></span>
                                <span class="text-sm font-semibold text-black">2. Требования</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">Проверим PHP, расширения и права.</p>
                        </div>
                        <div class="{{ $stepClass }}">
                            <div class="flex items-center gap-2">
                                <span class="{{ $dot }} bg-blue-400"></span>
                                <span class="text-sm font-semibold text-black">3. База и админ</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">Подключим БД и создадим доступ.</p>
                        </div>
                    </div>

                    {{-- «Фичи» --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-left w-full">
                        <div class="rounded-xl bg-white border border-gray-200 p-4">
                            <div class="text-blue-600 text-lg mb-1"><i class="fas fa-gauge"></i></div>
                            <div class="text-sm font-semibold text-black">Лёгкий мастер</div>
                            <div class="text-xs text-gray-600 mt-0.5">Минимально нужные поля без перегруза.</div>
                        </div>
                        <div class="rounded-xl bg-white border border-gray-200 p-4">
                            <div class="text-blue-600 text-lg mb-1"><i class="fas fa-shield-heart"></i></div>
                            <div class="text-sm font-semibold text-black">Безопасность</div>
                            <div class="text-xs text-gray-600 mt-0.5">Корректная и бережная генерация .env.</div>
                        </div>
                        <div class="rounded-xl bg-white border border-gray-200 p-4">
                            <div class="text-blue-600 text-lg mb-1"><i class="fas fa-bolt"></i></div>
                            <div class="text-sm font-semibold text-black">Скорость</div>
                            <div class="text-xs text-gray-600 mt-0.5">Установка проходит буквально за минуты.</div>
                        </div>
                    </div>

                    {{-- CTA-кнопки --}}
                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('install.requirements') }}"
                           class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/30">
                            <span>Начать установку</span>
                            <i class="fas fa-arrow-right transition-transform group-hover:translate-x-0.5"></i>
                        </a>
                        <a href="{{ route('install.features') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-100">
                            <i class="fas fa-star"></i><span>Возможности</span>
                        </a>
                        <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200">
                            <i class="fab fa-github"></i><span>GitHub</span>
                        </a>
                    </div>

                    {{-- Подсказка --}}
                    <div class="mt-4 text-xs">
                        <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fas fa-keyboard"></i>
                            <span>Держите под рукой реквизиты БД: хост, порт, база, пользователь, пароль.</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Мини-футер --}}
            <div class="text-center text-xs text-gray-500">
                © {{ date('Y') }} Ru CMS. Сделано с любовью к скорости и аккуратности.
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .country-select-btn {
        transition: all 0.2s ease;
    }
    .country-select-btn:hover {
        transform: translateY(-2px);
    }
    .country-select-btn:active {
        transform: translateY(0);
    }
</style>
@endpush
@endsection
