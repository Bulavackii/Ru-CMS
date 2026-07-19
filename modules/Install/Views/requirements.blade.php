@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-xl space-y-8 border border-gray-200">

        {{-- 🧭 Прогресс --}}
        @php
            $hasErrors = collect($requirements ?? [])->contains(false);
        @endphp
        <div class="flex items-center justify-center gap-2 text-xs">
            <span class="px-2 py-1 rounded bg-gray-100">1. Приветствие</span>
            <span class="px-2 py-1 rounded {{ $hasErrors ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                2. Требования {{ $hasErrors ? '— ошибки' : '— OK' }}
            </span>
            <span class="px-2 py-1 rounded bg-gray-100">3. База/Админ</span>
            <span class="px-2 py-1 rounded bg-gray-100">4. Готово</span>
        </div>

        {{-- Заголовок --}}
        <div class="text-center space-y-2">
            <h2 class="text-3xl font-extrabold text-gray-900 flex items-center justify-center gap-3">
                <i class="fas fa-clipboard-check text-blue-600 text-2xl"></i> Системные требования
            </h2>
            <p class="text-gray-600 text-sm sm:text-base">
                Убедитесь, что ваша система соответствует следующим параметрам.
            </p>
        </div>

        {{-- Список требований --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl divide-y divide-gray-200">
            @foreach ($requirements as $label => $ok)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas {{ $ok ? 'fa-circle-check text-green-500' : 'fa-circle-xmark text-red-500' }} text-lg"></i>
                            <span class="text-gray-800 font-medium">{{ $label }}</span>
                        </div>
                        <span class="text-sm font-bold {{ $ok ? 'text-green-600' : 'text-red-600' }}">
                            {{ $ok ? 'OK' : 'Ошибка' }}
                        </span>
                    </div>
                    <div class="mt-2 text-gray-500 text-xs sm:text-sm pl-8">
                        @switch($label)
                            @case('PHP >= 8.5')
                                Требуется PHP 8.5 для поддержки Laravel 12. Текущая версия: <span class="font-mono">{{ PHP_VERSION }}</span>.
                                @break
                            @case('PDO')
                                Необходимо для работы с базами данных (MySQL). Установите расширение <span class="font-mono">pdo_mysql</span>.
                                @break
                            @case('Fileinfo')
                                Нужен для корректной обработки загружаемых файлов/медиа. Установите/включите расширение <span class="font-mono">fileinfo</span>.
                                @break
                            @case('Writable: storage/')
                                Доступ на запись нужен для логов, кэша и сессий. Проверьте права на <span class="font-mono">storage/</span>.
                                Команда (Linux): <span class="font-mono">chmod -R 775 storage bootstrap/cache</span>.
                                @break
                        @endswitch
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Подсказки при ошибках --}}
        @if($hasErrors)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
                <div class="font-semibold mb-1 flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation"></i> Что делать, если что-то не ок?
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Проверьте, что используете PHP <strong>8.5</strong> и перезапустите веб-сервер.</li>
                    <li>Установите/включите отсутствующие расширения PHP (например, <span class="font-mono">pdo_mysql</span>, <span class="font-mono">fileinfo</span>).</li>
                    <li>Проверьте права: <span class="font-mono">storage/</span> и <span class="font-mono">bootstrap/cache</span> должны быть доступными для записи.</li>
                    <li>После исправлений нажмите «Проверить снова» — страница перезагрузится.</li>
                </ul>
            </div>
        @endif

        {{-- Действия --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('install.database') }}"
               class="inline-flex items-center gap-2 {{ $hasErrors ? 'pointer-events-none opacity-50' : '' }} bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition">
                <i class="fas fa-arrow-right"></i> Продолжить установку
            </a>
            <a href="{{ route('install.features') }}"
               class="inline-flex items-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-700 px-6 py-3 rounded-lg text-sm font-semibold border border-blue-200 transition">
                <i class="fas fa-star"></i> Посмотреть возможности
            </a>
            <a href="{{ url()->current() }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-lg text-sm font-semibold border border-gray-200 transition">
                <i class="fas fa-rotate-right"></i> Проверить снова
            </a>
        </div>

        {{-- Мелкая справка --}}
        <p class="text-xs text-gray-500 text-center">
            Если проблемы не решаются — обратитесь к документации вашего хостинга/OS или к администратору сервера.
        </p>
    </div>
</div>
@endsection
