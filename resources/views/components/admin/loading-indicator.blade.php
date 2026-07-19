{{-- Индикатор загрузки --}}
@props(['size' => 'md', 'text' => null])

@php
    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div class="flex flex-col items-center justify-center gap-2 {{ $attributes->get('class') }}">
    <div class="{{ $sizeClass }} border-4 border-gray-200 dark:border-gray-700 border-t-blue-600 dark:border-t-blue-400 rounded-full animate-spin"></div>
    @if($text)
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $text }}</p>
    @endif
</div>




