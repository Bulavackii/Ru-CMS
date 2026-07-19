{{-- Компонент для отображения ошибок --}}
@props(['type' => 'error', 'dismissible' => false])

@php
    $typeClasses = [
        'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
        'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
        'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
    ];
    $typeClass = $typeClasses[$type] ?? $typeClasses['error'];
    $iconMap = [
        'error' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle',
        'success' => 'check-circle',
    ];
    $icon = $iconMap[$type] ?? 'exclamation-circle';
@endphp

<div x-data="{ show: true }" 
     x-show="show"
     x-transition
     class="rounded-lg border p-4 {{ $typeClass }} {{ $attributes->get('class') }}">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <i class="fas fa-{{ $icon }}"></i>
        </div>
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button @click="show = false" 
                    class="flex-shrink-0 text-current opacity-50 hover:opacity-100 transition"
                    aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
        @endif
    </div>
</div>




