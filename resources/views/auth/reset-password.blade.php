@extends('layouts.guest')

@section('title', 'Сброс пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white border border-black rounded-lg shadow-lg p-6 space-y-6 animate-fade-in">

        {{-- 🧩 Заголовок --}}
        <h2 class="text-2xl font-extrabold text-center text-blue-700">
            <i class="fas fa-unlock-alt mr-2"></i>Сброс пароля
        </h2>

        {{-- 🔴 Ошибка валидации --}}
        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded shadow-sm text-sm">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            {{-- 🔐 Скрытый токен сброса --}}
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    <i class="fas fa-envelope mr-1 text-blue-500"></i> E-mail
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email', $request->email) }}"
                       required
                       autofocus
                       class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
                <p class="text-xs text-gray-500 mt-1">Укажите e-mail, на который пришло письмо.</p>
                @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔒 Новый пароль --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    <i class="fas fa-lock mr-1 text-blue-500"></i> Новый пароль
                </label>
                <div class="relative">
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="new-password"
                           oninput="updatePasswordStrength(this.value)"
                           class="mt-1 block w-full pr-10 border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2 @error('password') border-red-500 @enderror">
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
                    Пароль должен содержать: минимум 8 символов, буквы в верхнем и нижнем регистре, цифры и специальные символы.
                </p>
                @error('password')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔁 Повтор пароля --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    <i class="fas fa-key mr-1 text-blue-500"></i> Подтвердите пароль
                </label>
                <div class="relative">
                    <input id="password_confirmation"
                           type="password"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           oninput="checkPasswordMatch()"
                           class="mt-1 block w-full pr-10 border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-4 py-2">
                    <button type="button" 
                            onclick="togglePassword('password_confirmation')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i id="password_confirmation-toggle-icon" class="fas fa-eye"></i>
                    </button>
                </div>
                <p id="password-match-message" class="text-xs mt-1"></p>
                <p class="text-xs text-gray-500 mt-1">Повторите ввод нового пароля.</p>
            </div>

            {{-- ✅ Кнопка сброса --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                    🔁 Сбросить пароль
                </button>
            </div>
        </form>
    </div>

    <script>
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
                if (indicator) indicator.classList.add('hidden');
                return;
            }
            
            if (indicator) indicator.classList.remove('hidden');
            
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
                if (messageEl) messageEl.textContent = '';
                return;
            }
            
            if (password === passwordConfirmation) {
                if (messageEl) {
                    messageEl.textContent = '✓ Пароли совпадают';
                    messageEl.className = 'text-xs text-green-600 mt-1';
                }
            } else {
                if (messageEl) {
                    messageEl.textContent = '✗ Пароли не совпадают';
                    messageEl.className = 'text-xs text-red-600 mt-1';
                }
            }
        }
    </script>
@endsection
