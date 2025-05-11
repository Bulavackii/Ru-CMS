@props([
    'name',
    'label',
    'value' => '',
    'required' => false,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700">
        {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
    </label>
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'mt-1 w-full border-gray-300 rounded-lg px-4 py-2 shadow-sm focus:ring-blue-500 focus:border-blue-500']) }}
    >
</div>
