@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-xl space-y-8 border border-gray-200">

        <div class="text-center space-y-2">
            <h2 class="text-3xl font-extrabold text-gray-900 flex items-center justify-center gap-3">
                <i class="fas fa-clipboard-check text-blue-600 text-2xl"></i> Системные требования
            </h2>
            <p class="text-gray-600 text-sm sm:text-base">
                Убедитесь, что ваша система соответствует следующим параметрам.
            </p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl divide-y divide-gray-200">
            @foreach ($requirements as $label => $ok)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas {{ $ok ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} text-lg"></i>
                            <span class="text-gray-800 font-medium">{{ $label }}</span>
                        </div>
                        <span class="text-sm font-bold {{ $ok ? 'text-green-600' : 'text-red-600' }}">
                            {{ $ok ? 'OK' : 'Ошибка' }}
                        </span>
                    </div>
                    <div class="mt-2 text-gray-500 text-xs sm:text-sm pl-8">
                        @switch($label)
                            @case('PHP >= 8.1') Требуется современная версия PHP для поддержки Laravel. @break
                            @case('PDO') Необходимо для работы с базами данных. @break
                            @case('Fileinfo') Для обработки загружаемых файлов. @break
                            @case('Writable: storage/') Доступ на запись нужен для логов и кэша. @break
                        @endswitch
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center">
            <a href="{{ route('install.database') }}"
               class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition">
                <i class="fas fa-arrow-right"></i> Продолжить установку
            </a>
        </div>
    </div>
</div>
@endsection
