@extends('layouts.admin')

@section('title', __('admin.sections.localization'))

@section('content')
@php
    use Modules\Localization\Models\Country;

    $presets = config('localization.preset_countries', []);
    $importedCodes = Country::pluck('code')->all();
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-globe"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.sections.localization') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.localization.subtitle') }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('admin.localization.translations.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
            <i class="fas fa-language"></i> {{ __('admin.localization.translations_link') }}
        </a>
        <a href="{{ route('admin.localization.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i> {{ __('admin.localization.add_country') }}
        </a>
    </div>
</div>

@includeIf('layouts.partials.flash')

{{-- ── Сводка ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['countries', $stats['total_countries'] ?? 0, 'fa-globe'],
        ['active', $stats['active_countries'] ?? 0, 'fa-circle-check'],
        ['settings', $stats['total_settings'] ?? 0, 'fa-sliders'],
        ['system', $stats['system_settings'] ?? 0, 'fa-lock'],
    ] as [$stat, $value, $icon])
        <div class="admin-card p-4">
            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <i class="fas {{ $icon }} text-indigo-500"></i> {{ __('admin.localization.stat_' . $stat) }}
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" data-stat="{{ $stat }}">
                {{ $value }}
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('admin.localization.stat_' . $stat . '_hint') }}</div>
        </div>
    @endforeach
</div>

{{-- ── Инструменты ── --}}
<div class="admin-card p-4 mb-5" x-data="{ importOpen: false }">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
        <i class="fas fa-screwdriver-wrench text-indigo-500"></i> {{ __('admin.localization.tools') }}
    </h2>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Модал импорта на Alpine: прежний был на data-bs-toggle, а Bootstrap
             JS в проекте не подключён — кнопка просто ничего не делала --}}
        <button type="button" @click="importOpen = true"
                class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                       hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
            <i class="fas fa-download"></i> {{ __('admin.localization.import') }}
        </button>

        <form action="{{ route('admin.localization.clear.cache') }}" method="POST" class="inline">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                           hover:border-indigo-400 hover:text-indigo-600 px-3 py-2 text-sm transition">
                <i class="fas fa-arrows-rotate"></i> {{ __('admin.localization.clear_cache') }}
            </button>
        </form>

        <span class="text-sm text-gray-400 dark:text-gray-500">
            {{ __('admin.localization.available_presets') }} {{ trans_choice('admin.localization.countries_plural', count($presets)) }}
        </span>
    </div>

    {{-- Модал импорта --}}
    <div x-cloak x-show="importOpen" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(17,24,39,.55);"
         @keydown.escape.window="importOpen = false">
        <div class="admin-card w-full max-w-lg p-5" @click.outside="importOpen = false">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.localization.import') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.localization.import_hint') }}
                    </p>
                </div>
                <button type="button" @click="importOpen = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.localization.import.presets') }}" method="POST">
                @csrf
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse($presets as $code => $data)
                        @php $already = in_array($code, $importedCodes, true); @endphp
                        <label class="flex items-center gap-3 border border-gray-200 dark:border-gray-700 p-3
                                      {{ $already ? 'opacity-60' : 'cursor-pointer hover:border-indigo-400' }} transition">
                            <input type="checkbox" name="countries[]" value="{{ $code }}"
                                   class="border-gray-400" {{ $already ? 'disabled' : '' }}>
                            <span class="text-lg">{{ $data['flag'] ?? '🏳️' }}</span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $data['name'] }}</span>
                                <span class="block text-xs font-mono text-gray-400">{{ $code }}</span>
                            </span>
                            @if($already)
                                <span class="ml-auto text-xs text-green-700"><i class="fas fa-check"></i> {{ __('admin.localization.already_added') }}</span>
                            @endif
                        </label>
                    @empty
                        <p class="admin-hint">{{ __('admin.localization.no_presets') }}</p>
                    @endforelse
                </div>

                <div class="flex items-center gap-2 mt-5">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                                   px-4 py-2 text-sm font-semibold shadow-sm transition flex-1">
                        <i class="fas fa-download"></i> {{ __('admin.localization.import_submit') }}
                    </button>
                    <button type="button" @click="importOpen = false"
                            class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800
                                   px-4 py-2 text-sm font-semibold transition">
                        {{ __('admin.localization.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($countries->isEmpty())
    {{-- ── Пустое состояние ── --}}
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-globe"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.localization.empty_title') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-5">
            {{ __('admin.localization.empty_hint_1') }}
            {{ __('admin.localization.empty_hint_2') }}
        </p>
        <a href="{{ route('admin.localization.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i> {{ __('admin.localization.add_country') }}
        </a>
    </div>
@else
    {{-- ── Таблица стран ── --}}
    <div class="admin-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">{{ __('admin.localization.th_country') }}</th>
                    <th class="px-4 py-3 text-left font-semibold">{{ __('admin.localization.th_currency') }}</th>
                    <th class="px-4 py-3 text-left font-semibold">{{ __('admin.localization.th_locale') }}</th>
                    <th class="px-4 py-3 text-center font-semibold">{{ __('admin.localization.th_settings') }}</th>
                    <th class="px-4 py-3 text-center font-semibold">{{ __('admin.localization.th_status') }}</th>
                    <th class="px-4 py-3 text-center font-semibold">{{ __('admin.localization.th_actions') }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($countries as $country)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center gap-3">
                                <span class="text-xl leading-none">{{ $country->flag ?? '🏳️' }}</span>
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $country->name }}</div>
                                    @if($country->native_name)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $country->native_name }}</div>
                                    @endif
                                    <div class="text-xs font-mono text-gray-400 mt-0.5">{{ $country->code }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-300">
                            {{ $country->currency_code }}
                            @if($country->currency_symbol)
                                <span class="text-gray-400">({{ $country->currency_symbol }})</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 align-top font-mono text-xs text-gray-600 dark:text-gray-300">
                            {{ $country->locale }}
                        </td>

                        <td class="px-4 py-3 align-top text-center">
                            <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5">
                                {{ $country->settings_count }}
                            </span>
                        </td>

                        <td class="px-4 py-3 align-top text-center">
                            @if($country->active)
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5">{{ __('admin.localization.status_on') }}</span>
                            @else
                                <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5">{{ __('admin.localization.status_off') }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 align-top text-center whitespace-nowrap">
                            <a href="{{ route('admin.localization.edit', $country->code) }}"
                               class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                      text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                               title="{{ __('admin.localization.act_edit') }}"><i class="fas fa-pen"></i></a>

                            <a href="{{ route('admin.localization.settings', $country->code) }}"
                               class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                      text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                               title="{{ __('admin.localization.act_formats') }}"><i class="fas fa-sliders"></i></a>

                            <button type="submit" form="delete-country-{{ $country->code }}"
                                    class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                           text-gray-600 dark:text-gray-300 hover:border-red-400 hover:text-red-600 transition"
                                    title="{{ __('admin.localization.act_delete') }}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Формы удаления — вне таблицы --}}
    @foreach($countries as $country)
        <form id="delete-country-{{ $country->code }}" method="POST"
              action="{{ route('admin.localization.destroy', $country->code) }}" class="hidden"
              onsubmit="return confirm(@js(__('admin.localization.confirm_delete', ['name' => $country->name])))">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
@endsection
