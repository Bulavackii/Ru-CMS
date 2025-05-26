@extends('layouts.guest')

@section('title', 'Регистрация')

@section('content')
    <div class="bg-white border border-black rounded-lg shadow-md p-8 max-w-xl mx-auto animate-fade-in">
        <h2 class="text-3xl font-bold text-center text-blue-800 mb-6">
            📝 Регистрация пользователя
        </h2>

        {{-- ⚠️ Ошибки --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-800 px-4 py-2 rounded">
                <strong>Ошибка:</strong> {{ $errors->first() }}
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
                <input id="password" type="password" name="password" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                       placeholder="Минимум 8 символов">
                <p class="text-xs text-gray-500 mt-1">Используйте надёжный пароль</p>
            </div>

            {{-- 🔁 Подтверждение пароля --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-check-circle mr-1"></i> Повторите пароль
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                       placeholder="Повторите ввод пароля">
                <p class="text-xs text-gray-500 mt-1">Убедитесь, что пароли совпадают</p>
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

    {{-- 🔽 JS: показ/скрытие полей юр.лица --}}
    <script>
        document.getElementById('is_legal')?.addEventListener('change', function () {
            document.getElementById('legal-fields').classList.toggle('hidden', !this.checked);
        });
    </script>
@endsection
