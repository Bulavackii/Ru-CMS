@props(['label', 'name', 'value' => '', 'type' => 'text', 'required' => false, 'hint' => null])

<div>
    <label for="{{ $name }}" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
        {{ $label }} @if($required)*@endif
    </label>
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
           value="{{ old($name, $value) }}"
           @if($required) required @endif
           class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100 shadow-sm">
    @if($hint)
        <p class="text-xs text-gray-400 mt-1">{{ $hint }}</p>
    @endif
</div>
