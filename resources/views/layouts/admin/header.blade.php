<nav class="bg-gray-800 text-white shadow-md">
    <div class="max-w-screen-xl mx-auto px-4 py-3 flex flex-col md:flex-row justify-between items-center gap-4 md:gap-0">

        {{-- 🌐 Слева: Название или логотип --}}
        <div class="flex items-center gap-4">
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center font-semibold text-white hover:text-blue-400 transition">
                <i class="fas fa-globe mr-2"></i> На сайт
            </a>
        </div>

        {{-- ⚙️ Справа: Служебные ссылки --}}
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">

            {{-- 🐞 Сообщить об ошибке --}}
            <a href="{{ route('admin.error.report') }}"
               class="flex items-center hover:text-red-400 transition" title="Сообщить об ошибке">
                <i class="fas fa-bug mr-2"></i> Ошибка
            </a>

            {{-- 🌍 Геолокация --}}
            <a href="{{ route('admin.geolocation') }}"
               class="flex items-center hover:text-blue-300 transition" title="Геолокация пользователей">
                <i class="fas fa-map-marker-alt mr-2"></i> Геолокация
            </a>

            {{-- 🧠 Тех. информация --}}
            <a href="{{ route('admin.system_info') }}"
               class="flex items-center hover:text-green-400 transition" title="Информация о сервере">
                <i class="fas fa-server mr-2"></i> Система
            </a>

        </div>
    </div>
</nav>
