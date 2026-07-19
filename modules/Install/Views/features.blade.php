@extends('layouts.frontend-install')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Шаги установки --}}
        <div class="flex items-center justify-center gap-2 text-xs mb-8">
            <span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 font-medium">1. Приветствие</span>
            <span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 font-medium">2. Требования</span>
            <span class="px-3 py-1.5 rounded-full bg-blue-600 text-white font-semibold">3. Возможности</span>
            <span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 font-medium">4. База данных</span>
            <span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 font-medium">5. Готово</span>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            {{-- Заголовок --}}
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-8 md:p-12 text-center">
                <div class="text-5xl mb-4">🚀</div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Возможности RU CMS</h1>
                <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                    Современная модульная CMS для России и СНГ с расширенным функционалом
                </p>
            </div>

            {{-- Список возможностей --}}
            <div class="p-8 md:p-12">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    @foreach($features as $feature)
                    <div class="group relative border-2 rounded-xl p-6 transition-all duration-300 
                        {{ ($feature['highlight'] ?? false) ? 'border-blue-500 bg-blue-50 shadow-lg' : 'border-gray-200 bg-white hover:border-blue-300 hover:shadow-lg' }}">
                        @if($feature['highlight'] ?? false)
                        <div class="absolute top-3 right-3">
                            <span class="px-2 py-1 text-xs font-semibold bg-blue-600 text-white rounded-full">⭐</span>
                        </div>
                        @endif
                        <div class="text-5xl mb-4 transform group-hover:scale-110 transition-transform duration-300">
                            {{ $feature['icon'] }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $feature['title'] }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ $feature['description'] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Дополнительная информация --}}
                <div class="mt-8 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-6 border border-indigo-100">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 text-3xl">💡</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Что дальше?</h3>
                            <p class="text-gray-700 mb-3">
                                После установки вы сможете активировать нужные модули, настроить темы и начать создавать контент. 
                                Все модули работают независимо и могут быть активированы или отключены в любое время.
                            </p>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>✓ Установка займет всего несколько минут</li>
                                <li>✓ Все модули готовы к использованию</li>
                                <li>✓ Подробная документация доступна в админ-панели</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Кнопки навигации --}}
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-8 border-t border-gray-200">
                    <a href="{{ route('install.requirements') }}" 
                       class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-semibold transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>← Назад к требованиям</span>
                    </a>
                    <a href="{{ route('install.database') }}" 
                       class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl font-semibold transform hover:-translate-y-0.5">
                        <span>Продолжить установку</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

