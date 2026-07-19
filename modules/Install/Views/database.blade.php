@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-xl">
    <form method="POST" action="{{ route('install.database') }}"
          class="rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] p-6 sm:p-10 space-y-6"
          x-data="{showPass:false, submitting:false}"
          x-on:submit="submitting=true">
        @csrf

        @include('Install::partials.steps', ['current' => 'database'])

        {{-- Ошибки валидации/подключения --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-2xl p-4">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Заголовок --}}
        <div class="text-center space-y-2">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 grid place-items-center">
                <i data-lucide="database" class="w-6 h-6"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Настройка базы данных</h2>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                <i data-lucide="database" class="w-3.5 h-3.5"></i>
                PostgreSQL
            </div>
            <p class="text-gray-500 text-sm">
                Укажите параметры подключения к вашей базе PostgreSQL. Эти данные будут записаны в <span class="font-mono">.env</span>.
            </p>
        </div>

        {{-- Поля --}}
        <div class="space-y-4">
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label for="host" class="block mb-1 text-sm font-medium text-gray-700">Хост</label>
                    <input type="text" name="host" id="host"
                           value="{{ old('host', '127.0.0.1') }}"
                           autocomplete="off"
                           placeholder="127.0.0.1"
                           class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                           required autofocus>
                </div>
                <div>
                    <label for="port" class="block mb-1 text-sm font-medium text-gray-700">Порт</label>
                    <input type="text" name="port" id="port"
                           value="{{ old('port', $defaultPort) }}"
                           inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                           class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                           required>
                </div>
            </div>
            <p class="text-xs text-gray-500 -mt-2">Стандартный порт PostgreSQL — <span class="font-mono">5432</span>.</p>

            <div>
                <label for="database" class="block mb-1 text-sm font-medium text-gray-700">База данных</label>
                <input type="text"
                       name="database" id="database"
                       value="{{ old('database') }}"
                       placeholder="имя существующей базы"
                       autocomplete="off"
                       class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    База должна быть создана заранее и доступна пользователю ниже.
                </p>
            </div>

            <div>
                <label for="username" class="block mb-1 text-sm font-medium text-gray-700">Пользователь</label>
                <input type="text" name="username" id="username"
                       value="{{ old('username') }}"
                       autocomplete="username"
                       class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                       required>
            </div>

            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-gray-700">Пароль</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" id="password"
                           value="{{ old('password') }}"
                           placeholder="●●●●●●"
                           autocomplete="new-password"
                           class="w-full pr-24 px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    <button type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-2 py-1 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100"
                            x-on:click="showPass=!showPass">
                        <span x-show="!showPass">Показать</span>
                        <span x-show="showPass">Скрыть</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Подсказка --}}
        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
            <div class="flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 mt-0.5 text-blue-500 shrink-0"></i>
                <p>
                    Если подключение не удаётся: проверьте правильность хоста, порта и прав пользователя,
                    а также доступ на запись в <span class="font-mono">storage/</span> (логи/кэш).
                    Вы можете вернуться к <a href="{{ route('install.requirements') }}" class="text-blue-600 hover:underline">проверке требований</a>.
                </p>
            </div>
        </div>

        {{-- Кнопка --}}
        <div class="text-center pt-1">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/30 transition-colors"
                    :disabled="submitting">
                <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                </svg>
                <i data-lucide="arrow-right" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? 'Сохраняем…' : 'Продолжить'"></span>
            </button>

            <div class="mt-3 text-xs">
                <a href="{{ route('install.requirements') }}" class="text-gray-500 hover:text-gray-700 hover:underline">
                    ← Вернуться к требованиям
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
