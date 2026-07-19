{{-- Мобильное меню (drawer) --}}
<div x-data="{ open: false }" class="lg:hidden" x-cloak>
    {{-- Кнопка открытия --}}
    <button @click="open = true"
            class="fixed top-4 left-4 z-50 w-10 h-10 bg-gray-900 dark:bg-gray-800 text-white rounded-lg flex items-center justify-center shadow-lg hover:bg-gray-800 dark:hover:bg-gray-700 transition"
            aria-label="Открыть меню">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Overlay --}}
    <div x-show="open"
         @click="open = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 z-40"
         style="display: none;"></div>

    {{-- Drawer --}}
    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 shadow-2xl z-50 overflow-y-auto"
         style="display: none;">
        
        {{-- Заголовок --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 dark:text-white">Меню</h2>
            <button @click="open = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Навигация --}}
        <nav class="p-4 space-y-2" aria-label="Основная навигация">
            <a href="{{ route('admin.dashboard') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.dashboard') ? 'bg-black text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <i class="fas fa-dashboard w-5 text-center"></i>
                <span>Панель управления</span>
            </a>
            <a href="{{ route('admin.news.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.news.*') ? 'bg-black text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <i class="fas fa-file-text w-5 text-center"></i>
                <span>Новости</span>
            </a>
            <a href="{{ route('admin.pages.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.pages.*') ? 'bg-black text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <i class="fas fa-file-text w-5 text-center"></i>
                <span>Страницы</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.categories.*') ? 'bg-black text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <i class="fas fa-folder w-5 text-center"></i>
                <span>Категории</span>
            </a>
            <a href="{{ route('admin.files.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.files.*') ? 'bg-black text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <i class="fas fa-folder w-5 text-center"></i>
                <span>Файлы</span>
            </a>
        </nav>
    </div>
</div>

