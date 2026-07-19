@props([
    'label',
    'name',
    'options' => [],
    'selected' => null,
    'required' => false,
    'hint' => null,
])

<div>
    {{-- 🏷️ Метка поля --}}
    <label for="{{ $name }}" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
        {{ $label }} @if($required)*@endif
    </label>

    {{-- 🔽 Селект --}}
    <select name="{{ $name }}" id="{{ $name }}"
            @if($required) required @endif
            class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring focus:ring-blue-300">
        @foreach ($options as $key => $val)
            <option value="{{ $key }}" @selected(old($name, $selected) == $key)>{{ $val }}</option>
        @endforeach
    </select>

    {{-- 💬 Подсказка --}}
    @if ($hint)
        <p class="text-xs text-gray-400 mt-1">{{ $hint }}</p>
    @endif
</div>
