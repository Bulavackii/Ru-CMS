<nav class="bg-gray-900 text-white shadow z-30 w-full">
    <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-4 sm:gap-0">

        {{-- 🔗 Левая часть: логотип RU CMS и ссылка на сайт --}}
        <div class="flex items-center gap-3">

            {{-- Ссылка на клиентский сайт --}}
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center text-sm font-medium hover:text-blue-400 transition"
               title="Открыть сайт в новой вкладке">
                <i class="fas fa-globe mr-2"></i>
                <span class="hidden sm:inline">На сайт</span>
            </a>
        </div>

        {{-- ⚙️ Правая часть: действия администратора --}}
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">

            {{-- 🐞 Ошибка --}}
            <a href="{{ route('admin.error.report') }}"
               class="flex items-center hover:text-red-400 transition"
               title="Сообщить об ошибке">
                <i class="fas fa-bug mr-2 text-red-300"></i>
                <span class="hidden sm:inline">Ошибка</span>
            </a>

            {{-- 🌍 Геолокация --}}
            <a href="{{ route('admin.geolocation') }}"
               class="flex items-center hover:text-blue-300 transition"
               title="Геолокация пользователей">
                <i class="fas fa-map-marker-alt mr-2 text-blue-300"></i>
                <span class="hidden sm:inline">Геолокация</span>
            </a>

            {{-- 🧠 Системная информация --}}
            <a href="{{ route('admin.system_info') }}"
               class="flex items-center hover:text-green-400 transition"
               title="Информация о сервере и конфигурации">
                <i class="fas fa-server mr-2 text-green-300"></i>
                <span class="hidden sm:inline">Система</span>
            </a>
        </div>
    </div>
</nav>
