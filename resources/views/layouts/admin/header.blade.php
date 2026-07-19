<nav class="bg-gray-900 dark:bg-gray-800 text-white shadow z-30 w-full transition-colors duration-200"
     style="font-family: {{ data_get(optional(\Modules\Visual\Models\Theme::where('is_default',true)->first())->tokens,'font.base','Inter, system-ui, sans-serif') }}">

    @php
        // Оставляем только получение темы для шрифта (иконки — через @themeIcon)
        $theme = \Modules\Visual\Models\Theme::where('is_default', true)->first();
    @endphp

    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 py-3 sm:py-4 flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-4">

        {{-- 🔗 Левая часть: ссылка на сайт --}}
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center text-sm font-medium hover:text-blue-400 transition"
               title="Открыть сайт в новой вкладке">
                @themeIcon('home','mr-2')
                <span class="hidden sm:inline">На сайт</span>
            </a>
        </div>

        {{-- 🔍 Глобальный поиск --}}
        <div class="flex-1 max-w-md mx-4">
            @include('components.admin.global-search')
        </div>

        {{-- ⚙️ Правая часть: действия администратора --}}
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
            <a href="{{ route('admin.error.report') }}"
               class="flex items-center hover:text-red-400 transition"
               title="Сообщить об ошибке">
                @themeIcon('bug','mr-2 text-red-300')
                <span class="hidden sm:inline">Ошибка</span>
            </a>

            <a href="{{ route('admin.geolocation') }}"
               class="flex items-center hover:text-blue-300 transition"
               title="Геолокация пользователей">
                @themeIcon('globe','mr-2 text-blue-300')
                <span class="hidden sm:inline">Геолокация</span>
            </a>

            <a href="{{ route('admin.system_info') }}"
               class="flex items-center hover:text-green-400 transition"
               title="Информация о сервере и конфигурации">
                @themeIcon('cog','mr-2 text-green-300')
                <span class="hidden sm:inline">Система</span>
            </a>
            
            {{-- Центр уведомлений --}}
            @include('components.admin.notifications-center')
            
            {{-- 🌍 Переключатель страны/языка --}}
            @if(class_exists(\Modules\Localization\Views\Components\CountrySwitcher::class))
                <div class="hidden sm:block">
                    <x-country-switcher />
                </div>
            @endif
            
            {{-- Переключатель темы --}}
            @include('components.admin.dark-mode-toggle')
        </div>
    </div>
</nav>
