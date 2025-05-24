@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <form method="POST" class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-xl space-y-6 border border-gray-200">
        @csrf

        <div class="text-center space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center justify-center gap-2">
                <i class="fas fa-user-shield text-blue-600"></i> Создание администратора
            </h2>
            <p class="text-gray-600 text-sm sm:text-base">Введите данные для входа в админ-панель</p>
        </div>

        <div class="space-y-4">
            <div>
                <label for="name" class="block mb-1 text-sm font-medium text-gray-700">Имя</label>
                <input type="text" name="name" id="name" placeholder="Админ" value="{{ old('name', 'Админ') }}"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-900 bg-white" required>
            </div>
            <div>
                <label for="email" class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" placeholder="admin@example.com" value="{{ old('email') }}"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-900 bg-white" required>
            </div>
            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-gray-700">Пароль</label>
                <input type="password" name="password" id="password" placeholder="●●●●●●"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-900 bg-white" required>
            </div>
        </div>

        <div class="text-center pt-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition">
                <i class="fas fa-check-circle"></i> Завершить установку
            </button>
        </div>
    </form>
</div>
@endsection
