<div x-data="{ open: false }" x-id="['preview-modal']" class="relative">
    {{-- 🔘 Кнопка запуска --}}
    <button @click="open = true" type="button"
        class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-black dark:text-gray-400 dark:hover:text-white transition">
        <i class="fas fa-eye"></i> Предпросмотр
    </button>

    {{-- 🪟 Модальное окно --}}
    <div
        x-show="open"
        x-transition
        x-trap="open"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        aria-labelledby="preview-modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div @click.outside="open = false"
             class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full p-6"
             :aria-labelledby="$id('preview-modal')">

            {{-- 🔺 Шапка --}}
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white" :id="$id('preview-modal')">
                    {{ $title ?? 'Предпросмотр' }}
                </h2>
                <button @click="open = false"
                        class="text-gray-500 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 rounded">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- 📄 Контент --}}
            <div class="text-sm text-gray-700 dark:text-gray-200">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
