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
            class="relative w-9 h-9 grid place-items-center rounded-lg border border-gray-700 hover:bg-gray-800 hover:border-gray-600 transition"
            :title="darkMode ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
            :aria-label="darkMode ? 'Светлая тема' : 'Темная тема'">
        <i class="fas transition-transform duration-300"
           :class="darkMode ? 'fa-sun text-yellow-400 rotate-90' : 'fa-moon text-gray-300'"></i>
    </button>
</div>

