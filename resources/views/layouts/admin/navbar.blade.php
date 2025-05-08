<header class="bg-white dark:bg-gray-900 shadow-md py-4 px-4 md:px-6 text-sm z-40 relative">
    <div class="max-w-screen-xl mx-auto flex justify-between items-center">

        {{-- 🧩 Лого --}}
        <a href="{{ url('/admin') }}"
           class="flex items-center text-xl font-extrabold text-blue-600 dark:text-blue-400 tracking-tight hover:opacity-80 transition">
            <i class="fas fa-cogs mr-2"></i> RuShop Admin
        </a>

        {{-- 🔧 Панель навигации --}}
        <div class="flex items-center space-x-5">

            {{-- 🌗 Переключатель темы --}}
            @includeIf('layouts.partials.theme-switcher')

            {{-- 🔔 Уведомления --}}
            @php
                $unread = \Modules\Notifications\Models\Notification::where('enabled', 1)->count();
            @endphp
            <a href="{{ route('admin.notifications.index') }}"
               class="relative text-gray-600 dark:text-gray-300 hover:text-blue-600 transition">
                <i class="fas fa-bell text-lg"></i>
                @if ($unread > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1 animate-ping-slow">
                        {{ $unread }}
                    </span>
                @endif
            </a>

            {{-- 💬 Сообщения (заглушка) --}}
            <a href="#" title="Сообщения (в разработке)"
               class="relative text-gray-600 dark:text-gray-300 hover:text-blue-600 transition">
                <i class="fas fa-envelope text-lg opacity-50"></i>
            </a>

            {{-- 👤 Профиль --}}
            <x-user-dropdown />
        </div>
    </div>
</header>
