@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.demo') }}"
          class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden">
        @csrf

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'demo'])
            <div class="text-center">
                <div class="mx-auto w-10 h-10 rounded-xl bg-gray-900 text-white grid place-items-center mb-2">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">Демо-данные</h1>
                <p class="text-gray-500 text-xs">Установить стартовый контент для быстрого знакомства с системой?</p>
            </div>
        </div>

        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if ($errors->any())
                <div class="bg-gray-900 text-white text-xs rounded-2xl p-3">
                    <div class="flex items-center gap-1.5 font-semibold mb-1"><i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i> Не получилось</div>
                    <ul class="list-disc pl-5 space-y-0.5 text-gray-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Что входит: компактная сетка 2x2 --}}
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3">
                <h3 class="font-semibold text-xs text-gray-900 mb-2 flex items-center gap-1.5">
                    <i data-lucide="package-open" class="w-3.5 h-3.5"></i> Что будет установлено
                </h3>
                <div class="grid grid-cols-2 gap-1.5 text-[11px] text-gray-600">
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" title="Новости, Товары, Услуги">
                        <i data-lucide="folder-tree" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> Категории
                    </div>
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" title="Несколько примеров публикаций">
                        <i data-lucide="newspaper" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> Демо-новости
                    </div>
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" title="Готовое главное меню с пунктами">
                        <i data-lucide="menu" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> Главное меню
                    </div>
                    <div class="flex items-center gap-1.5 bg-white rounded-lg border border-gray-200 px-2 py-1.5" title="Базовая структура контента для старта">
                        <i data-lucide="layout-template" class="w-3.5 h-3.5 text-gray-700 shrink-0"></i> Структура
                    </div>
                </div>
            </div>

            <label class="flex items-start gap-2.5 cursor-pointer rounded-2xl border-2 border-gray-200 p-3 hover:border-gray-400 transition-colors">
                <input type="checkbox" name="install_demo" value="1" {{ $installDemo ? 'checked' : '' }}
                       class="mt-0.5 w-4 h-4 text-gray-900 rounded border-gray-300 focus:ring-gray-900/20">
                <div>
                    <span class="text-sm font-medium text-gray-900">Да, установить демо-данные</span>
                    <p class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                        Демо-данные всегда можно удалить позже из админ-панели.
                    </p>
                </div>
            </label>

            <div class="rounded-xl bg-gray-50 border border-gray-200 px-3 py-2 text-[11px] text-gray-500 flex items-start gap-1.5">
                <i data-lucide="lightbulb" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-600"></i>
                <span>Начинаете «с чистого листа»? Просто оставьте галочку снятой — установится только базовая структура без контента.</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3 flex items-center justify-between">
            <a href="{{ route('install.license') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-gray-900/25 transition-colors">
                <i data-lucide="flag" class="w-4 h-4"></i>
                <span>Завершить установку</span>
            </button>
        </div>
    </form>
</div>
@endsection
