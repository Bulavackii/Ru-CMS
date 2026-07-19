@extends('layouts.guest')

@section('title', 'Настройка двухфакторной аутентификации')

@section('content')
    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-900 border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700 dark:text-blue-400 flex items-center justify-center gap-2">
            <i class="fas fa-shield-alt"></i> Настройка двухфакторной аутентификации
        </h2>

        {{-- ✅ Сообщение об успехе --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        {{-- Recovery codes --}}
        @if (session('recovery_codes'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg">
                <strong class="block mb-2">⚠️ Сохраните эти коды восстановления в безопасном месте:</strong>
                <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                    @foreach (session('recovery_codes') as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
                <p class="text-xs mt-2">Используйте эти коды, если потеряете доступ к приложению аутентификатора.</p>
            </div>
        @endif

        {{-- ❗ Ошибки --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 📱 Инструкция --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h3 class="font-semibold mb-2 flex items-center gap-2">
                <i class="fas fa-info-circle"></i> Инструкция:
            </h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li>Установите приложение Google Authenticator на свой телефон (iOS или Android)</li>
                <li>Отсканируйте QR-код ниже в приложении</li>
                <li>Введите 6-значный код из приложения для подтверждения</li>
            </ol>
        </div>

        {{-- 🔐 QR код и секрет --}}
        <div class="flex flex-col items-center space-y-4">
            <div class="bg-white p-4 rounded-lg border-2 border-gray-300">
                @if($qrCodeUrl)
                    <img src="{{ $qrCodeUrl }}" alt="QR Code" class="w-48 h-48">
                @else
                    <p class="text-red-600">QR код недоступен</p>
                @endif
            </div>
            
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Или введите код вручную:</p>
                <div class="bg-gray-100 dark:bg-gray-800 px-4 py-2 rounded font-mono text-sm break-all">
                    {{ $secret }}
                </div>
            </div>
        </div>

        {{-- 🔐 Форма подтверждения --}}
        <form method="POST" action="{{ route('two-factor.enable') }}" class="space-y-6">
            @csrf

            {{-- 🔑 Код 2FA --}}
            <div>
                <label for="code" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                    <i class="fas fa-key mr-1"></i> Код из приложения
                </label>
                <input id="code"
                       type="text"
                       name="code"
                       required
                       autofocus
                       maxlength="6"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       title="Введите 6-значный код из Google Authenticator"
                       class="w-full px-4 py-2 text-center text-2xl tracking-widest border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none @error('code') border-red-500 @enderror"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                    Введите 6-значный код из приложения для подтверждения.
                </p>
                @error('code')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🚀 Кнопка --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md shadow transition transform hover:scale-105">
                    <i class="fas fa-check mr-1"></i> Включить двухфакторную аутентификацию
                </button>
            </div>
        </form>

        {{-- 🔗 Ссылка назад --}}
        <div class="text-center text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Вернуться в личный кабинет</a>
        </div>
    </div>
@endsection

