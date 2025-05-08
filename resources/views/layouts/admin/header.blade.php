<nav class="bg-gray-800 text-white shadow-md">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">

        {{-- 🌐 Слева: Название или логотип --}}
        <div class="flex items-center gap-6">
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center font-semibold hover:text-blue-400 transition">
                <i class="fas fa-globe mr-2"></i> На сайт
            </a>
        </div>

        {{-- ⚙️ Справа: Служебные ссылки --}}
        <div class="flex items-center gap-6 text-sm">

            {{-- 🐞 Сообщить об ошибке (форма, e-mail или внешний сервис) --}}
            <a href="mailto:support@example.com?subject=Ошибка на сайте&body=Опишите проблему..."
               class="flex items-center hover:text-red-400 transition" title="Сообщить об ошибке">
                <i class="fas fa-bug mr-2"></i> Ошибка
            </a>

            {{-- 🌍 Геолокация (можно вести на страницу IP-лога или карту) --}}
            <a href="{{ url('/admin/geolocation') }}"
               class="flex items-center hover:text-blue-300 transition" title="Геолокация пользователей">
                <i class="fas fa-map-marker-alt mr-2"></i> Геолокация
            </a>

            {{-- 🧠 Тех. информация о сервере --}}
            <a href="{{ url('/admin/system-info') }}"
               class="flex items-center hover:text-green-400 transition" title="Информация о сервере">
                <i class="fas fa-server mr-2"></i> Система
            </a>
        </div>
    </div>
</nav>
