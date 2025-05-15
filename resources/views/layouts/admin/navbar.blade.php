<header class="bg-white border-b shadow text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
    <div class="max-w-screen-xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-3">

        {{-- 🔷 Логотип / Название --}}
        <div class="flex items-center space-x-3">
            <a href="{{ url('/admin') }}" class="text-2xl font-extrabold text-blue-600 hover:text-blue-700 transition">
                ⚙️ RuShop CMS
            </a>
            <span class="text-xs text-gray-400 dark:text-gray-500 hidden sm:inline">— Панель управления</span>
        </div>

        {{-- 🧠 Интересный факт / слоган --}}
        <div
            class="text-center text-xs md:text-sm text-gray-500 dark:text-gray-400 italic select-none py-2 px-3 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-md">
            {{-- Рандомный интересный факт --}}
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

        {{-- 🔔 Сообщения + уведомления + профиль --}}
        <div class="flex items-center gap-4">

            {{-- Уведомления --}}
            @php $unread = \Modules\Notifications\Models\Notification::where('enabled', 1)->count(); @endphp
            <a href="{{ route('admin.notifications.index') }}" class="relative hover:text-blue-600" title="Уведомления">
                <i class="fas fa-bell text-lg"></i>
                @if ($unread > 0)
                    <span
                        class="absolute -top-1 -right-3 bg-red-500 text-white text-xs rounded-full px-1 animate-pulse">
                        {{ $unread }}
                    </span>
                @endif
            </a>

            {{-- Заказы (новые) --}}
            @php $newOrders = \Modules\Payments\Models\Order::where('is_new', true)->count(); @endphp
            <a href="{{ route('admin.orders.index') }}" class="relative hover:text-blue-600" title="Новые заказы">
                <i class="fas fa-box text-lg"></i>
                @if ($newOrders > 0)
                    <span
                        class="absolute -top-1 -right-3 bg-green-600 text-white text-xs rounded-full px-1 animate-pulse">
                        {{ $newOrders }}
                    </span>
                @endif
            </a>

            {{-- Сообщения --}}
            @php $unreadMessages = \Modules\Messages\Models\Message::where('is_read', false)->count(); @endphp
            <a href="{{ route('admin.messages.index') }}" class="relative hover:text-blue-600" title="Сообщения">
                <i class="fas fa-envelope text-lg"></i>
                @if ($unreadMessages > 0)
                    <span
                        class="absolute -top-1 -right-3 bg-red-500 text-white text-xs rounded-full px-1 animate-pulse">
                        {{ $unreadMessages }}
                    </span>
                @endif
            </a>

            {{-- Профиль --}}
            <x-user-dropdown />
        </div>
    </div>
</header>
