@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-xl">
    <div class="rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] p-6 sm:p-10 space-y-6">

        @include('Install::partials.steps', ['current' => 'license'])

        <div class="text-center space-y-2">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 grid place-items-center">
                <i data-lucide="key-round" class="w-6 h-6"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Лицензия</h1>
            <p class="text-gray-500 text-sm">Введите лицензионный ключ или промокод для активации CMS</p>
        </div>

        <form method="POST" action="{{ route('install.license') }}"
              x-data="{ type: 'license', submitting: false }"
              x-on:submit="submitting=true"
              class="space-y-6">
            @csrf

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-2xl p-4">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Выбор типа --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Тип активации</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-4 border-2 rounded-2xl cursor-pointer transition-colors"
                           :class="type === 'license' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="activation_type" value="license" x-model="type" class="sr-only">
                        <i data-lucide="key-round" class="w-5 h-5 shrink-0" :class="type === 'license' ? 'text-blue-600' : 'text-gray-400'"></i>
                        <div>
                            <div class="font-semibold text-sm text-gray-900">Лицензионный ключ</div>
                            <div class="text-xs text-gray-500 mt-0.5">У вас есть готовый ключ</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-4 border-2 rounded-2xl cursor-pointer transition-colors"
                           :class="type === 'promo' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="activation_type" value="promo" x-model="type" class="sr-only">
                        <i data-lucide="ticket-percent" class="w-5 h-5 shrink-0" :class="type === 'promo' ? 'text-blue-600' : 'text-gray-400'"></i>
                        <div>
                            <div class="font-semibold text-sm text-gray-900">Промокод</div>
                            <div class="text-xs text-gray-500 mt-0.5">Скидка по промокоду</div>
                        </div>
                    </label>
                </div>
            </div>

            <div x-show="type === 'license'" x-cloak>
                <label for="license_key" class="block text-sm font-medium text-gray-700 mb-1.5">Лицензионный ключ</label>
                <input type="text"
                       name="license_key" id="license_key"
                       value="{{ old('license_key') }}"
                       placeholder="XXXX-XXXX-XXXX-XXXX"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 text-gray-900 font-mono text-sm"
                       autocomplete="off">
                <p class="mt-2 text-xs text-gray-500">Формат: XXXX-XXXX-XXXX-XXXX</p>
            </div>

            <div x-show="type === 'promo'" x-cloak>
                <label for="promo_code" class="block text-sm font-medium text-gray-700 mb-1.5">Промокод</label>
                <input type="text"
                       name="promo_code" id="promo_code"
                       value="{{ old('promo_code') }}"
                       placeholder="Введите промокод"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 text-gray-900 font-mono text-sm uppercase"
                       autocomplete="off">
                <p class="mt-2 text-xs text-gray-500">Промокод можно использовать только один раз.</p>
            </div>

            <div class="rounded-xl bg-blue-50 border border-blue-100 p-3">
                <div class="flex items-start gap-2 text-xs text-gray-700">
                    <i data-lucide="info" class="w-4 h-4 mt-0.5 text-blue-600 shrink-0"></i>
                    <p>Лицензионный ключ или промокод обязательны для активации CMS. Если у вас нет ключа, обратитесь к разработчику.</p>
                </div>
            </div>

            <div class="flex justify-between items-center pt-1">
                <a href="{{ route('install.admin') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/30 transition-colors"
                        :disabled="submitting">
                    <span x-show="!submitting">Продолжить</span>
                    <span x-show="submitting" x-cloak>Проверяем…</span>
                    <i data-lucide="arrow-right" class="w-4 h-4" x-show="!submitting"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
