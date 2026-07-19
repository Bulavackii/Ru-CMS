@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.database') }}"
          class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden"
          x-data="{showPass:false, submitting:false}"
          x-on:submit="submitting=true">
        @csrf

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'database'])
            <div class="text-center">
                <div class="mx-auto w-10 h-10 rounded-xl bg-gray-900 text-white grid place-items-center mb-2">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900 inline-flex items-center gap-2">
                    Настройка базы данных
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px] font-semibold border border-gray-200" title="Единственная поддерживаемая СУБД — открытая и бесплатная">
                        <i data-lucide="database-zap" class="w-3 h-3"></i> PostgreSQL
                    </span>
                </h2>
                <p class="text-gray-500 text-xs">Данные будут записаны в <span class="font-mono">.env</span> — подключение проверяется до записи</p>
            </div>
        </div>

        {{-- Поля --}}
        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if ($errors->any())
                <div class="bg-gray-900 text-white text-xs rounded-2xl p-3">
                    <div class="flex items-center gap-1.5 font-semibold mb-1"><i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i> Не получилось</div>
                    <ul class="list-disc pl-5 space-y-0.5 text-gray-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-3 gap-2.5">
                <div class="col-span-2">
                    <label for="host" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="server" class="w-3 h-3 text-gray-400"></i> Хост
                    </label>
                    <input type="text" name="host" id="host"
                           value="{{ old('host', '127.0.0.1') }}"
                           autocomplete="off"
                           placeholder="127.0.0.1"
                           title="Обычно 127.0.0.1 (локальный сервер) или адрес сервера БД"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required autofocus>
                </div>
                <div>
                    <label for="port" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="plug" class="w-3 h-3 text-gray-400"></i> Порт
                    </label>
                    <input type="text" name="port" id="port"
                           value="{{ old('port', $defaultPort) }}"
                           inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                           title="Стандартный порт PostgreSQL — 5432"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required>
                </div>
            </div>

            <div>
                <label for="database" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                    <i data-lucide="database" class="w-3 h-3 text-gray-400"></i> База данных
                </label>
                <input type="text"
                       name="database" id="database"
                       value="{{ old('database') }}"
                       placeholder="имя существующей базы"
                       autocomplete="off"
                       title="База должна быть создана заранее: CREATE DATABASE имя OWNER пользователь;"
                       class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                       required>
                <p class="mt-1 text-[11px] text-gray-400 flex items-center gap-1">
                    <i data-lucide="info" class="w-3 h-3 shrink-0"></i>
                    Создаётся заранее: <span class="font-mono">CREATE DATABASE имя OWNER пользователь;</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="username" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="user" class="w-3 h-3 text-gray-400"></i> Пользователь
                    </label>
                    <input type="text" name="username" id="username"
                           value="{{ old('username') }}"
                           autocomplete="username"
                           title="Роль PostgreSQL с правом LOGIN и доступом к базе"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required>
                </div>
                <div>
                    <label for="password" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="lock" class="w-3 h-3 text-gray-400"></i> Пароль
                    </label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'"
                               name="password" id="password"
                               value="{{ old('password') }}"
                               placeholder="●●●●●●"
                               autocomplete="new-password"
                               class="w-full pr-10 px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm text-gray-900 focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900">
                        <button type="button"
                                class="absolute right-1.5 inset-y-0 my-auto w-7 h-7 grid place-items-center rounded-lg text-gray-400 hover:text-gray-800 hover:bg-gray-100"
                                x-on:click="showPass=!showPass"
                                :title="showPass ? 'Скрыть пароль' : 'Показать пароль'">
                            {{-- x-show на span-обёртке: Lucide заменяет <i> на <svg> и теряет Alpine-атрибуты --}}
                            <span x-show="!showPass" class="grid place-items-center"><i data-lucide="eye" class="w-4 h-4"></i></span>
                            <span x-show="showPass" x-cloak class="grid place-items-center"><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-[11px] text-gray-500 flex items-start gap-1.5">
                <i data-lucide="life-buoy" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-600"></i>
                <span>Не подключается? Проверьте, что служба PostgreSQL запущена, порт верный, а у пользователя есть доступ к базе. Требования можно <a href="{{ route('install.requirements') }}" class="underline hover:text-gray-800">перепроверить</a>.</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3 flex items-center justify-between">
            <a href="{{ route('install.requirements') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gray-900 hover:bg-black disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-gray-900/25 transition-colors"
                    :disabled="submitting">
                <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                </svg>
                <i data-lucide="cable" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? 'Проверяем подключение…' : 'Проверить и продолжить'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
