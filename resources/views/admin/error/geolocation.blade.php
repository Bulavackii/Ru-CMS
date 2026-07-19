@extends('layouts.admin')

@section('title', 'Геолокация')

@section('content')
    {{-- 🧭 Заголовок страницы --}}
    <h1 class="text-3xl font-extrabold mb-6 text-gray-800 dark:text-white flex items-center gap-2">
        🌍 Геолокация пользователя
    </h1>

    {{-- 🧾 Блок с информацией --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-4xl border border-gray-200 dark:border-gray-700 space-y-8 animate-fade-in">

        {{-- 📍 IP-адрес --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300 text-sm mb-1 flex items-center">
                    <i class="fas fa-wifi text-blue-500 mr-1"></i> IP-адрес
                </p>
                <p id="ip-address" class="text-xl font-mono text-gray-900 dark:text-white select-all">
                    {{ request()->ip() }}
                </p>
            </div>
            <button onclick="copyToClipboard('ip-address')"
                class="text-sm text-blue-600 hover:text-blue-800 dark:hover:text-blue-400 transition flex items-center gap-1 mt-1"
                title="Скопировать IP">
                <i class="fas fa-copy"></i> Скопировать
            </button>
        </div>

        {{-- 💻 User Agent --}}
        <div>
            <p class="font-semibold text-gray-700 dark:text-gray-300 text-sm mb-1">
                <i class="fas fa-desktop text-green-500 mr-1"></i> Информация об устройстве
            </p>
            <div class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 p-4 rounded-md text-sm font-mono break-all">
                {{ request()->userAgent() }}
            </div>
        </div>

        {{-- 🌐 Язык и время --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- 🌍 Язык --}}
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-100 dark:border-gray-600">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                    <i class="fas fa-language text-indigo-500 mr-1"></i> Язык браузера
                </p>
                <p class="text-gray-800 dark:text-white">{{ request()->server('HTTP_ACCEPT_LANGUAGE') }}</p>
            </div>

            {{-- 🕒 Время --}}
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-100 dark:border-gray-600">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                    <i class="fas fa-clock text-orange-500 mr-1"></i> Время запроса
                </p>
                <p class="text-gray-800 dark:text-white">{{ now()->format('d.m.Y H:i:s') }}</p>
            </div>
        </div>

        {{-- 🌍 Геолокация по IP --}}
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200">
            <p class="text-sm mb-2 font-medium flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-pink-500"></i> Геолокация по IP:
            </p>

            @if ($location)
                <ul class="text-sm pl-4 list-disc">
                    <li>Город: <span class="text-gray-800 dark:text-white italic">{{ $location->cityName ?? '—' }}</span></li>
                    <li>Регион: <span class="text-gray-800 dark:text-white italic">{{ $location->regionName ?? '—' }}</span></li>
                    <li>Страна: <span class="text-gray-800 dark:text-white italic">{{ $location->countryName ?? '—' }}</span></li>
                    <li>Провайдер: <span class="text-gray-800 dark:text-white italic">{{ $location->org ?? '—' }}</span></li>
                </ul>
            @else
                <p class="italic text-sm text-gray-500 dark:text-gray-400">
                    ⚠️ Не удалось определить местоположение. Возможна блокировка API или используется локальный IP (127.0.0.1).
                </p>
            @endif
        </div>

        {{-- ℹ️ Примечание --}}
        <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-600 p-4 rounded text-yellow-800 dark:text-yellow-200 text-sm flex items-start gap-2">
            <i class="fas fa-info-circle mt-1"></i>
            <div>
                <p class="font-semibold">Как подключить геолокацию:</p>
                Используйте <a href="https://ip-api.com/" class="underline hover:text-yellow-700 dark:hover:text-yellow-400"
                    target="_blank">ip-api.com</a> или <a href="https://ipinfo.io/" target="_blank"
                    class="underline hover:text-yellow-700 dark:hover:text-yellow-400">ipinfo.io</a> для определения страны, города и провайдера.
            </div>
        </div>
    </div>

    {{-- 📋 JS для копирования IP --}}
    <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('IP-адрес скопирован!');
            });
        }
    </script>

    {{-- 💫 Анимация появления --}}
    <style>
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection
