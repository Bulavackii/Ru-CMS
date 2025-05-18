<header class="bg-white border-b shadow text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
    <div class="max-w-screen-xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-4">

        {{-- 🎯 Логотип RU CMS --}}
        <div class="flex items-center gap-3">
            <a href="{{ url('#') }}"
                class="flex items-center gap-2 text-2xl font-extrabold tracking-tight hover:text-blue-600 transition">
                <div
                    class="bg-blue-600 text-white w-8 h-8 rounded-md flex items-center justify-center shadow-inner text-sm">
                    RU</div>
                <span class="text-gray-700 dark:text-white">CMS</span>
            </a>
            <span class="text-xs text-gray-400 dark:text-gray-500 hidden sm:inline">— Панель управления</span>
        </div>

        {{-- 💬 Слоган / факт дня --}}
        <div
            class="text-center text-xs md:text-sm text-gray-500 dark:text-gray-400 italic px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-md shadow-md w-full md:w-auto">
            @php
                $facts = [
                    '💡 Контент — это не только текст. Это впечатление, которое вы создаёте.',
                    '🔍 Сильный контент увеличивает доверие и повышает вовлечённость.',
                    '🚀 Хороший контент может изменить восприятие вашего бренда.',
                    '📚 Важность контента возрастает с ростом цифровой культуры.',
                    '📝 Создавайте контент, который решает проблемы вашей аудитории.',
                ];
                $randomFact = $facts[array_rand($facts)];
            @endphp
            {{ $randomFact }}
        </div>

        {{-- 🔔 Уведомления, заказы, сообщения, профиль --}}
        <div class="flex items-center gap-4 relative">

            {{-- 🔔 Уведомления --}}
            @php $unread = \Modules\Notifications\Models\Notification::where('enabled', 1)->count(); @endphp
            <a href="{{ route('admin.notifications.index') }}" class="relative hover:text-blue-500 transition"
                title="Уведомления">
                <i class="fas fa-bell text-lg"></i>
                @if ($unread > 0)
                    <span
                        class="absolute -top-2 -right-3 bg-red-500 text-white text-[11px] font-semibold px-1.5 py-0.5 rounded-full shadow-sm animate-bounce leading-none">
                        {{ $unread }}
                    </span>
                @endif
            </a>

            {{-- 📦 Заказы --}}
            @php $newOrders = \Modules\Payments\Models\Order::where('is_new', true)->count(); @endphp
            <a href="{{ route('admin.orders.index') }}" class="relative hover:text-green-500 transition"
                title="Новые заказы">
                <i class="fas fa-box text-lg"></i>
                @if ($newOrders > 0)
                    <span
                        class="absolute -top-2 -right-3 bg-green-500 text-white text-[11px] font-semibold px-1.5 py-0.5 rounded-full shadow-sm animate-bounce leading-none">
                        {{ $newOrders }}
                    </span>
                @endif
            </a>

            {{-- ✉️ Сообщения --}}
            @php $unreadMessages = \Modules\Messages\Models\Message::where('is_read', false)->count(); @endphp
            <a href="{{ route('admin.messages.index') }}" class="relative hover:text-indigo-500 transition"
                title="Сообщения">
                <i class="fas fa-envelope text-lg"></i>
                @if ($unreadMessages > 0)
                    <span
                        class="absolute -top-2 -right-3 bg-indigo-600 text-white text-[11px] font-semibold px-1.5 py-0.5 rounded-full shadow-sm animate-bounce leading-none">
                        {{ $unreadMessages }}
                    </span>
                @endif
            </a>

            {{-- 👤 Профиль --}}
            <x-user-dropdown />
        </div>
    </div>
</header>
