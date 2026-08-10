@extends('layouts.admin')

@section('title', __('admin.system.geo_title'))

@section('content')
{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex items-center gap-3">
    <span class="admin-icon-badge"><i class="fas fa-globe"></i></span>
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.system.geo_title') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.system.geo_subtitle') }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- ── Запрос ── --}}
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
            <i class="fas fa-wifi text-indigo-500"></i> {{ __('admin.system.geo_ip') }}
        </h2>

        <div class="flex items-center gap-3 flex-wrap mb-4">
            <span class="geo-ip">{{ $ip }}</span>

            <button type="button" id="geo-copy" data-ip="{{ $ip }}"
                    class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                           hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-1.5 text-sm font-semibold transition">
                <i class="fas fa-copy"></i> <span>{{ __('admin.system.geo_copy') }}</span>
            </button>
        </div>

        @php
            // Строку браузера показывали как есть — читать её человеку тяжело:
            // это техническая строка на две строки экрана. Разбираем на
            // понятные части, а исходник оставляем во всплывающей подсказке.
            $ua = (string) $userAgent;

            // Порядок важен: браузеры на движке Chrome пишут в строке и своё
            // имя, и Chrome. Проверяем сначала частные случаи.
            [$browser, $browserToken] = match (true) {
                str_contains($ua, 'YaBrowser')                          => ['Яндекс.Браузер', 'YaBrowser'],
                str_contains($ua, 'Edg/')                               => ['Edge', 'Edg'],
                str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => ['Opera', 'OPR'],
                str_contains($ua, 'Firefox')                            => ['Firefox', 'Firefox'],
                str_contains($ua, 'Chrome')                             => ['Chrome', 'Chrome'],
                str_contains($ua, 'Safari')                             => ['Safari', 'Version'],
                default                                                 => [null, null],
            };

            // Версию ищем именно у определённого браузера. Общий поиск по
            // списку брал первое совпадение в строке, и у Яндекс.Браузера
            // показывалась версия Chrome: она стоит раньше.
            $browserVersion = null;

            if ($browserToken && preg_match('~' . $browserToken . '/(\d+)~', $ua, $verMatch)) {
                $browserVersion = $verMatch[1];
            }

            $system = match (true) {
                str_contains($ua, 'Windows NT 10') => 'Windows 10 или 11',
                str_contains($ua, 'Windows')       => 'Windows',
                str_contains($ua, 'Android')       => 'Android',
                str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
                str_contains($ua, 'Mac OS X')      => 'macOS',
                str_contains($ua, 'Linux')         => 'Linux',
                default                            => null,
            };

            $isMobile = (bool) preg_match('~Mobile|Android|iPhone|iPad~', $ua);

            // Языки приходят с весами: ru-RU,ru;q=0.9,en-US;q=0.8 — оставляем
            // сами коды в порядке предпочтения, веса человеку не нужны.
            $langs = collect(explode(',', (string) $language))
                ->map(fn ($x) => trim(explode(';', $x)[0]))
                ->filter()
                ->unique()
                ->take(5)
                ->values();
        @endphp

        <dl class="geo-list">
            <div>
                <dt>{{ __('admin.system.geo_device') }}</dt>
                <dd>
                    <span class="geo-chips" @if($ua) title="{{ $ua }}" @endif>
                        @if($browser)
                            <span class="geo-chip">{{ $browser }}@if($browserVersion) {{ $browserVersion }}@endif</span>
                        @endif
                        @if($system)<span class="geo-chip">{{ $system }}</span>@endif
                        <span class="geo-chip geo-chip--soft">
                            {{ $isMobile ? __('admin.system.geo_mobile') : __('admin.system.geo_desktop') }}
                        </span>
                        @unless($browser || $system)
                            <span class="geo-chip geo-chip--soft">—</span>
                        @endunless
                    </span>
                </dd>
            </div>
            <div>
                <dt>{{ __('admin.system.geo_lang') }}</dt>
                <dd>
                    <span class="geo-chips">
                        @forelse($langs as $i => $code)
                            <span class="geo-chip @if($i > 0) geo-chip--soft @endif">{{ $code }}</span>
                        @empty
                            <span class="geo-chip geo-chip--soft">—</span>
                        @endforelse
                    </span>
                </dd>
            </div>
            <div><dt>{{ __('admin.system.geo_time') }}</dt><dd>{{ $timestamp->format('d.m.Y H:i:s') }}</dd></div>
        </dl>
    </section>

    {{-- ── Местоположение ── --}}
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
            <i class="fas fa-location-dot text-indigo-500"></i> {{ __('admin.system.geo_where') }}
        </h2>

        @if($location)
            <dl class="geo-list">
                <div><dt>{{ __('admin.system.geo_city') }}</dt><dd>{{ $location->cityName ?: '—' }}</dd></div>
                <div><dt>{{ __('admin.system.geo_region') }}</dt><dd>{{ $location->regionName ?: '—' }}</dd></div>
                <div><dt>{{ __('admin.system.geo_country') }}</dt><dd>{{ $location->countryName ?: '—' }}</dd></div>
                <div><dt>{{ __('admin.system.geo_zip') }}</dt><dd>{{ $location->zipCode ?: '—' }}</dd></div>

                @if($location->latitude && $location->longitude)
                    <div><dt>{{ __('admin.system.geo_coords') }}</dt>
                        <dd class="geo-mono">{{ $location->latitude }}, {{ $location->longitude }}</dd></div>
                @endif
            </dl>
        @else
            {{-- Разделяем два разных случая: локальный адрес — это норма,
                 а недоступный сервис — повод проверить сеть. --}}
            <div class="geo-empty">
                <i class="fas fa-map-location-dot"></i>
                <p class="font-semibold text-gray-900 dark:text-white">{{ __('admin.system.geo_local') }}</p>
                <p class="geo-help">
                    {{ $isLocal
                        ? __('admin.system.geo_local_hint', ['ip' => $ip])
                        : __('admin.system.geo_failed_hint') }}
                </p>
            </div>
        @endif
    </section>
