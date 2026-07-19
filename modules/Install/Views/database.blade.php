@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <form method="POST" action="{{ route('install.database') }}"
          class="bg-white shadow-xl rounded-2xl p-6 sm:p-10 w-full max-w-xl space-y-6 border border-gray-200"
          x-data="{showPass:false, submitting:false}"
          x-on:submit="submitting=true">
        {{-- 🛡 CSRF --}}
        @csrf

        {{-- 🧭 Шаги мастера --}}
        <ol class="flex items-center justify-center gap-2 text-xs text-gray-500">
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">1. Приветствие</li>
            <li class="px-2 py-1 rounded bg-blue-600 text-white font-semibold">2. Требования/БД</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">3. Администратор</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">4. Лицензия</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">5. Готово</li>
        </ol>

        {{-- 🔔 Ошибки валидации/подключения --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 🧩 Заголовок --}}
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center justify-center gap-2">
                <i class="fas fa-database text-blue-600"></i> Настройка базы данных
            </h2>
            <p class="text-gray-600 text-sm sm:text-base">
                Укажите параметры подключения MySQL. Эти данные будут записаны в <span class="font-mono">.env</span>.
            </p>
        </div>

        {{-- ⚙️ Драйвер (оставлен на будущее; контроллер уже ожидает `connection`) --}}
        <div>
            <label for="connection" class="block mb-1 text-sm font-medium text-gray-700">Драйвер</label>
            <select id="connection" name="connection"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500">
                <option value="mysql" selected>MySQL / MariaDB</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">
                Сейчас поддерживается MySQL/MariaDB. Другие драйверы можно будет включить позднее.
            </p>
        </div>

        {{-- 📋 Поля --}}
        <div class="space-y-4">
            {{-- Хост --}}
            <div>
                <label for="host" class="block mb-1 text-sm font-medium text-gray-700">Хост</label>
                <input type="text"
                       name="host" id="host"
                       value="{{ old('host', '127.0.0.1') }}"
                       autocomplete="host"
                       placeholder="например, 127.0.0.1 или db.internal"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500"
                       required autofocus>
                <p class="mt-1 text-xs text-gray-500">
                    Если база на этом же сервере — обычно <span class="font-mono">127.0.0.1</span> или <span class="font-mono">localhost</span>.
                </p>
            </div>

            {{-- Порт --}}
            <div>
                <label for="port" class="block mb-1 text-sm font-medium text-gray-700">Порт</label>
                <input type="text"
                       name="port" id="port"
                       value="{{ old('port', '3306') }}"
                       inputmode="numeric" pattern="[0-9]*"
                       autocomplete="off"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Стандартный порт MySQL — <span class="font-mono">3306</span>.
                </p>
            </div>

            {{-- База данных --}}
            <div>
                <label for="database" class="block mb-1 text-sm font-medium text-gray-700">База данных</label>
                <input type="text"
                       name="database" id="database"
                       value="{{ old('database') }}"
                       placeholder="имя существующей базы"
                       autocomplete="off"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    База должна быть создана заранее и доступна пользователю ниже.
                </p>
            </div>

            {{-- Пользователь --}}
            <div>
                <label for="username" class="block mb-1 text-sm font-medium text-gray-700">Пользователь</label>
                <input type="text"
                       name="username" id="username"
                       value="{{ old('username') }}"
                       autocomplete="username"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Пользователь MySQL с правами на выбранную базу (SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER).
                </p>
            </div>

            {{-- Пароль + «Показать» --}}
            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-gray-700">Пароль</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" id="password"
                           value="{{ old('password') }}"
                           placeholder="●●●●●●"
                           autocomplete="new-password"
                           class="w-full pr-24 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500">
                    <button type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-2 py-1 rounded border border-gray-200 bg-gray-50 hover:bg-gray-100"
                            x-on:click="showPass=!showPass">
                        <span x-show="!showPass">Показать</span>
                        <span x-show="showPass">Скрыть</span>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Пароль может быть пустым, если это разрешено настройкой сервера БД (не рекомендуется).
                </p>
            </div>
        </div>

        {{-- ℹ️ Подсказка/FAQ --}}
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
            <div class="flex items-start gap-2">
                <i class="fas fa-info-circle mt-0.5 text-blue-500"></i>
                <p>
                    Если подключение не удаётся: проверьте правильность хоста, порта и прав пользователя,
                    а также доступ на запись в <span class="font-mono">storage/</span> (логи/кэш).
                    Вы можете вернуться к <a href="{{ route('install.requirements') }}" class="text-blue-600 hover:underline">проверке требований</a>.
                </p>
            </div>
        </div>

        {{-- ✅ Кнопка --}}
        <div class="text-center pt-1">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition"
                    :disabled="submitting">
                <span class="inline-block w-4" x-show="submitting" x-cloak>
                    <svg viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                    </svg>
                </span>
                <i class="fas fa-arrow-right"></i>
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
