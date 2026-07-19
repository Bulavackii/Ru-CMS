{{-- Переключатель темной темы --}}
<div x-data="{
        darkMode: false,
        init() {
            // Светлая тема по умолчанию — включаем тёмную только по явному выбору пользователя
            this.darkMode = localStorage.getItem('darkMode') === 'true';
            this.applyTheme();
        },
        toggle() {
            this.darkMode = !this.darkMode;
            this.applyTheme();
            localStorage.setItem('darkMode', this.darkMode);
        },
        applyTheme() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
     }"
     class="flex items-center">
    <button @click="toggle()"
            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200"
            :title="darkMode ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
            :aria-label="darkMode ? 'Светлая тема' : 'Темная тема'">
        <i class="fas transition-transform duration-300" 
           :class="darkMode ? 'fa-sun text-yellow-500 rotate-90' : 'fa-moon text-gray-600 dark:text-gray-300'"></i>
        <span class="hidden sm:inline text-sm text-gray-700 dark:text-gray-200" 
              x-text="darkMode ? 'Светлая' : 'Темная'"></span>
    </button>
</div>

