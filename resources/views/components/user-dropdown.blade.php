@props([
    'name' => auth()->user()->name ?? 'Админ',
    'avatar' => auth()->user()->avatar ?? null,
])

<div x-data="{ open: false }" class="relative">
    {{-- 🔘 Кнопка пользователя --}}
    <button @click="open = !open"
            class="flex items-center space-x-2 text-gray-600 dark:text-gray-200 hover:text-blue-600 transition focus:outline-none">
        @if ($avatar)
            <img src="{{ asset($avatar) }}" alt="avatar"
                 class="w-8 h-8 rounded-full border border-gray-300 dark:border-gray-700 shadow-sm" />
        @else
            <i class="fas fa-user-circle text-2xl"></i>
        @endif
        <span class="hidden md:inline font-medium truncate max-w-[120px]">{{ $name }}</span>
    </button>

    {{-- 📥 Выпадающее меню --}}
    <div x-show="open"
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="absolute right-0 mt-3 w-52 bg-white dark:bg-gray-800 shadow-2xl rounded-xl py-2 z-50 text-sm ring-1 ring-gray-100 dark:ring-gray-700"
         style="display: none;">

        @if (trim($slot))
            {{ $slot }}
        @else
            <a href="{{ url('/dashboard') }}"
               class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition">👤 Профиль</a>
            <a href="{{ url('/admin/modules') }}"
               class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition">⚙️ Модули</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 transition">
                    🚪 Выйти
                </button>
            </form>
        @endif
    </div>
</div>
