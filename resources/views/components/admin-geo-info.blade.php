@props(['icon' => 'fas fa-info-circle', 'label' => '', 'value' => null])

<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm h-full">
    <p class="text-xs text-gray-500 mb-1 flex items-center gap-1">
        <i class="{{ $icon }}"></i> {{ $label }}
    </p>
    <p class="text-base text-gray-800 font-medium">
        {{ $value ?? '—' }}
    </p>
</div>
