<footer class="relative text-sm mt-10" style="color:var(--color-text,#6b7280)">
    {{-- фон-паттерн из темы --}}
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
         style="background-image: var(--bg-image); background-repeat:repeat; background-size:auto;"></div>

    <div class="relative z-10 backdrop-blur-md border-t border-gray-200 shadow-inner"
         style="background:var(--color-footer,#ffffff)">

        <div class="max-w-screen-xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-3 gap-10">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="text-white font-bold w-8 h-8 flex items-center justify-center shadow-inner text-sm tracking-wide rounded"
                         style="background:var(--color-primary,#2563eb)">RU</div>
                    <h3 class="text-lg font-bold" style="color:var(--color-text,#111827)">CMS</h3>
                </div>
                <p class="text-xs opacity-80">Разработчик — Булавацкий Д.О.</p>
                <p class="text-xs opacity-70 mt-2">Laravel {{ app()->version() }}</p>
            </div>

            <div>
                <h3 class="text-base font-semibold mb-4 text-center" style="color:var(--color-text,#111827)">Навигация</h3>
                <div class="grid grid-cols-2 gap-2 text-sm justify-items-center">
                    @php
                        $navLinks = [
                            ['url' => '/terms',        'icon' => 'file-contract', 'text' => 'Соглашение'],
                            ['url' => '/partnership',  'icon' => 'handshake',     'text' => 'Сотрудничество'],
                            ['url' => '/developers',   'icon' => 'code',          'text' => 'Разработчикам'],
                            ['url' => '/concept',      'icon' => 'lightbulb',     'text' => 'Концепция'],
                            ['url' => '/sitemap',      'icon' => 'sitemap',       'text' => 'Карта сайта'],
                            ['url' => '/donate',       'icon' => 'donate',        'text' => 'Пожертвовать'],
                        ];
                    @endphp
                    @foreach ($navLinks as $link)
                        <a href="{{ url($link['url']) }}"
                           class="flex items-center gap-1 px-2 py-1 rounded transition text-[13px] hover:opacity-90"
                           style="color:var(--color-text,#374151)">
                            @themeIcon($link['icon']) {{ $link['text'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold mb-4 text-center" style="color:var(--color-text,#111827)">Мы в соцсетях</h3>
                <div class="flex justify-center flex-wrap gap-3 text-[18px]">
                    <a href="https://vk.com/ru_cms" target="_blank" class="transition transform hover:scale-110" aria-label="VK" style="color:var(--color-primary,#2563eb)">@themeIcon('vk')</a>
                    <a href="https://t.me/ru_cms" target="_blank" class="transition transform hover:scale-110" aria-label="Telegram" style="color:var(--color-primary,#2563eb)">@themeIcon('telegram-plane')</a>
                    <a href="https://wa.me/79856204400" target="_blank" class="transition transform hover:scale-110" aria-label="WhatsApp" style="color:#10b981">@themeIcon('whatsapp')</a>
                    <a href="https://github.com/Bulavackii/Ru-CMS" class="transition transform hover:scale-110" aria-label="GitHub" style="color:var(--color-text,#6b7280)">@themeIcon('github')</a>
                    <a href="#" class="transition transform hover:scale-110" aria-label="YouTube" style="color:#ef4444">@themeIcon('youtube')</a>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 px-4 py-6 backdrop-blur-sm" style="background:var(--color-footer,#ffffff)">
            <div class="max-w-screen-xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6 text-xs">
                <form method="POST" action="#" class="flex flex-col md:flex-row md:items-center gap-3 text-sm w-full md:w-auto">
                    @csrf
                    <input type="email" name="email" placeholder="Ваш email"
                           class="px-4 py-2 rounded-md border focus:ring-2 focus:outline-none w-full md:w-auto"
                           style="border-color:#d1d5db; background:#fff; color:var(--color-text,#111827)" required>
                    <button type="submit" class="px-4 py-2 rounded-md transition font-semibold"
                            style="background:var(--color-primary,#2563eb); color:#fff">Подписаться</button>
                </form>

                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <form method="POST" action="#" class="flex items-center gap-2">
                        @csrf
                        <label for="lang" class="font-medium" style="color:var(--color-text,#111827)">Язык:</label>
                        <select name="locale" id="lang" class="w-40 px-4 py-2 rounded-md border shadow-sm focus:ring-2 focus:outline-none transition"
                                style="border-color:#d1d5db; background:#fff; color:var(--color-text,#111827)">
                            <option value="ru" selected>🇷🇺 Русский</option>
                            <option value="en">🇬🇧 English</option>
                        </select>
                    </form>
                    <span class="text-center md:text-left" style="color:var(--color-text,#6b7280)">© {{ date('Y') }} Все права защищены</span>
                </div>
            </div>
        </div>
    </div>

    <button id="backToTopBtn" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-6 right-6 z-50 p-3 rounded-full shadow-md transition transform hover:scale-105 hidden"
            style="background:var(--color-primary,#2563eb); color:#fff" title="Наверх">
        @themeIcon('arrow-up')
    </button>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('backToTopBtn');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 200) { btn.classList.remove('hidden'); btn.classList.add('animate-fade-in'); }
        else { btn.classList.add('hidden'); }
    });
});
</script>
<style>
@keyframes fade-in { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
.animate-fade-in{ animation: fade-in .4s ease-out; }
</style>
