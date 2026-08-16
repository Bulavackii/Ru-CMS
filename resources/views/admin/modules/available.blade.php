@extends('layouts.admin')

@section('title', __('admin.modules.available_title'))

@section('content')
    {{-- Шапка в два ряда (.mh-*, общее определение в лейауте). --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
        <div class="mh-row">
            <span class="admin-icon-badge"><i class="fas fa-cloud-arrow-down"></i></span>
            <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">
                {{ __('admin.modules.available_title') }}
            </h1>
        </div>

        <div class="mh-row mh-row--sub">
            <p class="mh-facts text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.modules.available_hint') }}
            </p>

            <a href="{{ route('admin.modules.index') }}"
               class="mh-back inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <i class="fas fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
            </a>
        </div>
    </div>

    @php $список = collect($modules ?? []); @endphp

    @if (! empty($repositories) && count($repositories) > 1)
        <div class="admin-card p-4 mb-4 flex flex-wrap items-center gap-2 min-w-0">
            <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('admin.modules.repository') }}
            </span>

            @foreach ($repositories as $ключ => $название)
                {{-- ⚠️ Название хранилища бывает длинным (адрес репозитория),
                     а `inline-flex` не сжимается — на 360 строка чипов
                     распирала страницу на 71 пиксель. Перенос и обрезка. --}}
                <a href="{{ route('admin.modules.distribution.available', ['repository' => is_string($ключ) ? $ключ : $название]) }}"
                   class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                          max-w-full min-w-0 truncate
                          {{ ($repository ?? '') === (is_string($ключ) ? $ключ : $название) ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700 dark:text-gray-200' }}">
                    {{ is_array($название) ? ($название['title'] ?? $ключ) : $название }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($список->isEmpty())
        <div class="admin-card p-10 text-center text-gray-500 dark:text-gray-400">
            <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-cloud-arrow-down"></i></span>
            <div class="font-semibold text-gray-900 dark:text-white">{{ __('admin.modules.available_empty') }}</div>
            <p class="text-sm mt-2 max-w-md mx-auto">{{ __('admin.modules.available_empty_hint') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($список as $модуль)
                @php $м = (array) $модуль; @endphp

                <div class="admin-card p-4 flex flex-col gap-2 min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="admin-icon-badge"><i class="fas fa-puzzle-piece"></i></span>
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                {{ $м['title'] ?? $м['name'] ?? '—' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">
                                {{ $м['name'] ?? '' }} {{ isset($м['version']) ? '· ' . $м['version'] : '' }}
                            </div>
                        </div>
                    </div>

                    @if (! empty($м['description']))
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $м['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
