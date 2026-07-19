@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">🔑 Лицензия</h1>
                <p class="text-lg text-gray-600">Введите лицензионный ключ или промокод для активации CMS</p>
            </div>

            <form method="POST" action="{{ route('install.license') }}" 
                  x-data="{ type: 'license', submitting: false }"
                  x-on:submit="submitting=true">
                @csrf
                
                {{-- Ошибки --}}
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4 mb-6">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Выбор типа --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Тип активации:</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                               :class="type === 'license' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="activation_type" value="license" x-model="type" class="sr-only">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">🔑 Лицензионный ключ</div>
                                <div class="text-sm text-gray-600 mt-1">У вас есть готовый ключ</div>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                               :class="type === 'promo' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="activation_type" value="promo" x-model="type" class="sr-only">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">🎟️ Промокод</div>
                                <div class="text-sm text-gray-600 mt-1">Используйте промокод для скидки</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Поле лицензионного ключа --}}
                <div x-show="type === 'license'" class="mb-6">
                    <label for="license_key" class="block text-sm font-medium text-gray-700 mb-2">
                        Лицензионный ключ
                    </label>
                    <input type="text"
                           name="license_key"
                           id="license_key"
                           value="{{ old('license_key') }}"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-mono text-sm"
                           autocomplete="off">
                    <p class="mt-2 text-sm text-gray-500">
                        Введите лицензионный ключ, полученный от разработчика. Формат: XXXX-XXXX-XXXX-XXXX
                    </p>
                </div>

                {{-- Поле промокода --}}
                <div x-show="type === 'promo'" class="mb-6">
                    <label for="promo_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Промокод
                    </label>
                    <input type="text"
                           name="promo_code"
                           id="promo_code"
                           value="{{ old('promo_code') }}"
                           placeholder="Введите промокод"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-mono text-sm uppercase"
                           autocomplete="off">
                    <p class="mt-2 text-sm text-gray-500">
                        Введите промокод для получения скидки. Промокод будет применен при создании подписки.
                    </p>
                </div>

                {{-- Информация --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                        <div class="text-sm text-gray-700">
                            <p class="font-semibold mb-1">Важно:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Лицензионный ключ или промокод обязательны для активации CMS</li>
                                <li>Если у вас нет ключа, обратитесь к разработчику</li>
                                <li>Промокод можно использовать только один раз</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Кнопки --}}
                <div class="flex justify-between items-center">
                    <a href="{{ route('install.admin') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        ← Назад
                    </a>
                    <button type="submit" 
                            class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="submitting">
                        <span x-show="!submitting">Продолжить →</span>
                        <span x-show="submitting">Проверяем...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

