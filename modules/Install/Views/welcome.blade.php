@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gray-100">
    <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-xl space-y-8 border border-gray-200 text-center">
        <div class="flex flex-col items-center gap-3">
            <div class="w-16 h-16 rounded-full bg-blue-600 text-white text-3xl font-bold flex items-center justify-center">RU</div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Ru CMS</h1>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 flex items-center justify-center gap-2">
            <i class="fas fa-rocket text-blue-500"></i> Добро пожаловать в установку
        </h2>
        <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
            Ваша CMS почти готова. Пройдите несколько шагов, чтобы завершить установку.
        </p>
        <a href="{{ route('install.requirements') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-sm font-semibold shadow transition">
            <i class="fas fa-play"></i> Начать установку
        </a>
    </div>
</div>
@endsection
