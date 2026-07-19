@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-3xl max-h-full flex flex-col">
    <div class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden">

        {{-- Шапка: логотип + название (монохром) --}}
        <div class="px-6 sm:px-10 pt-6 pb-4 text-center shrink-0">
            <div class="inline-flex items-center gap-3">
                {{-- Логотип: чёрный квадрат с «слоями» — намёк на модульность CMS --}}
                <div class="w-12 h-12 rounded-2xl bg-gray-900 shadow-lg shadow-gray-900/25 grid place-items-center text-white">
                    <i data-lucide="layers" class="w-6 h-6"></i>
                </div>
                <div class="text-left">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                        Ru&nbsp;CMS <span class="text-gray-400 font-semibold">· Установка</span>
                    </h1>
                    <p class="text-xs text-gray-500">Быстрый старт · Минимум шагов · Готово к продакшену</p>
                </div>
            </div>
        </div>

        {{-- Прокручиваемая середина (на маленьких экранах), обычно всё влезает --}}
        <div class="px-6 sm:px-10 overflow-y-auto install-scroll min-h-0 space-y-4">

            {{-- 🌍 Выбор страны и языка: флаг — главный элемент --}}
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-center justify-center gap-2 text-sm font-semibold text-gray-800 mb-3">
                    <i data-lucide="languages" class="w-4 h-4"></i>
                    Язык и страна системы
                </div>
                @php
                    // Инлайн-SVG флаги: Windows не отображает эмодзи флагов вообще
                    // (Segoe UI Emoji их не содержит), поэтому эмодзи из COUNTRY_PRESETS
                    // здесь бесполезны. SVG — локально, без единого внешнего запроса.
                    $flagSvg = [
                        'RU' => '<svg viewBox="0 0 30 20" class="w-9 h-6 rounded shadow-sm"><rect width="30" height="20" fill="#fff"/><rect y="6.67" width="30" height="6.67" fill="#0039A6"/><rect y="13.33" width="30" height="6.67" fill="#D52B1E"/><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'BY' => '<svg viewBox="0 0 30 20" class="w-9 h-6 rounded shadow-sm"><rect width="30" height="20" fill="#CE1720"/><rect y="13.33" width="30" height="6.67" fill="#007C30"/><rect width="3.3" height="20" fill="#fff"/><path d="M.8 1.5h1.7v2H.8zM.8 5.5h1.7v2H.8zM.8 9.5h1.7v2H.8zM.8 13.5h1.7v2H.8zM.8 17h1.7v2H.8z" fill="#CE1720"/><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'KZ' => '<svg viewBox="0 0 30 20" class="w-9 h-6 rounded shadow-sm"><rect width="30" height="20" fill="#00AFCA"/><circle cx="15" cy="9" r="3.4" fill="#FEC50C"/><g stroke="#FEC50C" stroke-width=".7"><line x1="15" y1="3.6" x2="15" y2="5"/><line x1="15" y1="13" x2="15" y2="14.4"/><line x1="9.6" y1="9" x2="11" y2="9"/><line x1="19" y1="9" x2="20.4" y2="9"/><line x1="11.2" y1="5.2" x2="12.2" y2="6.2"/><line x1="17.8" y1="11.8" x2="18.8" y2="12.8"/><line x1="18.8" y1="5.2" x2="17.8" y2="6.2"/><line x1="12.2" y1="11.8" x2="11.2" y2="12.8"/></g><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'UA' => '<svg viewBox="0 0 30 20" class="w-9 h-6 rounded shadow-sm"><rect width="30" height="10" fill="#005BBB"/><rect y="10" width="30" height="10" fill="#FFD500"/><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'US' => '<svg viewBox="0 0 30 20" class="w-9 h-6 rounded shadow-sm"><rect width="30" height="20" fill="#fff"/><g fill="#B22234"><rect width="30" height="1.54"/><rect y="3.08" width="30" height="1.54"/><rect y="6.15" width="30" height="1.54"/><rect y="9.23" width="30" height="1.54"/><rect y="12.31" width="30" height="1.54"/><rect y="15.38" width="30" height="1.54"/><rect y="18.46" width="30" height="1.54"/></g><rect width="12" height="10.77" fill="#3C3B6E"/><g fill="#fff"><circle cx="2" cy="1.8" r=".55"/><circle cx="5" cy="1.8" r=".55"/><circle cx="8" cy="1.8" r=".55"/><circle cx="11" cy="1.8" r=".55"/><circle cx="3.5" cy="3.6" r=".55"/><circle cx="6.5" cy="3.6" r=".55"/><circle cx="9.5" cy="3.6" r=".55"/><circle cx="2" cy="5.4" r=".55"/><circle cx="5" cy="5.4" r=".55"/><circle cx="8" cy="5.4" r=".55"/><circle cx="11" cy="5.4" r=".55"/><circle cx="3.5" cy="7.2" r=".55"/><circle cx="6.5" cy="7.2" r=".55"/><circle cx="9.5" cy="7.2" r=".55"/><circle cx="2" cy="9" r=".55"/><circle cx="5" cy="9" r=".55"/><circle cx="8" cy="9" r=".55"/><circle cx="11" cy="9" r=".55"/></g><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'DE' => '<svg viewBox="0 0 30 20" class="w-9 h-6 rounded shadow-sm"><rect width="30" height="6.67" fill="#000"/><rect y="6.67" width="30" height="6.67" fill="#DD0000"/><rect y="13.33" width="30" height="6.67" fill="#FFCE00"/><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                    ];
                @endphp
                <form method="GET" action="{{ route('install.welcome') }}">
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                        @foreach($presetCountries as $code => $country)
                            @php $isSel = ($currentCountry ?? 'RU') === $code; @endphp
                            <button type="submit"
                                    name="country_code"
                                    value="{{ $code }}"
                                    title="{{ $country['name'] ?? $code }} — язык: {{ strtoupper($country['locale'] ?? 'ru') }}, {{ $country['currency_code'] ?? '' }}"
                                    class="country-select-btn rounded-xl border-2 p-2.5 text-center transition-all
                                           {{ $isSel
                                               ? 'border-gray-900 bg-white shadow-md'
                                               : 'border-gray-200 bg-white hover:border-gray-400' }}">
                                <div class="flex justify-center mb-1.5">{!! $flagSvg[$code] ?? '<span class="text-2xl leading-none">🌍</span>' !!}</div>
                                <div class="text-[11px] font-semibold {{ $isSel ? 'text-gray-900' : 'text-gray-600' }}">{{ $country['name'] ?? $code }}</div>
                                @if ($isSel)
                                    <div class="mt-1 inline-flex items-center justify-center gap-0.5 text-[10px] text-gray-900 font-medium">
                                        <i data-lucide="check" class="w-3 h-3"></i> Выбрано
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2 text-center flex items-center justify-center gap-1">
                        <i data-lucide="info" class="w-3 h-3"></i>
                        Определяет язык интерфейса, формат дат, валюту и часовой пояс. Можно изменить позже в админ-панели.
                    </p>
                </form>
            </div>

            {{-- Шаги --}}
            @include('Install::partials.steps', ['current' => 'welcome'])

            {{-- Три мини-фичи в одну строку --}}
            <div class="grid grid-cols-3 gap-2">
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center" title="Только нужные поля, ничего лишнего">
                    <i data-lucide="gauge" class="w-4 h-4 mx-auto mb-1 text-gray-700"></i>
                    <div class="text-xs font-semibold text-gray-900">Лёгкий мастер</div>
                    <div class="text-[10px] text-gray-400 hidden sm:block">5 минут до запуска</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center" title="Аккуратная генерация .env, проверка подключения к БД до записи">
                    <i data-lucide="shield-check" class="w-4 h-4 mx-auto mb-1 text-gray-700"></i>
                    <div class="text-xs font-semibold text-gray-900">Безопасность</div>
                    <div class="text-[10px] text-gray-400 hidden sm:block">Бережный .env</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center" title="Все ресурсы локальные — ни одного обращения к внешним CDN">
                    <i data-lucide="hard-drive" class="w-4 h-4 mx-auto mb-1 text-gray-700"></i>
                    <div class="text-xs font-semibold text-gray-900">Без CDN</div>
                    <div class="text-[10px] text-gray-400 hidden sm:block">Всё локально</div>
                </div>
            </div>

            {{-- Подсказка про реквизиты БД --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[11px] text-gray-600 flex items-center justify-center gap-2">
                <i data-lucide="database" class="w-3.5 h-3.5 shrink-0"></i>
                <span>Понадобятся реквизиты PostgreSQL: хост, порт, имя базы, пользователь и пароль.</span>
            </div>
        </div>

        {{-- Кнопки: прижаты к низу карточки --}}
        <div class="px-6 sm:px-10 py-4 shrink-0 border-t border-gray-100 mt-4">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                <a href="{{ route('install.requirements') }}"
                   class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gray-900 hover:bg-black shadow-lg shadow-gray-900/25 transition-colors">
                    <i data-lucide="play" class="w-4 h-4"></i>
                    <span>Начать установку</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5"></i>
                </a>
                <a href="{{ route('install.features') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-800 bg-white hover:bg-gray-50 border border-gray-300 transition-colors">
                    <i data-lucide="star" class="w-4 h-4"></i><span>Возможности</span>
                </a>
                <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank" rel="noopener"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-500 bg-gray-100 hover:bg-gray-200 border border-gray-200 transition-colors">
                    <i data-lucide="github" class="w-4 h-4"></i><span>GitHub</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .country-select-btn { transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
    .country-select-btn:hover { transform: translateY(-2px); }
    .country-select-btn:active { transform: translateY(0); }
</style>
@endpush
@endsection
