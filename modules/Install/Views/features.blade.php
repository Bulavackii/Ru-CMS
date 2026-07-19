@extends('layouts.frontend-install')

@section('content')
<div class="mx-auto w-full max-w-6xl">
    <div class="rounded-3xl border border-gray-200/70 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.15)] overflow-hidden">
        {{-- Заголовок --}}
        <div class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white p-8 md:p-12 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-white/15 grid place-items-center mb-4">
                <i data-lucide="rocket" class="w-7 h-7"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold mb-3">Возможности RU CMS</h1>
            <p class="text-lg text-blue-100 max-w-2xl mx-auto">
                Современная модульная CMS для России и СНГ с расширенным функционалом
            </p>
        </div>

        <div class="p-6 md:p-10">
            {{-- Список возможностей --}}
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                @foreach($features as $feature)
                <div class="relative rounded-2xl p-6 border transition-all duration-200
                    {{ ($feature['highlight'] ?? false) ? 'border-blue-300 bg-blue-50/60 shadow-sm' : 'border-gray-200 bg-white hover:border-blue-200 hover:shadow-md' }}">
                    @if($feature['highlight'] ?? false)
                        <span class="absolute top-3 right-3 w-5 h-5 rounded-full bg-blue-600 text-white grid place-items-center">
                            <i data-lucide="star" class="w-3 h-3"></i>
                        </span>
                    @endif
                    <div class="w-11 h-11 rounded-xl bg-blue-600/10 text-blue-600 grid place-items-center mb-4">
                        <i data-lucide="{{ $feature['icon'] }}" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $feature['description'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Дополнительная информация --}}
            <div class="bg-blue-50/60 rounded-2xl p-6 border border-blue-100">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-blue-600/10 text-blue-600 grid place-items-center">
                        <i data-lucide="lightbulb" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Что дальше?</h3>
                        <p class="text-sm text-gray-700 mb-3">
                            После установки вы сможете активировать нужные модули, настроить темы и начать создавать контент.
                            Все модули работают независимо и могут быть активированы или отключены в любое время.
                        </p>
                        <ul class="text-sm text-gray-600 space-y-1.5">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-blue-600"></i> Установка займёт всего несколько минут</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-blue-600"></i> Все модули готовы к использованию</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-blue-600"></i> Подробная документация доступна в админ-панели</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Кнопки навигации --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-8 border-t border-gray-200">
                <a href="{{ route('install.requirements') }}"
                   class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-semibold transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Назад к требованиям</span>
                </a>
                <a href="{{ route('install.database') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl transition-colors shadow-lg shadow-blue-500/30 font-semibold">
                    <span>Продолжить установку</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
