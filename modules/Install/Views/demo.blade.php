@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-xl">
    <div class="rounded-3xl border border-gray-200/70 bg-white/80 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] p-6 sm:p-10 space-y-6">

        @include('Install::partials.steps', ['current' => 'demo'])

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-2xl p-4">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="text-center space-y-2">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 grid place-items-center">
                <i data-lucide="package" class="w-6 h-6"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Демо-данные</h1>
            <p class="text-gray-500 text-sm">Установить стартовые данные для быстрого начала работы?</p>
        </div>

        <form method="POST" action="{{ route('install.demo') }}" class="space-y-6">
            @csrf

            <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-5">
                <h3 class="font-semibold text-sm text-gray-900 mb-3">Что будет установлено:</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-blue-600"></i> Примерные категории (Новости, Товары, Услуги)</li>
                    <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-blue-600"></i> Несколько демо-новостей</li>
                    <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-blue-600"></i> Главное меню с пунктами</li>
                    <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-blue-600"></i> Базовая структура контента</li>
                </ul>
            </div>

            <label class="flex items-start gap-3 cursor-pointer rounded-2xl border border-gray-200 p-4 hover:border-blue-200 transition-colors">
                <input type="checkbox" name="install_demo" value="1" {{ $installDemo ? 'checked' : '' }}
                       class="mt-0.5 w-5 h-5 text-blue-600 rounded focus:ring-blue-500/40">
                <div>
                    <span class="text-sm font-medium text-gray-800">Да, установить демо-данные</span>
                    <p class="text-xs text-gray-500 mt-1">Вы всегда сможете удалить демо-данные позже из админ-панели.</p>
                </div>
            </label>

            <div class="flex justify-between items-center pt-1">
                <a href="{{ route('install.license') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/30 transition-colors">
                    <span>Завершить установку</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
