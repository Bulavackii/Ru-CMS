<footer class="relative text-sm text-gray-600">
    {{-- 🖼️ Фон --}}
    <div class="absolute inset-0 z-0 opacity-10"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;">
    </div>

    {{-- 🌫️ Содержимое поверх с полупрозрачным фоном --}}
    <div class="relative z-10 bg-white/90 backdrop-blur-md border-t border-gray-200 shadow-inner">
        <div class="max-w-screen-xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

            {{-- 🛍️ Инфо --}}
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">🛍️ RuShop CMS</h3>
                <p class="text-xs text-gray-500 mb-2">Разработчик — Булавацкий Д.О.</p>
                <p class="text-xs text-gray-400">© {{ date('Y') }} Все права защищены.</p>
            </div>

            {{-- 🌐 Меню --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">Навигация</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="{{ url('/') }}" class="hover:text-blue-600 transition">Главная</a></li>
                    <li><a href="{{ url('/about') }}" class="hover:text-blue-600 transition">О нас</a></li>
                    <li><a href="{{ url('/contact') }}" class="hover:text-blue-600 transition">Контакты</a></li>
                    <li><a href="{{ url('/privacy') }}" class="hover:text-blue-600 transition">Политика конфиденциальности</a></li>
                    <li><a href="{{ url('/terms') }}" class="hover:text-blue-600 transition">Условия использования</a></li>
                </ul>
            </div>

            {{-- ✉️ Подписка --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">Подписка</h3>
                <form method="POST" action="#" class="flex flex-col gap-3">
                    @csrf
                    <input type="email" name="email" placeholder="Ваш email"
                           class="px-4 py-2 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm"
                           required>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md transition font-semibold text-sm">
                        Подписаться
                    </button>
                </form>
            </div>

            {{-- 🤝 Соцсети + Язык --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">Мы в соцсетях</h3>
                <div class="flex flex-wrap gap-3 text-xl text-gray-700 mb-4">
                    <a href="#" class="hover:text-blue-500 transition"><i class="fab fa-telegram-plane"></i></a>
                    <a href="#" class="hover:text-green-500 transition"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="hover:text-blue-700 transition"><i class="fab fa-vk"></i></a>
                    <a href="#" class="hover:text-gray-800 transition"><i class="fab fa-github"></i></a>
                    <a href="#" class="hover:text-red-600 transition"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="hover:text-indigo-600 transition"><i class="fas fa-envelope"></i></a>
                </div>

                <form method="POST" action="#" class="text-xs space-y-2">
                    @csrf
                    <label for="lang" class="block font-medium text-gray-700">Язык сайта</label>
                    <select name="locale" id="lang"
                            class="border border-gray-300 rounded px-3 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="ru" selected>🇷🇺 Русский</option>
                        <option value="en">🇬🇧 English</option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</footer>
