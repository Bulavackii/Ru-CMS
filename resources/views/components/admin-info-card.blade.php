@props(['icon', 'title'])

<div class="bg-white dark:bg-gray-900 rounded-xl shadow p-5 border border-gray-200 dark:border-gray-700">
    {{-- 🔖 Заголовок блока --}}
    <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
        <i class="{{ $icon }} text-base text-gray-400 dark:text-gray-500"></i>
        {{ $title }}
    </h2>

    {{-- 📄 Содержимое --}}
    <div class="text-lg text-gray-900 dark:text-white font-mono break-words select-all">
        {{ $slot }}
    </div>
</div>
