<nav class="bg-gray-800 text-white shadow-sm z-30 w-full">
    <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-col md:flex-row justify-between items-center gap-4 md:gap-0">

        {{-- 🌐 Слева: Логотип или ссылка на сайт --}}
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center font-semibold text-white hover:text-blue-400 transition-all duration-200">
                <i class="fas fa-globe mr-2"></i> <span class="hidden sm:inline">На сайт</span>
            </a>
        </div>

        {{-- ⚙️ Справа: Служебные ссылки --}}
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">

            {{-- 🐞 Сообщить об ошибке --}}
            <a href="{{ route('admin.error.report') }}"
               class="flex items-center hover:text-red-400 transition-all duration-200"
               title="Сообщить об ошибке">
                <i class="fas fa-bug mr-2 text-red-300"></i>
                <span class="hidden sm:inline">Ошибка</span>
            </a>

            {{-- 🌍 Геолокация --}}
            <a href="{{ route('admin.geolocation') }}"
               class="flex items-center hover:text-blue-300 transition-all duration-200"
               title="Геолокация пользователей">
                <i class="fas fa-map-marker-alt mr-2 text-blue-300"></i>
                <span class="hidden sm:inline">Геолокация</span>
            </a>

            {{-- 🧠 Системная информация --}}
            <a href="{{ route('admin.system_info') }}"
               class="flex items-center hover:text-green-400 transition-all duration-200"
               title="Информация о сервере">
                <i class="fas fa-server mr-2 text-green-300"></i>
                <span class="hidden sm:inline">Система</span>
            </a>
        </div>
    </div>
</nav>
