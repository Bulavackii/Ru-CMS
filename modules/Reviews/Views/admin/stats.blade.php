@extends('layouts.admin')

@section('title', __('admin.sections.reviews'))

@section('content')
    {{-- Шапка в два ряда (.mh-*, общее определение в лейауте). --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-chart-simple"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ __('admin.reviews.stats_title') }}
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.reviews.stats_hint') }}
            </p>

            <a href="{{ route('admin.reviews.index') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fas fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
            </a>
        </div>
    </div>

    @php
        // ⚠️ Запрос отдаёт одну строку с агрегатами; при пустой таблице поля
        // приходят пустыми, а не нулями — приводим сами.
        $всего   = (int) ($stats->total ?? 0);
        $средняя = round((float) ($stats->avg_rating ?? 0), 2);

        $поЗвёздам = [
            5 => (int) ($stats->five_stars ?? 0),
            4 => (int) ($stats->four_stars ?? 0),
            3 => (int) ($stats->three_stars ?? 0),
            2 => (int) ($stats->two_stars ?? 0),
            1 => (int) ($stats->one_star ?? 0),
        ];
    @endphp

    {{-- Сводка --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @foreach ([
            ['fa-comments', __('admin.reviews.stat_total'), $всего],
            ['fa-star', __('admin.reviews.stat_average'), $всего ? $средняя : '—'],
            ['fa-hourglass-half', __('admin.reviews.stat_pending'), (int) $pending],
        ] as [$значок, $подпись, $значение])
            <div class="admin-card p-4 flex items-center gap-3 min-w-0">
                <span class="admin-icon-badge"><i class="fas {{ $значок }}"></i></span>
                <div class="min-w-0">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $подпись }}</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $значение }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Распределение по звёздам --}}
    <div class="admin-card p-5">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4">
            {{ __('admin.reviews.stats_spread') }}
        </div>

        @if ($всего === 0)
            <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                <div class="admin-icon-badge mx-auto mb-3"><i class="fas fa-comments"></i></div>
                <div class="font-semibold">{{ __('admin.reviews.stats_empty') }}</div>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($поЗвёздам as $звёзд => $сколько)
                    @php $доля = $всего ? round($сколько * 100 / $всего) : 0; @endphp

                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-14 flex-none text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $звёзд }} <i class="fas fa-star text-yellow-500"></i>
                        </span>

                        <span class="flex-1 min-w-0 h-3 bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <span class="block h-full bg-indigo-500" style="width: {{ $доля }}%"></span>
                        </span>

                        <span class="w-20 flex-none text-right text-sm text-gray-600 dark:text-gray-300"
                              style="font-variant-numeric: tabular-nums">
                            {{ $сколько }} · {{ $доля }}%
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
