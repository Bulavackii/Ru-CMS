@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-xl">
    <form method="POST" action="{{ route('install.admin') }}"
          class="rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] p-6 sm:p-10 space-y-6"
          x-data="{showPass:false, submitting:false, strength:0, tipsOpen:false}"
          x-on:submit="submitting=true">
        @csrf

        @include('Install::partials.steps', ['current' => 'admin'])

        {{-- Ошибки --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-2xl p-4">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @if (config('app.debug') && $errors->has('artisan'))
                    <pre class="mt-2 text-[11px] whitespace-pre-wrap bg-red-100/60 rounded-lg p-2 max-h-40 overflow-auto">{{ $errors->first('artisan') }}</pre>
                @endif
            </div>
        @endif

        {{-- Заголовок --}}
        <div class="text-center space-y-2">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 grid place-items-center">
                <i data-lucide="user-round" class="w-6 h-6"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Создание администратора</h2>
            <p class="text-gray-500 text-sm">
                Этот аккаунт получит полный доступ к панели управления. На этом шаге также накатятся миграции всех модулей.
            </p>
        </div>

        {{-- Поля --}}
        <div class="space-y-4">
            <div>
                <label for="name" class="block mb-1 text-sm font-medium text-gray-700">Имя</label>
                <input type="text"
                       name="name" id="name"
                       placeholder="Админ"
                       value="{{ old('name', 'Админ') }}"
                       autocomplete="name"
                       class="w-full px-4 py-2 rounded-xl border border-gray-300 text-gray-900 bg-white focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                       required autofocus>
            </div>

            <div>
                <label for="email" class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                <input type="email"
                       name="email" id="email"
                       placeholder="admin@example.com"
                       value="{{ old('email') }}"
                       autocomplete="email"
                       inputmode="email"
                       class="w-full px-4 py-2 rounded-xl border border-gray-300 text-gray-900 bg-white focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Используется для входа и восстановления доступа.
                </p>
            </div>

            {{-- Пароль + подсказки --}}
            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-gray-700">Пароль</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" id="password"
                           placeholder="●●●●●●"
                           autocomplete="new-password"
                           class="w-full pr-24 px-4 py-2 rounded-xl border border-gray-300 text-gray-900 bg-white focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                           required
                           x-on:input="
                                const v = $event.target.value || '';
                                let score = 0;
                                if (v.length >= 8) score++;
                                if (v.length >= 12) score++;
                                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                                if (/\d/.test(v)) score++;
                                if (/[^A-Za-z0-9]/.test(v)) score++;
                                strength = Math.min(score, 5);
                           ">
                    <button type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-2 py-1 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100"
                            x-on:click="showPass=!showPass">
                        <span x-show="!showPass">Показать</span>
                        <span x-show="showPass">Скрыть</span>
                    </button>
                </div>

                <div class="mt-2">
                    <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full transition-all rounded-full"
                             :class="[
                                strength<=1 ? 'bg-red-500' : (strength==2 ? 'bg-orange-500' : (strength==3 ? 'bg-yellow-500' : (strength==4 ? 'bg-green-500' : 'bg-emerald-600')))
                             ]"
                             :style="`width:${(strength/5)*100}%`"></div>
                    </div>
                    <div class="mt-1 flex items-center justify-between">
                        <p class="text-xs text-gray-500">Надёжность пароля</p>
                        <button type="button" class="text-xs text-blue-600 hover:underline" x-on:click="tipsOpen=!tipsOpen">
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
        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
            <div class="flex items-start gap-2">
                <i data-lucide="life-buoy" class="w-4 h-4 mt-0.5 text-blue-500 shrink-0"></i>
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
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/30 transition-colors"
                    :disabled="submitting">
                <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                </svg>
                <i data-lucide="check-circle-2" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? 'Создаём и накатываем миграции…' : 'Продолжить'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
