@props([
    'label',
    'name',
    'value' => '',
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'icon' => null, // пример: 'fas fa-user'
])

<div class="mb-4">
    {{-- 🏷️ Название поля --}}
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
        @if($icon)
            <i class="{{ $icon }} mr-1 text-blue-500"></i>
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
        {{ $attributes->merge(['class' => 'w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-800 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500']) }}
    >

    {{-- 💬 Подсказка --}}
    @if ($hint)
        <p class="text-xs text-gray-400 mt-1">{{ $hint }}</p>
    @endif

    {{-- ❗ Ошибка --}}
    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
