@extends('layouts.admin')

@section('title', __('admin.delivery.title'))

@section('content')
@php
    use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;

    $required = SeedDefaultDeliveryMethodsCommand::credentialFields();
@endphp

{{-- ── Шапка раздела ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-truck"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.delivery.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.delivery.subtitle') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.delivery.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
        <i class="fas fa-plus"></i> {{ __('admin.delivery.add') }}
    </a>
</div>

@includeIf('layouts.partials.flash')

@forelse($methods as $method)
    @php
        // Служба с включённым API, но без ключей, ничего не посчитает —
        // сказать об этом надо в списке, а не внутри формы.
        $needed = $required[$method->code] ?? [];
        $settings = (array) $method->api_settings;
        $missing = $needed !== []
            && collect($needed)->filter(fn ($field) => blank($settings[$field] ?? null))->isNotEmpty();
    @endphp

    <div class="admin-card p-4 mb-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <strong class="text-gray-900 dark:text-white">{{ $method->title }}</strong>

                @if($method->code)
                    <code class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5">{{ $method->code }}</code>
                @endif

                @if($method->active)
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5">
                        <i class="fas fa-circle-check"></i> {{ __('admin.delivery.on') }}
                    </span>
                @else
                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5">
                        <i class="fas fa-ban"></i> {{ __('admin.delivery.off') }}
                    </span>
                @endif

                @if($method->api_enabled)
                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5">{{ __('admin.delivery.api_on') }}</span>
                @endif

                @if($missing)
                    <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5" title="{{ __('admin.delivery.no_keys_hint') }}">
                        <i class="fas fa-key"></i> {{ __('admin.delivery.no_keys') }}
                    </span>
                @endif
            </div>

            @if($method->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $method->description }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span><b>{{ $method->formatted_price }}</b></span>
                <span>{{ $method->delivery_days }}</span>

                @if($method->free_delivery_threshold > 0)
                    <span>{{ __('admin.delivery.f_free_from') }}
                        <b>{{ number_format((float) $method->free_delivery_threshold, 0, ',', ' ') }}</b></span>
                @endif

                @if($method->weight_limit !== null)
                    <span>{{ __('admin.delivery.f_weight') }}: <b>{{ (float) $method->weight_limit }}</b></span>
                @endif

                @if($method->docs_url)
                    <a href="{{ $method->docs_url }}" target="_blank" rel="noopener"
                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        <i class="fas fa-book"></i> {{ __('admin.delivery.docs') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.delivery.edit', $method->id) }}"
               class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                      hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition"
               title="{{ __('admin.delivery.act_edit') }}">
                <i class="fas fa-pen"></i>
            </a>

            <form action="{{ route('admin.delivery.destroy', $method->id) }}" method="POST"
                  onsubmit="return confirm(@js(__('admin.delivery.confirm_delete')))">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                               hover:border-red-400 hover:text-red-600 px-3 py-2 text-sm font-semibold transition"
                        title="{{ __('admin.delivery.act_delete') }}">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
@empty
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-truck"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.delivery.empty') }}</h2>
        <p class="admin-hint max-w-xl mx-auto">{{ __('admin.delivery.empty_hint') }}</p>
    </div>
@endforelse
@endsection
