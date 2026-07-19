@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.admin') }}"
          class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden"
          x-data="{showPass:false, submitting:false, strength:0, tipsOpen:false}"
          x-on:submit="submitting=true">
        @csrf

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'admin'])
            <div class="text-center">
                <div class="mx-auto w-10 h-10 rounded-xl bg-gray-900 text-white grid place-items-center mb-2">
                    <i data-lucide="user-round" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Создание администратора</h2>
                <p class="text-gray-500 text-xs flex items-center justify-center gap-1">
                    <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                    Полный доступ к панели. На этом шаге также накатываются миграции всех модулей.
                </p>
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
                    @if (config('app.debug') && $errors->has('artisan'))
                        <pre class="mt-2 text-[10px] whitespace-pre-wrap bg-black/40 rounded-lg p-2 max-h-32 overflow-auto install-scroll">{{ $errors->first('artisan') }}</pre>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="name" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="user" class="w-3 h-3 text-gray-400"></i> Имя
                    </label>
                    <input type="text"
                           name="name" id="name"
                           placeholder="Админ"
                           value="{{ old('name', 'Админ') }}"
                           autocomplete="name"
                           title="Отображаемое имя администратора"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required autofocus>
                </div>
                <div>
                    <label for="email" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="mail" class="w-3 h-3 text-gray-400"></i> Email
                    </label>
                    <input type="email"
                           name="email" id="email"
                           placeholder="admin@example.com"
                           value="{{ old('email') }}"
                           autocomplete="email"
                           inputmode="email"
                           title="Используется для входа и восстановления доступа"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required>
                </div>
            </div>

            {{-- Пароль + индикатор --}}
            <div>
                <label for="password" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                    <i data-lucide="lock" class="w-3 h-3 text-gray-400"></i> Пароль
                </label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" id="password"
                           placeholder="●●●●●●"
                           autocomplete="new-password"
                           class="w-full pr-10 px-3 py-2 rounded-xl border border-gray-300 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
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
                            class="absolute right-1.5 inset-y-0 my-auto w-7 h-7 grid place-items-center rounded-lg text-gray-400 hover:text-gray-800 hover:bg-gray-100"
                            x-on:click="showPass=!showPass"
                            :title="showPass ? 'Скрыть пароль' : 'Показать пароль'">
                        {{-- x-show на span-обёртке: Lucide заменяет <i> на <svg> и теряет Alpine-атрибуты --}}
                        <span x-show="!showPass" class="grid place-items-center"><i data-lucide="eye" class="w-4 h-4"></i></span>
                        <span x-show="showPass" x-cloak class="grid place-items-center"><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                    </button>
                </div>

                {{-- Шкала надёжности: монохром — от светло-серого к чёрному --}}
                <div class="mt-1.5">
                    <div class="h-1 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full transition-all rounded-full"
                             :class="strength<=1 ? 'bg-gray-300' : (strength==2 ? 'bg-gray-400' : (strength==3 ? 'bg-gray-500' : (strength==4 ? 'bg-gray-700' : 'bg-gray-900')))"
                             :style="`width:${(strength/5)*100}%`"></div>
                    </div>
                    <div class="mt-1 flex items-center justify-between">
                        <p class="text-[11px] text-gray-400 flex items-center gap-1">
                            <i data-lucide="shield" class="w-3 h-3"></i>
                            Надёжность: <span class="font-medium text-gray-600" x-text="['—','слабый','средний','хороший','сильный','отличный'][strength]"></span>
                        </p>
                        <button type="button" class="text-[11px] text-gray-500 hover:text-gray-900 underline decoration-dotted" x-on:click="tipsOpen=!tipsOpen">
                            Подсказки
                        </button>
                    </div>
                    <ul x-show="tipsOpen" x-cloak class="mt-1.5 text-[11px] text-gray-500 space-y-0.5 list-disc pl-5">
                        <li>Минимум 12 символов, строчные + прописные + цифры + символы.</li>
                        <li>Избегайте словарных слов и личных данных.</li>
                        <li>Лучший вариант — сгенерировать менеджером паролей.</li>
                    </ul>
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-[11px] text-gray-500 flex items-start gap-1.5">
                <i data-lucide="life-buoy" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-600"></i>
                <span>Если установка падает на миграциях — проверьте доступ к БД и права на запись <span class="font-mono">storage/</span>, затем вернитесь к <a href="{{ route('install.requirements') }}" class="underline hover:text-gray-800">проверке требований</a>.</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3 flex items-center justify-between">
            <a href="{{ route('install.database') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gray-900 hover:bg-black disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-gray-900/25 transition-colors"
                    :disabled="submitting">
                <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                </svg>
                <i data-lucide="user-plus" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? 'Создаём и накатываем миграции…' : 'Создать и продолжить'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
