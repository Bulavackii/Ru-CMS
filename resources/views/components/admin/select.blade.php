@props(['label', 'name', 'options' => [], 'selected' => null])

<div>
    <label for="{{ $name }}" class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
        {{ $label }}
    </label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-100 shadow-sm">
        @foreach ($options as $key => $val)
            <option value="{{ $key }}" @selected(old($name, $selected) == $key)>{{ $val }}</option>
        @endforeach
    </select>
</div>
