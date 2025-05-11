@props(['icon', 'title'])

<div class="bg-white rounded-xl shadow p-5 border border-gray-200">
    <h2 class="text-sm font-semibold text-gray-600 mb-2 flex items-center gap-2">
        <i class="{{ $icon }}"></i> {{ $title }}
    </h2>
    <div class="text-lg text-gray-900 font-mono select-all">
        {{ $slot }}
    </div>
</div>
