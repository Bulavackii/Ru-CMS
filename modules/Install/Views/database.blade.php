@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <form method="POST" action="{{ route('install.database') }}"
          class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-xl space-y-6 border border-gray-200">

        {{-- 🛡 CSRF --}}
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        {{-- 🧩 Заголовок --}}
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center justify-center gap-2">
                <i class="fas fa-database text-blue-600"></i> Настройка базы данных
            </h2>
            <p class="text-gray-600 text-sm sm:text-base">Введите параметры подключения к базе MySQL</p>
        </div>

        {{-- 📋 Поля --}}
        <div class="space-y-4">
            @foreach (['host' => 'Хост', 'port' => 'Порт', 'database' => 'База данных', 'username' => 'Пользователь', 'password' => 'Пароль'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block mb-1 text-sm font-medium text-gray-700">{{ $label }}</label>
                    <input type="{{ $field === 'password' ? 'password' : 'text' }}" name="{{ $field }}" id="{{ $field }}"
                           value="{{ old($field, $field === 'host' ? '127.0.0.1' : ($field === 'port' ? '3306' : '')) }}"
                           placeholder="{{ $field === 'password' ? '●●●●●●' : '' }}"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 focus:ring focus:border-blue-500"
                           {{ $field !== 'password' ? 'required' : '' }}>
                </div>
            @endforeach
        </div>

        {{-- ✅ Кнопка --}}
        <div class="text-center pt-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition">
                <i class="fas fa-arrow-right"></i> Продолжить
            </button>
        </div>
    </form>
</div>
@endsection
