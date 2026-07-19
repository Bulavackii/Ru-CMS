@php
    $currentCountryCode = auth()->check() && auth()->user()->country_code 
        ? auth()->user()->country_code 
        : (session('country_code', config('localization.default_country', 'RU')));
@endphp

<div class="country-switcher">
    <form method="POST" action="{{ route('localization.switch') }}" class="flex items-center gap-2" id="country-switcher-form">
        @csrf
        @if(request()->is('admin/*'))
            {{-- Компактный вид для админки --}}
            <select 
                name="country_code" 
                id="country_code"
                onchange="this.form.submit()"
                class="px-2 py-1.5 rounded border border-gray-600 dark:border-gray-500 bg-gray-800 dark:bg-gray-700 text-white text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none transition min-w-[120px]"
                title="Выбрать страну и язык">
            @foreach($countries as $country)
                <option value="{{ $country->code }}" {{ $country->code === $currentCountryCode ? 'selected' : '' }}>
                    {{ $country->flag ?? '🏳️' }} {{ $country->code }}
                </option>
            @endforeach
            </select>
        @else
            {{-- Полный вид для фронтенда --}}
            <label for="country_code" class="font-medium text-sm">Страна:</label>
            <select 
                name="country_code" 
                id="country_code"
                onchange="this.form.submit()"
                class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition text-sm min-w-[180px]">
            @foreach($countries as $country)
                <option value="{{ $country->code }}" {{ $country->code === $currentCountryCode ? 'selected' : '' }}>
                    {{ $country->flag ?? '🏳️' }} {{ $country->name }}
                    @if($country->native_name && $country->native_name !== $country->name)
                        ({{ $country->native_name }})
                    @endif
                </option>
            @endforeach
            </select>
        @endif
    </form>
</div>

<style>
    .country-switcher select {
        cursor: pointer;
    }
    
    .country-switcher select:hover {
        border-color: #3b82f6;
    }
</style>

