@extends('layouts.frontend-install')

@section('content')
<div class="w-full max-w-4xl max-h-full flex flex-col">
    <div class="rounded-3xl border border-gray-200 bg-white/90 backdrop-blur-xl shadow-[0_24px_60px_-24px_rgba(0,0,0,.25)] flex flex-col max-h-full overflow-hidden">

        {{-- Шапка: монохромный градиент --}}
        <div class="bg-gradient-to-br from-gray-900 to-gray-700 text-white px-6 py-5 text-center shrink-0">
            <div class="mx-auto w-10 h-10 rounded-xl bg-white/15 grid place-items-center mb-2">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <h1 class="text-xl font-bold">Возможности RU CMS</h1>
            <p class="text-sm text-gray-300">Современная модульная CMS для России и СНГ</p>
        </div>

        {{-- Сетка возможностей: скроллится внутри --}}
        <div class="p-4 sm:p-6 overflow-y-auto install-scroll min-h-0">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($features as $feature)
                <div class="relative rounded-2xl p-4 border transition-all duration-200
                    {{ ($feature['highlight'] ?? false) ? 'border-gray-900 bg-gray-50' : 'border-gray-200 bg-white hover:border-gray-400' }}"
                    title="{{ $feature['description'] }}">
                    @if($feature['highlight'] ?? false)
                        <span class="absolute top-2.5 right-2.5 w-4 h-4 rounded-full bg-gray-900 text-white grid place-items-center" title="Ключевая возможность">
                            <i data-lucide="star" class="w-2.5 h-2.5"></i>
                        </span>
                    @endif
                    <div class="w-8 h-8 rounded-lg bg-gray-900 text-white grid place-items-center mb-2">
                        <i data-lucide="{{ $feature['icon'] }}" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ $feature['title'] }}</h3>
                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-3">{{ $feature['description'] }}</p>
                </div>
                @endforeach
            </div>

            <div class="mt-3 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-2.5 flex items-center gap-2 text-xs text-gray-600">
                <i data-lucide="lightbulb" class="w-4 h-4 shrink-0 text-gray-700"></i>
                <span>После установки модули можно свободно включать и отключать в админ-панели — каждый работает независимо.</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 py-4 shrink-0 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-2">
            <a href="{{ route('install.requirements') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 font-medium transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Назад к требованиям</span>
            </a>
            <a href="{{ route('install.database') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-xl transition-colors shadow-lg shadow-gray-900/25 text-sm font-semibold">
                <span>Продолжить установку</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</div>
@endsection
