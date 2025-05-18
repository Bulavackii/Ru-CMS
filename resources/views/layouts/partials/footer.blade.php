<footer class="relative text-sm text-gray-600 mt-10">
    {{-- 🖼️ Фон-узор --}}
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;">
    </div>

    {{-- 🌫️ Основной контейнер --}}
    <div class="relative z-10 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-t border-gray-200 dark:border-gray-700 shadow-inner">

        {{-- 🔝 Верхняя часть --}}
        <div class="max-w-screen-xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-3 gap-10">

            {{-- 🛠️ Инфо о CMS --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="bg-blue-600 text-white font-bold w-8 h-8 flex items-center justify-center rounded-md shadow-inner text-sm tracking-wide">RU</div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">CMS</h3>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Разработчик — Булавацкий Д.О.</p>
                <p class="text-xs text-gray-400 mt-2">Laravel {{ app()->version() }}</p>
            </div>

            {{-- 🌐 Навигация --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4 text-center">Навигация</h3>
                <div class="grid grid-cols-2 gap-2 text-sm justify-items-center">
                    @php
                        $navLinks = [
                            ['url' => '/', 'icon' => 'home', 'text' => 'Главная'],
                            ['url' => '/about', 'icon' => 'book-open', 'text' => 'О нас'],
                            ['url' => '/contacts', 'icon' => 'phone-alt', 'text' => 'Контакты'],
                            ['url' => '/faq', 'icon' => 'question-circle', 'text' => 'Вопросы'],
                            ['url' => '/privacy', 'icon' => 'user-secret', 'text' => 'Политика'],
                            ['url' => '/terms', 'icon' => 'file-contract', 'text' => 'Условия'],
                        ];
                    @endphp
                    @foreach ($navLinks as $link)
                        <a href="{{ url($link['url']) }}"
                           class="flex items-center gap-1 text-gray-700 dark:text-gray-300 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-800 px-2 py-1 rounded transition">
                            <i class="fas fa-{{ $link['icon'] }}"></i> {{ $link['text'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- 🌍 Социальные сети --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4 text-center">Мы в соцсетях</h3>
                <div class="flex justify-center flex-wrap gap-3 text-xl text-gray-600 dark:text-gray-300">
                    @php
                        $socials = [
                            ['icon' => 'telegram-plane', 'color' => 'text-blue-500'],
                            ['icon' => 'whatsapp', 'color' => 'text-green-500'],
                            ['icon' => 'vk', 'color' => 'text-blue-700'],
                            ['icon' => 'github', 'color' => 'text-gray-800'],
                            ['icon' => 'youtube', 'color' => 'text-red-600'],
                            ['icon' => 'envelope', 'color' => 'text-indigo-600'],
                        ];
                    @endphp
                    @foreach ($socials as $s)
                        <a href="#" class="hover:{{ $s['color'] }} transition transform hover:scale-110" aria-label="{{ $s['icon'] }}">
                            <i class="fab fa-{{ $s['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 🔻 Нижняя часть --}}
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm">
            <div class="max-w-screen-xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6 text-xs text-gray-500 dark:text-gray-400">

                {{-- ✉️ Подписка --}}
                <form method="POST" action="#" class="flex flex-col md:flex-row md:items-center gap-3 text-sm w-full md:w-auto">
                    @csrf
                    <input type="email" name="email" placeholder="Ваш email"
                           class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none w-full md:w-auto"
                           required>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition font-semibold">
                        Подписаться
                    </button>
                </form>

                {{-- 🌍 Язык и копирайт --}}
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <form method="POST" action="#" class="flex items-center gap-2">
                        @csrf
                        <label for="lang" class="font-medium">Язык:</label>
                        <select name="locale" id="lang"
                                class="border border-gray-300 dark:border-gray-600 rounded px-3 py-1 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="ru" selected>🇷🇺 Русский</option>
                            <option value="en">🇬🇧 English</option>
                        </select>
                    </form>
                    <span class="text-center md:text-left">© {{ date('Y') }} Все права защищены</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔝 Кнопка "Наверх" --}}
    <button id="backToTopBtn"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-6 right-6 z-50 p-3 rounded-full shadow-md bg-blue-600 text-white hover:bg-blue-700 transition transform hover:scale-105 hidden"
            title="Наверх">
        <i class="fas fa-arrow-up"></i>
    </button>
</footer>

{{-- 🔧 JS + Анимация --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('backToTopBtn');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 200) {
                btn.classList.remove('hidden');
                btn.classList.add('animate-fade-in');
            } else {
                btn.classList.add('hidden');
            }
        });
    });
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fade-in 0.4s ease-out;
    }
</style>
