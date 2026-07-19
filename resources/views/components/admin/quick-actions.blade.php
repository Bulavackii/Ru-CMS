{{-- Плавающая кнопка быстрых действий --}}
<div x-data="quickActions()" class="fixed bottom-6 right-6 z-40">
    {{-- Главная кнопка --}}
    <button @click="open = !open"
            class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-transform transform hover:scale-110"
            :class="{'rotate-45': open}">
        <i class="fas" :class="open ? 'fa-times' : 'fa-plus'"></i>
    </button>

    {{-- Меню действий --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="open = false"
         class="absolute bottom-20 right-0 space-y-2"
         style="display: none;">
        
        @php
            $actions = [
                ['title' => 'Новость', 'icon' => 'newspaper', 'route' => 'admin.news.create', 'color' => 'blue'],
                ['title' => 'Страница', 'icon' => 'file', 'route' => 'admin.pages.create', 'color' => 'green'],
                ['title' => 'Пользователь', 'icon' => 'user-plus', 'route' => 'admin.users.create', 'color' => 'purple'],
                ['title' => 'Категория', 'icon' => 'folder', 'route' => 'admin.categories.create', 'color' => 'orange'],
                ['title' => 'Слайдшоу', 'icon' => 'image', 'route' => 'admin.slideshow.create', 'color' => 'pink'],
                ['title' => 'Файл', 'icon' => 'upload', 'route' => 'admin.files.index', 'color' => 'indigo'],
            ];
        @endphp

        @foreach($actions as $action)
            @php
                $colorMap = [
                    'blue' => ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-600 dark:text-blue-400'],
                    'green' => ['bg' => 'bg-green-100 dark:bg-green-900', 'text' => 'text-green-600 dark:text-green-400'],
                    'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900', 'text' => 'text-purple-600 dark:text-purple-400'],
                    'orange' => ['bg' => 'bg-orange-100 dark:bg-orange-900', 'text' => 'text-orange-600 dark:text-orange-400'],
                    'pink' => ['bg' => 'bg-pink-100 dark:bg-pink-900', 'text' => 'text-pink-600 dark:text-pink-400'],
                    'indigo' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900', 'text' => 'text-indigo-600 dark:text-indigo-400'],
                ];
                $colors = $colorMap[$action['color']] ?? $colorMap['blue'];
            @endphp
            @if(Route::has($action['route']))
                <a href="{{ route($action['route']) }}"
                   class="flex items-center gap-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105 min-w-[180px]"
                   @click="open = false"
                   aria-label="{{ $action['title'] }}">
                    <div class="w-10 h-10 {{ $colors['bg'] }} rounded-lg flex items-center justify-center">
                        <i class="fas fa-{{ $action['icon'] }} {{ $colors['text'] }}"></i>
                    </div>
                    <span class="font-medium">{{ $action['title'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</div>

<script>
function quickActions() {
    return {
        open: false,
        
        init() {
            // Горячая клавиша Ctrl+N для быстрого создания
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    this.open = !this.open;
                }
            });
        }
    }
}
</script>

