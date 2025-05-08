<footer class="bg-white border-t mt-10 shadow-inner text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">
    <div class="max-w-screen-xl mx-auto px-4 py-6 grid md:grid-cols-3 gap-6 items-center text-center md:text-left">

        {{-- 🧩 Инфо --}}
        <div>
            <div class="text-gray-700 dark:text-gray-200 font-semibold tracking-wide">
                🛠️ <span class="text-blue-600 dark:text-blue-400 font-bold">RuShop CMS</span> — Панель управления
            </div>
            <div class="text-xs mt-1 text-gray-400">
                Разработчик: Булавацкий Д.О. &nbsp;|&nbsp; v1.0.0 · PHP {{ PHP_VERSION }}
            </div>
            <div class="text-xs text-gray-400 mt-1" id="footer-time">
                Обновлено: <span class="font-mono">—</span>
            </div>
        </div>

        {{-- 📚 Ссылки --}}
        <div class="flex flex-col space-y-1 items-center md:items-start">
            <a href="/terms" class="hover:underline hover:text-blue-600">📄 Условия использования</a>
            <a href="https://github.com/Bulavackii/mycms" target="_blank" class="hover:underline hover:text-blue-600">🔧 GitHub</a>
            <a href="/admin/help" class="hover:underline hover:text-blue-600">💬 Поддержка</a>
        </div>

        {{-- 🌙 Переключатель темы + соцсети --}}
        <div class="flex flex-col items-center md:items-end space-y-2">
            <div class="flex space-x-4 text-lg">
                <a href="https://t.me/username" title="Telegram" class="hover:text-blue-500" target="_blank" rel="noopener">
                    <i class="fab fa-telegram-plane"></i>
                </a>
                <a href="https://vk.com/username" title="VK" class="hover:text-blue-700" target="_blank" rel="noopener">
                    <i class="fab fa-vk"></i>
                </a>
            </div>
            <button onclick="toggleTheme()" class="text-xs hover:text-indigo-600 mt-2">
                🌓 Переключить тему
            </button>
        </div>
    </div>
</footer>
