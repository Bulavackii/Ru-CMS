@extends('layouts.admin')

@section('title', 'Геолокация')

@section('content')
    <h1 class="text-3xl font-extrabold mb-6 text-gray-800 flex items-center gap-2">
        🌍 Геолокация пользователя
    </h1>

    <div class="bg-white rounded-xl shadow-lg p-6 max-w-4xl border border-gray-200 space-y-8 animate-fade-in">

        {{-- 📍 IP-адрес --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="font-semibold text-gray-700 text-sm mb-1 flex items-center">
                    <i class="fas fa-wifi text-blue-500 mr-1"></i> IP-адрес
                </p>
                <p id="ip-address" class="text-xl font-mono text-gray-900 select-all">{{ request()->ip() }}</p>
            </div>
            <button onclick="copyToClipboard('ip-address')"
                class="text-sm text-blue-600 hover:text-blue-800 transition flex items-center gap-1 mt-1"
                title="Скопировать IP">
                <i class="fas fa-copy"></i> Скопировать
            </button>
        </div>

        {{-- 💻 Информация об устройстве --}}
        <div>
            <p class="font-semibold text-gray-700 text-sm mb-1">
                <i class="fas fa-desktop text-green-500 mr-1"></i> User Agent (браузер / устройство)
            </p>
            <div class="bg-gray-100 text-gray-800 p-4 rounded-md text-sm font-mono break-all">
                {{ request()->userAgent() }}
            </div>
        </div>

        {{-- 🌐 Язык и время --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                <p class="text-sm font-medium text-gray-600 mb-1">
                    <i class="fas fa-language text-indigo-500 mr-1"></i> Язык браузера
                </p>
                <p class="text-gray-800">{{ request()->server('HTTP_ACCEPT_LANGUAGE') }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                <p class="text-sm font-medium text-gray-600 mb-1">
                    <i class="fas fa-clock text-orange-500 mr-1"></i> Время запроса
                </p>
                <p class="text-gray-800">{{ now()->format('d.m.Y H:i:s') }}</p>
            </div>
        </div>

        {{-- 🌍 Геолокация по IP --}}
        <div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300 text-gray-700">
            <p class="text-sm mb-2 font-medium flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-pink-500"></i> Геолокация по IP:
            </p>

            @if ($location)
                <ul class="text-sm pl-4 list-disc">
                    <li>Город: <span class="text-gray-800 italic">{{ $location->cityName ?? '—' }}</span></li>
                    <li>Регион: <span class="text-gray-800 italic">{{ $location->regionName ?? '—' }}</span></li>
                    <li>Страна: <span class="text-gray-800 italic">{{ $location->countryName ?? '—' }}</span></li>
                    <li>Провайдер: <span class="text-gray-800 italic">{{ $location->org ?? '—' }}</span></li>
                </ul>
            @else
                <p class="italic text-sm text-gray-500">⚠️ Не удалось определить местоположение. Возможна блокировка API или
                    локальный IP (127.0.0.1).</p>
            @endif
        </div>

        {{-- 🔗 Примечание --}}
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded text-yellow-800 text-sm flex items-start gap-2">
            <i class="fas fa-info-circle mt-1"></i>
            <div>
                <p class="font-semibold">Как подключить API:</p>
                Используйте <a href="https://ip-api.com/" class="underline hover:text-yellow-700"
                    target="_blank">ip-api.com</a> или <a href="https://ipinfo.io/" target="_blank"
                    class="underline hover:text-yellow-700">ipinfo.io</a> для автоматического получения геоданных по IP.
            </div>
        </div>
    </div>

    {{-- ✂ JS для копирования --}}
    <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('IP-адрес скопирован!');
            });
        }
    </script>

    {{-- 💫 Анимация --}}
    <style>
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection
