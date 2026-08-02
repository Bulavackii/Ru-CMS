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

        <dl class="geo-list">
            <div><dt>{{ __('admin.system.geo_device') }}</dt><dd class="geo-mono">{{ $userAgent ?: '—' }}</dd></div>
            <div><dt>{{ __('admin.system.geo_lang') }}</dt><dd class="geo-mono">{{ $language ?: '—' }}</dd></div>
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
                <p class="admin-hint">
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
    <p class="admin-hint">{{ __('admin.system.geo_how_text') }}</p>
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

    .geo-list{ display:grid; gap:.45rem; font-size:.9rem }
    .geo-list > div{ display:flex; gap:.75rem; align-items:baseline; justify-content:space-between;
                     padding-bottom:.45rem; border-bottom:1px solid #f1f5f9 }
    .geo-list > div:last-child{ border-bottom:0; padding-bottom:0 }
    .geo-list dt{ color:#6b7280; flex-shrink:0 }
    .geo-list dd{ margin:0; font-weight:600; color:#111827; text-align:right; word-break:break-word }
    .geo-mono{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.78rem; font-weight:500 !important }

    .geo-empty{ text-align:center; padding:1.5rem .5rem }
    .geo-empty i{ font-size:1.75rem; color:#c7d2fe; display:block; margin-bottom:.75rem }

    @media (prefers-color-scheme: dark){
        .geo-ip, .geo-list dd{ color:#f3f4f6 }
        .geo-list > div{ border-color:#1f2937 }
    }
</style>
@endpush
