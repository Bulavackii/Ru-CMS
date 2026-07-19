@extends('layouts.guest')

@section('title', 'Регистрация')

@section('content')
    <div class="bg-white border border-black rounded-lg shadow-md p-8 max-w-xl mx-auto animate-fade-in">
        <h2 class="text-3xl font-bold text-center text-blue-800 mb-6">
            📝 Регистрация пользователя
        </h2>

        {{-- ✅ Сообщение об успехе --}}
        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- ⚠️ Ошибки валидации --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Обнаружены ошибки:</strong>
                </div>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-6" id="registration-form">
            @csrf

            {{-- 👤 Имя --}}
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-user mr-1"></i> Имя
                </label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                       placeholder="Иван Иванов">
                <p class="text-xs text-gray-500 mt-1">Введите ваше полное имя</p>
            </div>

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-envelope mr-1"></i> E-mail
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                       placeholder="you@example.com">
                <p class="text-xs text-gray-500 mt-1">На этот адрес придёт письмо с подтверждением</p>
            </div>

            {{-- 🔒 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-lock mr-1"></i> Пароль
                </label>
                <div class="relative">
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="new-password"
                           oninput="updatePasswordStrength(this.value)"
                           class="w-full border border-black rounded px-4 py-2 pr-10 focus:outline-none focus:ring focus:ring-blue-200 @error('password') border-red-500 @enderror"
                           placeholder="Минимум 8 символов">
                    <button type="button"
                            onclick="togglePassword('password')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i id="password-toggle-icon" class="fas fa-eye"></i>
                    </button>
                </div>

                {{-- Индикатор сложности пароля --}}
                <div id="password-strength-indicator" class="mt-2 hidden">
                    <div class="flex gap-1 mb-1">
                        <div id="strength-bar-1" class="h-1 flex-1 rounded bg-gray-200"></div>
                        <div id="strength-bar-2" class="h-1 flex-1 rounded bg-gray-200"></div>
                        <div id="strength-bar-3" class="h-1 flex-1 rounded bg-gray-200"></div>
                        <div id="strength-bar-4" class="h-1 flex-1 rounded bg-gray-200"></div>
                    </div>
                    <p id="strength-text" class="text-xs"></p>
                </div>

                <p class="text-xs text-gray-500 mt-1">
                    Пароль должен содержать: минимум 8 символов, буквы в верхнем и нижнем регистре, цифры и специальные символы
                </p>
                @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔁 Подтверждение пароля --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-check-circle mr-1"></i> Повторите пароль
                </label>
                <div class="relative">
                    <input id="password_confirmation"
                           type="password"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           oninput="checkPasswordMatch()"
                           class="w-full border border-black rounded px-4 py-2 pr-10 focus:outline-none focus:ring focus:ring-blue-200 @error('password_confirmation') border-red-500 @enderror"
                           placeholder="Повторите ввод пароля">
                    <button type="button"
                            onclick="togglePassword('password_confirmation')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i id="password_confirmation-toggle-icon" class="fas fa-eye"></i>
                    </button>
                </div>
                <p id="password-match-message" class="text-xs mt-1"></p>
                @error('password_confirmation')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🧾 Чекбокс Юр. лицо --}}
            <div class="flex items-center">
                <input type="checkbox" id="is_legal" name="is_legal" class="mr-2 border-black focus:ring-blue-300">
                <label for="is_legal" class="text-sm font-medium text-gray-700">
                    Зарегистрироваться как юридическое лицо
                </label>
            </div>

            {{-- 🏢 Форма Юр. лица --}}
            <div id="legal-fields" class="hidden space-y-4 mt-4">
                <div>
                    <label for="org_name" class="block text-sm font-medium text-gray-700">🏢 Наименование организации</label>
                    <input id="org_name" type="text" name="org_name"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                           placeholder="ООО «Ромашка»">
                </div>
                <div>
                    <label for="ogrn" class="block text-sm font-medium text-gray-700">🧾 ОГРН</label>
                    <input id="ogrn" type="text" name="ogrn"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                           placeholder="1234567890123">
                </div>
                <div>
                    <label for="inn" class="block text-sm font-medium text-gray-700">🔢 ИНН</label>
                    <input id="inn" type="text" name="inn"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                           placeholder="1234567890">
                </div>
                <div>
                    <label for="kpp" class="block text-sm font-medium text-gray-700">🧮 КПП</label>
                    <input id="kpp" type="text" name="kpp"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                           placeholder="123456789">
                </div>
            </div>

            {{-- 🔒 Каптча --}}
            @if(config('captcha.enabled', true) && class_exists(\Modules\Captcha\Services\CaptchaService::class))
                <div class="captcha-wrapper">
                    @php
                        $captchaService = app('captcha');
                        $captchaType = config('captcha.default_type', 'image');
                        $captchaHtml = $captchaService->render($captchaType);
                    @endphp
                    {!! $captchaHtml !!}
                    @error('captcha')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Введите код с изображения для защиты от автоматических регистраций.</p>
                </div>
            @endif

            {{-- 📜 Согласие с условиями --}}
            <div class="flex items-start">
                <input type="checkbox" id="terms_agree" name="terms_agree" required
                       class="mt-1 mr-2 border-black focus:ring-blue-300">
                <label for="terms_agree" class="text-sm text-gray-700">
                    Я соглашаюсь с <a href="{{ url('/terms') }}" class="text-blue-600 hover:underline font-medium" target="_blank">
                        пользовательским соглашением
                    </a>
                    и принимаю условия использования сайта.
                </label>
            </div>

            {{-- ✅ Кнопка --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 rounded shadow-md hover:shadow-lg transition-transform transform hover:scale-105">
                    <i class="fas fa-user-plus mr-1"></i> Зарегистрироваться
                </button>
            </div>
        </form>

        {{-- 🔗 Ссылка на вход --}}
        <div class="mt-6 text-sm text-center text-gray-600">
            Уже есть аккаунт?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-semibold">Войти</a>
        </div>
    </div>

    {{-- 🔽 JS: показ/скрытие полей юр.лица, проверка пароля --}}
    <script>
        // Показ/скрытие полей юридического лица
        document.getElementById('is_legal')?.addEventListener('change', function () {
            const legalFields = document.getElementById('legal-fields');
            if (legalFields) {
                legalFields.classList.toggle('hidden', !this.checked);
                // Делаем поля обязательными/необязательными
                const requiredFields = legalFields.querySelectorAll('input[type="text"]');
                requiredFields.forEach(field => {
                    field.required = this.checked;
                });
            }
        });

        // Показ/скрытие пароля
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-toggle-icon');
            if (field && icon) {
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }

        // Проверка сложности пароля
        function updatePasswordStrength(password) {
            const indicator = document.getElementById('password-strength-indicator');
            const strengthText = document.getElementById('strength-text');

            if (!password) {
                indicator.classList.add('hidden');
                return;
            }

            indicator.classList.remove('hidden');

            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            strength = Math.min(strength, 4);

            // Обновляем индикаторы
            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('strength-bar-' + i);
                if (bar) {
                    if (i <= strength) {
                        if (strength <= 1) bar.className = 'h-1 flex-1 rounded bg-red-500';
                        else if (strength === 2) bar.className = 'h-1 flex-1 rounded bg-orange-500';
                        else if (strength === 3) bar.className = 'h-1 flex-1 rounded bg-yellow-500';
                        else bar.className = 'h-1 flex-1 rounded bg-green-500';
                    } else {
                        bar.className = 'h-1 flex-1 rounded bg-gray-200';
                    }
                }
            }

            // Обновляем текст
            const texts = {
                0: '',
                1: 'Слабый пароль',
                2: 'Средний пароль',
                3: 'Хороший пароль',
                4: 'Надёжный пароль'
            };

            if (strengthText) {
                strengthText.textContent = texts[strength] || '';
                strengthText.className = 'text-xs ' + (
                    strength <= 1 ? 'text-red-600' :
                    strength === 2 ? 'text-orange-600' :
                    strength === 3 ? 'text-yellow-600' :
                    'text-green-600'
                );
            }
        }

        // Проверка совпадения паролей
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            const messageEl = document.getElementById('password-match-message');

            if (!passwordConfirmation) {
                messageEl.textContent = '';
                return;
            }

            if (password === passwordConfirmation) {
                messageEl.textContent = '✓ Пароли совпадают';
                messageEl.className = 'text-xs text-green-600 mt-1';
            } else {
                messageEl.textContent = '✗ Пароли не совпадают';
                messageEl.className = 'text-xs text-red-600 mt-1';
            }
        }

    </script>
@endsection
