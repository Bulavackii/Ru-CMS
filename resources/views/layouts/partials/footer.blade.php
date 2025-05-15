<footer class="relative text-sm text-gray-600 mt-10">
    {{-- 🖼️ Фон --}}
    <div class="absolute inset-0 z-0 opacity-10"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;">
    </div>

    {{-- 🌫️ Основной фон --}}
    <div class="relative z-10 bg-white/90 backdrop-blur-md border-t border-gray-200 shadow-inner">

        {{-- 🔝 Верхняя секция --}}
        <div class="max-w-screen-xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-8">

            {{-- 🛍️ Инфо --}}
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">🛍️ RuShop CMS</h3>
                <p class="text-xs text-gray-500 mb-2">Разработчик — Булавацкий Д.О.</p>
            </div>

            {{-- 🌐 Навигация --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-4 text-center">Навигация</h3>
                <div class="grid grid-cols-2 gap-2 text-sm text-gray-700 justify-items-center">
                    <a href="{{ url('/') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-home"></i> Главная
                    </a>
                    <a href="{{ url('/about') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-book-open"></i> О нас
                    </a>
                    <a href="{{ url('/contacts') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-phone-alt"></i> Контакты
                    </a>
                    <a href="{{ url('/faq') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-question-circle"></i> Вопросы
                    </a>
                    <a href="{{ url('/privacy') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-user-secret"></i> Политика
                    </a>
                    <a href="{{ url('/terms') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-file-contract"></i> Условия
                    </a>
                </div>
            </div>

            {{-- 🤝 Соцсети --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-4 text-center">Мы в соцсетях</h3>
                <div class="flex justify-center flex-wrap gap-3 text-xl text-gray-700">
                    <a href="#" class="hover:text-blue-500 transition"><i class="fab fa-telegram-plane"></i></a>
                    <a href="#" class="hover:text-green-500 transition"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="hover:text-blue-700 transition"><i class="fab fa-vk"></i></a>
                    <a href="#" class="hover:text-gray-800 transition"><i class="fab fa-github"></i></a>
                    <a href="#" class="hover:text-red-600 transition"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="hover:text-indigo-600 transition"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
        </div>

        {{-- 🔻 Нижняя секция: подписка, язык, копирайт --}}
        <div class="border-t border-gray-200 px-4 py-6 bg-white/80 backdrop-blur-sm">
            <div class="max-w-screen-xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6 text-xs text-gray-500">

                {{-- ✉️ Подписка --}}
                <form method="POST" action="#" class="flex flex-col md:flex-row md:items-center gap-3 text-sm">
                    @csrf
                    <input type="email" name="email" placeholder="Ваш email"
                           class="px-4 py-2 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                           required>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition font-semibold">
                        Подписаться
                    </button>
                </form>

                {{-- 🌍 Язык и копирайт --}}
                <div class="flex flex-col md:flex-row md:items-center gap-4 text-xs text-gray-600">
                    <form method="POST" action="#" class="flex items-center gap-2">
                        @csrf
                        <label for="lang" class="font-medium">Язык:</label>
                        <select name="locale" id="lang"
                                class="border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="ru" selected>🇷🇺 Русский</option>
                            <option value="en">🇬🇧 English</option>
                        </select>
                    </form>
                    <span>© {{ date('Y') }} Все права защищены</span>
                </div>
            </div>
        </div>
    </div>
</footer>
