@props([
    'name',
    'label',
    'value' => '',
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'icon' => null, // пример: 'fas fa-user'
])

<div>
    {{-- 🏷️ Метка --}}
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">
        @if($icon)
            <i class="{{ $icon }} mr-1 text-gray-400"></i>
        @endif
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>

    {{-- 🧾 Поле ввода --}}
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' =>
                'w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm ' .
                'bg-white dark:bg-gray-800 text-gray-900 dark:text-white ' .
                'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition'
        ]) }}
    >

    {{-- 💬 Подсказка --}}
    @if($hint)
        <p class="text-xs text-gray-400 mt-1">{{ $hint }}</p>
    @endif
</div>
