@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-2xl max-h-full flex flex-col">
    <div class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden">

        @php $hasErrors = collect($requirements ?? [])->contains(false); @endphp

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'requirements'])
            <div class="text-center">
                <div class="mx-auto w-10 h-10 rounded-xl bg-gray-900 text-white grid place-items-center mb-2">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Системные требования</h2>
                <p class="text-gray-500 text-xs flex items-center justify-center gap-1">
                    <i data-lucide="scan-search" class="w-3.5 h-3.5"></i>
                    Проверка окружения выполняется автоматически при каждом открытии страницы
                </p>
            </div>
        </div>

        {{-- Список: две колонки, скроллится внутри при нехватке высоты --}}
        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0">
            <div class="rounded-2xl border border-gray-200 overflow-hidden">
                <div class="grid sm:grid-cols-2 divide-y sm:divide-y-0 divide-gray-100">
                    @foreach ($requirements as $label => $ok)
                        <div class="px-4 py-2.5 flex items-center justify-between gap-2 sm:odd:border-r sm:border-b sm:last:border-b-0 border-gray-100"
                             title="@switch($label)
                                 @case('PHP >= 8.5')Требуется PHP 8.5 (Laravel 12). Текущая версия: {{ PHP_VERSION }}@break
                                 @case('PDO PostgreSQL (pdo_pgsql)')Расширение для работы с PostgreSQL@break
                                 @case('Fileinfo')Определение типов загружаемых файлов@break
                                 @case('Writable: storage/')Права на запись: логи, кэш, сессии@break
                                 @case('Writable: bootstrap/cache')Права на запись: кэш конфигурации и маршрутов@break
                                 @default Обязательное расширение PHP
                             @endswitch">
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="{{ $ok ? 'check-circle-2' : 'x-circle' }}"
                                   class="w-4 h-4 shrink-0 {{ $ok ? 'text-gray-900' : 'text-gray-400' }}"></i>
                                <span class="text-gray-800 text-xs font-medium truncate">{{ $label }}</span>
                            </div>
                            <span class="text-[10px] font-bold shrink-0 px-1.5 py-0.5 rounded-full {{ $ok ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-500' }}">
                                {{ $ok ? 'OK' : 'НЕТ' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($hasErrors)
                <div class="mt-3 rounded-2xl border border-gray-300 bg-gray-50 p-3 text-gray-700 text-xs">
                    <div class="font-semibold mb-1 flex items-center gap-1.5">
                        <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Как исправить
                    </div>
                    <ul class="space-y-1 pl-5 list-disc">
                        <li>Убедитесь, что запущен PHP <strong>8.5+</strong>, и перезапустите веб-сервер.</li>
                        <li>Включите отсутствующие расширения в <span class="font-mono">php.ini</span> (например, <span class="font-mono">pdo_pgsql</span>).</li>
                        <li>Дайте права на запись: <span class="font-mono">storage/</span> и <span class="font-mono">bootstrap/cache</span>.</li>
                        <li>Затем нажмите «Проверить снова».</li>
                    </ul>
                </div>
            @else
                <div class="mt-3 rounded-2xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 flex items-center justify-center gap-2">
                    <i data-lucide="party-popper" class="w-3.5 h-3.5"></i>
                    Всё готово — окружение полностью соответствует требованиям.
                </div>
            @endif
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                <a href="{{ route('install.database') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 {{ $hasErrors ? 'pointer-events-none opacity-40' : '' }} bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-gray-900/25 transition-colors">
                    <span>Продолжить</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
                <a href="{{ url()->current() }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-800 px-5 py-2.5 rounded-xl text-sm font-semibold border border-gray-300 transition-colors">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i> Проверить снова
                </a>
                <a href="{{ route('install.welcome') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 px-3 py-2.5 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
