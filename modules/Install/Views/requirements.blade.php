@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-xl">
    <div class="rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] p-6 sm:p-10 space-y-8">

        @php $hasErrors = collect($requirements ?? [])->contains(false); @endphp

        @include('Install::partials.steps', ['current' => 'requirements'])

        {{-- Заголовок --}}
        <div class="text-center space-y-2">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 grid place-items-center">
                <i data-lucide="clipboard-check" class="w-6 h-6"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Системные требования</h2>
            <p class="text-gray-500 text-sm sm:text-base">
                Убедитесь, что ваша система соответствует следующим параметрам.
            </p>
        </div>

        {{-- Список требований --}}
        <div class="bg-gray-50/80 border border-gray-200 rounded-2xl divide-y divide-gray-200 overflow-hidden">
            @foreach ($requirements as $label => $ok)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i data-lucide="{{ $ok ? 'check-circle-2' : 'x-circle' }}"
                               class="w-5 h-5 {{ $ok ? 'text-green-500' : 'text-red-500' }}"></i>
                            <span class="text-gray-800 font-medium text-sm">{{ $label }}</span>
                        </div>
                        <span class="text-xs font-bold {{ $ok ? 'text-green-600' : 'text-red-600' }}">
                            {{ $ok ? 'OK' : 'Ошибка' }}
                        </span>
                    </div>
                    <div class="mt-2 text-gray-500 text-xs sm:text-sm pl-8">
                        @switch($label)
                            @case('PHP >= 8.5')
                                Требуется PHP 8.5 для поддержки Laravel 12. Текущая версия: <span class="font-mono">{{ PHP_VERSION }}</span>.
                                @break
                            @case('PDO PostgreSQL (pdo_pgsql)')
                                Необходимо для работы с PostgreSQL. Установите/включите расширение <span class="font-mono">pdo_pgsql</span>.
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
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
                <div class="font-semibold mb-1 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> Что делать, если что-то не ок?
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Проверьте, что используете PHP <strong>8.5</strong> и перезапустите веб-сервер.</li>
                    <li>Установите/включите отсутствующие расширения PHP (например, <span class="font-mono">pdo_pgsql</span>, <span class="font-mono">fileinfo</span>).</li>
                    <li>Проверьте права: <span class="font-mono">storage/</span> и <span class="font-mono">bootstrap/cache</span> должны быть доступными для записи.</li>
                    <li>После исправлений нажмите «Проверить снова» — страница перезагрузится.</li>
                </ul>
            </div>
        @endif

        {{-- Действия --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('install.database') }}"
               class="inline-flex items-center gap-2 {{ $hasErrors ? 'pointer-events-none opacity-40' : '' }} bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/30 transition-colors">
                <span>Продолжить установку</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
            <a href="{{ route('install.features') }}"
               class="inline-flex items-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-700 px-6 py-3 rounded-xl text-sm font-semibold border border-blue-100 transition-colors">
                <i data-lucide="star" class="w-4 h-4"></i> Возможности
            </a>
            <a href="{{ url()->current() }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-xl text-sm font-semibold border border-gray-200 transition-colors">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i> Проверить снова
            </a>
        </div>

        <p class="text-xs text-gray-400 text-center">
            Если проблемы не решаются — обратитесь к документации вашего хостинга/ОС или к администратору сервера.
        </p>
    </div>
</div>
@endsection
