@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <form method="POST" action="{{ route('install.admin') }}"
          class="bg-white shadow-xl rounded-2xl p-6 sm:p-10 w-full max-w-xl space-y-6 border border-gray-200"
          x-data="{showPass:false, submitting:false, strength:0, tipsOpen:false}"
          x-on:submit="submitting=true">
        @csrf

        {{-- Шаги установки --}}
        <ol class="flex items-center justify-center gap-2 text-xs text-gray-500">
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">1. Приветствие</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">2. Требования</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">3. База данных</li>
            <li class="px-2 py-1 rounded bg-blue-600 text-white font-semibold">4. Администратор</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">5. Лицензия</li>
            <li class="px-2 py-1 rounded bg-gray-100 font-medium">6. Готово</li>
        </ol>

        {{-- Ошибки --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Заголовок --}}
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center justify-center gap-2">
                <i class="fas fa-user-shield text-blue-600"></i> Создание администратора
            </h2>
            <p class="text-gray-600 text-sm sm:text-base">
                Этот аккаунт получит полный доступ к панели управления. Позже вы сможете добавить других пользователей.
            </p>
        </div>

        {{-- Поля --}}
        <div class="space-y-4">
            {{-- Имя --}}
            <div>
                <label for="name" class="block mb-1 text-sm font-medium text-gray-700">Имя</label>
                <input type="text"
                       name="name" id="name"
                       placeholder="Админ"
                       value="{{ old('name', 'Админ') }}"
                       autocomplete="name"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-900 bg-white focus:ring focus:border-blue-500"
                       required autofocus>
                <p class="mt-1 text-xs text-gray-500">
                    Отображается в админке и материалах (например, как автор).
                </p>
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                <input type="email"
                       name="email" id="email"
                       placeholder="admin@example.com"
                       value="{{ old('email') }}"
                       autocomplete="email"
                       inputmode="email"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-900 bg-white focus:ring focus:border-blue-500"
                       required>
                <div class="mt-1 flex items-start gap-2">
                    <i class="fas fa-info-circle text-gray-400 mt-0.5"></i>
                    <p class="text-xs text-gray-500">
                        Используется для входа и восстановления доступа. Рекомендуем служебную почту (например, <span class="font-mono">admin@your-domain.ru</span>).
                    </p>
                </div>
            </div>

            {{-- Пароль + подсказки --}}
            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-gray-700">Пароль</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" id="password"
                           placeholder="●●●●●●"
                           autocomplete="new-password"
                           class="w-full pr-24 px-4 py-2 rounded-lg border border-gray-300 text-gray-900 bg-white focus:ring focus:border-blue-500"
                           required
                           x-on:input="
                                const v = $event.target.value || '';
                                // простая оценка сложности: длина + наборы символов
                                let score = 0;
                                if (v.length >= 8) score++;
                                if (v.length >= 12) score++;
                                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                                if (/\d/.test(v)) score++;
                                if (/[^A-Za-z0-9]/.test(v)) score++;
                                strength = Math.min(score, 5);
                           ">
                    <button type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-2 py-1 rounded border border-gray-200 bg-gray-50 hover:bg-gray-100"
                            x-on:click="showPass=!showPass">
                        <span x-show="!showPass">Показать</span>
                        <span x-show="showPass">Скрыть</span>
                    </button>
                </div>

                {{-- Индикатор сложности --}}
                <div class="mt-2">
                    <div class="h-1.5 rounded bg-gray-100 overflow-hidden">
                        <div class="h-full transition-all"
                             :class="[
                                strength<=1 ? 'bg-red-500' : (strength==2 ? 'bg-orange-500' : (strength==3 ? 'bg-yellow-500' : (strength==4 ? 'bg-green-500' : 'bg-emerald-600')))
                             ]"
                             :style="`width:${(strength/5)*100}%`"></div>
                    </div>
                    <div class="mt-1 flex items-center justify-between">
                        <p class="text-xs text-gray-500">Надёжность пароля</p>
                        <button type="button"
                                class="text-xs text-blue-600 hover:underline"
                                x-on:click="tipsOpen=!tipsOpen">
                            Подсказки
                        </button>
                    </div>
                    <ul x-show="tipsOpen" x-cloak class="mt-2 text-xs text-gray-500 space-y-1 list-disc pl-5">
                        <li>Минимум 12 символов.</li>
                        <li>Смешивайте строчные/прописные буквы, цифры и символы.</li>
                        <li>Избегайте слов из словаря и личных данных.</li>
                        <li>Сгенерируйте уникальный пароль менеджером паролей.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Доп.инфо/помощь --}}
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
            <div class="flex items-start gap-2">
                <i class="fas fa-life-ring mt-0.5 text-blue-500"></i>
                <p>
                    Если установка падает на этом шаге (миграции/сессии), вернитесь и проверьте доступ к БД и права на запись папки
                    <span class="font-mono">storage/</span>. Вы также можете снова пройти
                    <a href="{{ route('install.requirements') }}" class="text-blue-600 hover:underline">проверку требований</a>.
                </p>
            </div>
        </div>

        {{-- Кнопка --}}
        <div class="pt-1 text-center">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition"
                    :disabled="submitting">
                <span class="inline-block w-4" x-show="submitting" x-cloak>
                    <svg viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                    </svg>
                </span>
                <i class="fas fa-check-circle"></i>
                <span x-text="submitting ? 'Создаём…' : 'Завершить установку'"></span>
            </button>
        </div>
    </form>
</div>

@endsection
