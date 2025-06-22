<footer class="bg-white dark:bg-gray-900 border-t mt-10 shadow-inner text-sm text-gray-500 dark:text-gray-400">
    <div class="max-w-screen-xl mx-auto px-4 py-6 grid md:grid-cols-3 gap-8 items-center text-center md:text-left">

        {{-- 🧩 Инфо о CMS --}}
        <div>
            <div class="text-gray-800 dark:text-gray-200 font-semibold tracking-wide">
                🛠️ <span class="text-blue-600 dark:text-blue-400 font-bold">RU</span><span class="font-bold"> CMS</span> — Панель управления
            </div>
            <div class="text-xs mt-1 text-gray-400">
                Разработчик: Булавацкий Д.О. &nbsp;|&nbsp; v1.0.0 · PHP {{ PHP_VERSION }}
            </div>
            <div class="text-xs text-gray-400 mt-1" id="footer-time">
                Обновлено: <span class="font-mono">—</span>
            </div>
        </div>

        {{-- 📚 Полезные ссылки --}}
        <div class="flex flex-col space-y-1 items-center md:items-start">
            <a href="/terms" class="hover:underline hover:text-blue-600 transition">📄 Условия использования</a>
            <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank" class="hover:underline hover:text-blue-600 transition">🔧 GitHub проекта</a>
            <a href="/admin/help" class="hover:underline hover:text-blue-600 transition">💬 Поддержка и помощь</a>
        </div>

        {{-- 🌙 Переключатель темы и соцсети --}}
        <div class="flex flex-col items-center md:items-end space-y-2">
            <div class="flex space-x-4 text-xl">
                <a href="https://t.me/ru_cms" title="Telegram" class="hover:text-blue-500 transition" target="_blank">
                    <i class="fab fa-telegram-plane"></i>
                </a>
                <a href="https://vk.com/ru_cms" title="VK" class="hover:text-blue-700 transition" target="_blank">
                    <i class="fab fa-vk"></i>
                </a>
            </div>
            <button onclick="toggleTheme()" class="text-xs text-gray-500 hover:text-indigo-600 transition mt-2 flex items-center gap-1">
                <i class="fas fa-adjust"></i> Переключить тему
            </button>
        </div>
    </div>

    {{-- 🕒 JS для обновления времени --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const span = document.getElementById('footer-time').querySelector('span');
            const now = new Date();
            span.textContent = now.toLocaleString('ru-RU', {
                dateStyle: 'medium',
                timeStyle: 'short'
            });
        });

        // 🌙 Тема
        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        }

        // 🌙 Автоустановка темы при загрузке
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</footer>
