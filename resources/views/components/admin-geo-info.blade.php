@props([
    'icon' => 'fas fa-info-circle',  // Иконка FontAwesome
    'label' => '',                   // Название параметра
    'value' => null                  // Значение параметра
])

<div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm h-full">
    {{-- 🔖 Заголовок --}}
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center gap-1">
        <i class="{{ $icon }} text-sm w-4 text-gray-400 dark:text-gray-500"></i>
        {{ $label }}
    </p>

    {{-- 📄 Значение --}}
    <p class="text-base text-gray-800 dark:text-white font-medium break-words">
        {{ $value !== null && $value !== '' ? $value : '—' }}
    </p>
</div>
