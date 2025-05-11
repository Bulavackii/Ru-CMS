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
        <div class="text-center text-xs md:text-sm text-gray-500 dark:text-gray-400 italic select-none">
            💡 Контент — это не только текст. Это впечатление, которое вы создаёте.
        </div>

        {{-- 🔔 Сообщения + уведомления + профиль --}}
        <div class="flex items-center gap-4">

            {{-- Уведомления --}}
            @php $unread = \Modules\Notifications\Models\Notification::where('enabled', 1)->count(); @endphp
            <a href="{{ route('admin.notifications.index') }}" class="relative hover:text-blue-600" title="Уведомления">
                <i class="fas fa-bell text-lg"></i>
                @if ($unread > 0)
                    <span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full px-1 animate-pulse">
                        {{ $unread }}
                    </span>
                @endif
            </a>

            {{-- Сообщения --}}
            @php $unreadMessages = \Modules\Messages\Models\Message::where('is_read', false)->count(); @endphp
            <a href="{{ route('admin.messages.index') }}" class="relative hover:text-blue-600" title="Сообщения">
                <i class="fas fa-envelope text-lg"></i>
                @if ($unreadMessages > 0)
                    <span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full px-1 animate-pulse">
                        {{ $unreadMessages }}
                    </span>
                @endif
            </a>

            {{-- Профиль --}}
            <x-user-dropdown />
        </div>
    </div>
</header>