</div>

<section class="admin-card p-5 mt-4">
    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-2">
        <i class="fas fa-circle-info text-indigo-500"></i> {{ __('admin.system.geo_how') }}
    </h2>
    <p class="geo-help">{{ __('admin.system.geo_how_text') }}</p>
</section>
@endsection

@push('scripts')
<script>
    document.getElementById('geo-copy')?.addEventListener('click', function () {
        const label = this.querySelector('span');
        const before = label.textContent;

        navigator.clipboard.writeText(this.dataset.ip).then(function () {
            label.textContent = @js(__('admin.system.geo_copied'));
            setTimeout(function () { label.textContent = before; }, 2000);
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Литеральный CSS — в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    .geo-ip{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:1.35rem; font-weight:700;
             color:#111827; letter-spacing:.02em }

    /* Подсказка под блоком.
       Раньше тут стоял класс врезки-примечания: он рисует заливку и полосу
       слева, и короткое пояснение читалось как выделенный текст. */
    .geo-help{ margin-top:.4rem; font-size:.8rem; line-height:1.5; color:#64748b }

    /* Разобранные сведения о браузере и языках.
       Исходная строка браузера остаётся во всплывающей подсказке чипов —
       она нужна при разборе обращения, но каждый день её читать незачем. */
    .geo-chips{ display:inline-flex; flex-wrap:wrap; gap:.3rem; justify-content:flex-end }
    .geo-chip{ display:inline-flex; align-items:center; padding:.16rem .5rem;
        font-size:.75rem; font-weight:600; color:#4338ca; background:rgba(99,102,241,.1) }
    .geo-chip--soft{ color:#64748b; background:#f1f5f9; font-weight:500 }

    .geo-list{ display:grid; gap:.45rem; font-size:.9rem }
    .geo-list > div{ display:flex; gap:.75rem; align-items:baseline; justify-content:space-between;
                     padding-bottom:.45rem; border-bottom:1px solid #f1f5f9 }
    .geo-list > div:last-child{ border-bottom:0; padding-bottom:0 }
    .geo-list dt{ color:#6b7280; flex-shrink:0 }
    .geo-list dd{ margin:0; font-weight:600; color:#111827; text-align:right; word-break:break-word }
    .geo-mono{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.78rem; font-weight:500 !important }

    .geo-empty{ text-align:center; padding:1.5rem .5rem }
    .geo-empty i{ font-size:1.75rem; color:#c7d2fe; display:block; margin-bottom:.75rem }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) — это
       настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не оформление панели. При тёмной
       системе и светлой панели он перекрашивал текст в почти белый на
       белом фоне: сумма заказа пропадала совсем. Тему панели задают класс
       .dark и переменные --admin-*; перекрытие по настройке ОС их только
       ломало. */
</style>
@endpush
