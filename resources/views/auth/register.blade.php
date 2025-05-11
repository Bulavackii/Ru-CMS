@extends('layouts.guest')

@section('title', 'Регистрация')

@section('content')
    <div class="bg-white border border-black rounded-lg shadow-md p-8 max-w-xl mx-auto animate-fade-in">
        <h2 class="text-3xl font-bold text-center text-blue-800 mb-6">📝 Регистрация пользователя</h2>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-800 px-4 py-2 rounded">
                <strong>Ошибка:</strong> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-6" id="registration-form">
            @csrf

            {{-- 👤 Имя --}}
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Имя</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
            </div>

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
            </div>

            {{-- 🔒 Пароль --}}
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Пароль</label>
                <input id="password" type="password" name="password" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
            </div>

            {{-- 🔁 Подтверждение пароля --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Повторите пароль</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
            </div>

            {{-- 🧾 Чекбокс Юр. лицо --}}
            <div class="flex items-center">
                <input type="checkbox" id="is_legal" name="is_legal" class="mr-2 border-black focus:ring-blue-300">
                <label for="is_legal" class="text-sm font-medium text-gray-700">Зарегистрироваться как юридическое лицо</label>
            </div>

            {{-- 🏢 Форма Юр. лица (по умолчанию скрыта) --}}
            <div id="legal-fields" class="hidden space-y-4 mt-4">
                <div>
                    <label for="org_name" class="block text-sm font-medium text-gray-700">Наименование организации</label>
                    <input id="org_name" type="text" name="org_name"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label for="ogrn" class="block text-sm font-medium text-gray-700">ОГРН</label>
                    <input id="ogrn" type="text" name="ogrn"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label for="inn" class="block text-sm font-medium text-gray-700">ИНН</label>
                    <input id="inn" type="text" name="inn"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label for="kpp" class="block text-sm font-medium text-gray-700">КПП</label>
                    <input id="kpp" type="text" name="kpp"
                           class="w-full border border-black rounded px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                </div>
            </div>

            {{-- ✅ Кнопка регистрации --}}
            <div>
                <button type="submit"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 rounded shadow-md hover:shadow-lg transition-transform transform hover:scale-105">
                    ✅ Зарегистрироваться
                </button>
            </div>
        </form>

        {{-- 🔗 Ссылка на вход --}}
        <div class="mt-6 text-sm text-center text-gray-600">
            Уже есть аккаунт?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-semibold">Войти</a>
        </div>
    </div>

    {{-- 🔽 JS: Показ/скрытие формы юр. лица --}}
    <script>
        document.getElementById('is_legal')?.addEventListener('change', function () {
            const legalFields = document.getElementById('legal-fields');
            legalFields.classList.toggle('hidden', !this.checked);
        });
    </script>
@endsection
