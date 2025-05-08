<footer class="bg-white border-t mt-10 shadow-inner text-sm text-gray-600">
    <div class="max-w-screen-xl mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-4 gap-6">

        {{-- 🛍️ Инфо / бренд --}}
        <div>
            <div class="text-lg font-bold text-gray-800 mb-1">
                🛍️ RuShop CMS
            </div>
            <p class="text-xs text-gray-400 mb-2">Разработчик — Булавацкий Д.О.</p>
            <p class="text-xs text-gray-500">© {{ date('Y') }} Все права защищены.</p>
        </div>

        {{-- 🌐 Меню --}}
        <div>
            <div class="font-semibold text-gray-700 mb-2">Навигация</div>
            <ul class="space-y-1">
                <li><a href="{{ url('/') }}" class="hover:underline">Главная</a></li>
                <li><a href="{{ url('/about') }}" class="hover:underline">О нас</a></li>
                <li><a href="{{ url('/contact') }}" class="hover:underline">Контакты</a></li>
                <li><a href="{{ url('/privacy') }}" class="hover:underline">Политика конфиденциальности</a></li>
                <li><a href="{{ url('/terms') }}" class="hover:underline">Условия использования</a></li>
            </ul>
        </div>

        {{-- ✉️ Подписка --}}
        <div>
            <div class="font-semibold text-gray-700 mb-2">Подписка на новости</div>
            <form method="POST" action="#" class="flex flex-col gap-2">
                @csrf
                <input type="email" name="email" placeholder="Ваш email"
                    class="px-3 py-2 rounded border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                <button type="submit"
                    class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 transition">
                    Подписаться
                </button>
            </form>
        </div>

        {{-- 🤝 Соцсети + язык --}}
        <div>
            <div class="font-semibold text-gray-700 mb-2">Мы в соцсетях</div>
            <div class="flex flex-wrap gap-3 text-lg mb-4">
                <a href="https://t.me/username" title="Telegram" target="_blank" rel="noopener"
                   class="hover:text-blue-500"><i class="fab fa-telegram-plane"></i></a>
                <a href="https://wa.me/79991234567" title="WhatsApp" target="_blank" rel="noopener"
                   class="hover:text-green-500"><i class="fab fa-whatsapp"></i></a>
                <a href="https://vk.com/username" title="VK" target="_blank" rel="noopener"
                   class="hover:text-blue-700"><i class="fab fa-vk"></i></a>
                <a href="https://github.com/username" title="GitHub" target="_blank" rel="noopener"
                   class="hover:text-gray-800"><i class="fab fa-github"></i></a>
                <a href="https://youtube.com/@username" title="YouTube" target="_blank" rel="noopener"
                   class="hover:text-red-600"><i class="fab fa-youtube"></i></a>
                <a href="mailto:info@example.com" title="Email" class="hover:text-indigo-600">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>

            {{-- Язык --}}
            <form method="POST" action="#" class="text-xs">
                @csrf
                <label for="lang" class="block mb-1 font-medium text-gray-700">Язык сайта</label>
                <select name="locale" id="lang" class="border rounded px-2 py-1 text-sm">
                    <option value="ru" selected>🇷🇺 Русский</option>
                    <option value="en">🇬🇧 English</option>
                </select>
            </form>
        </div>
    </div>
</footer>
