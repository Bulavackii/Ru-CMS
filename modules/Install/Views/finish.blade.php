@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-xl text-center space-y-6 border border-gray-200 animate-fade-in">
        <div class="text-green-500 text-5xl">
            <i class="fas fa-check-circle"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-900">Установка завершена!</h2>

        <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
            Поздравляем! Ru CMS была успешно установлена.
            <br>Теперь вы можете перейти на сайт и продолжить настройку.
        </p>

        <a href="/"
           class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition">
            <i class="fas fa-lock"></i> Перейти на сайт
        </a>
    </div>
</div>
@endsection
