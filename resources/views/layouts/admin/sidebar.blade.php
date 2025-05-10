<aside class="w-64 bg-white h-screen shadow-lg border-r text-gray-800 flex flex-col"
       style="animation: fadeIn 0.4s ease-in-out;">

    {{-- 🔰 Верхний блок --}}
    <div class="flex items-center gap-3 px-6 py-5 border-b">
        <img src="{{ asset('images/flag.jpg') }}" alt="Флаг России"
             class="w-8 h-5 object-cover rounded-sm shadow-sm" />
        <span class="flex items-center gap-2 font-extrabold text-lg tracking-tight">
            <i class="fas fa-tools text-blue-500"></i> Админ-панель
        </span>
    </div>

    {{-- 📂 Навигация --}}
    <nav class="flex-1 overflow-y-auto mt-4 px-2 space-y-1 text-sm font-medium" style="scrollbar-width: thin;">

        {{-- Контент --}}
        <p class="px-4 pt-4 text-xs uppercase text-gray-400 font-semibold">Контент</p>

        @php
            $links = [
                ['route' => route('admin.news.index'), 'check' => request()->is('admin/news*'), 'icon' => 'fa-newspaper', 'label' => 'Новости'],
                ['route' => route('admin.categories.index'), 'check' => request()->is('admin/categories*'), 'icon' => 'fa-tags', 'label' => 'Категории'],
                ['route' => route('admin.slideshow.index'), 'check' => request()->is('admin/slideshow*'), 'icon' => 'fa-images', 'label' => 'Слайдшоу'],
            ];
        @endphp

        @foreach ($links as $link)
            <a href="{{ $link['route'] }}"
               title="{{ $link['label'] }}"
               class="flex items-center gap-2 px-4 py-2 rounded transition-all duration-200 group
               {{ $link['check']
                   ? 'bg-blue-50 text-blue-700 font-semibold border-l-4 border-blue-500 shadow-inner animate-pulse'
                   : 'hover:bg-blue-100 hover:text-blue-800 hover:shadow hover:font-semibold text-gray-700' }}">
                <i class="fas {{ $link['icon'] }} transition-transform duration-300 group-hover:rotate-6"></i>
                {{ $link['label'] }}
            </a>
        @endforeach

        {{-- Система --}}
        <p class="px-4 pt-6 text-xs uppercase text-gray-400 font-semibold">Система</p>

        @php
            $systemLinks = [
                ['url' => '/admin/modules', 'check' => request()->is('admin/modules'), 'icon' => 'fa-cubes', 'label' => 'Модули'],
                ['url' => '/admin/users', 'check' => request()->is('admin/users'), 'icon' => 'fa-users', 'label' => 'Пользователи'],
                ['url' => '/admin/search', 'check' => request()->is('admin/search'), 'icon' => 'fa-search', 'label' => 'Поиск'],
                ['url' => route('admin.notifications.index'), 'check' => request()->is('admin/notifications*'), 'icon' => 'fa-bell', 'label' => 'Уведомления'],
            ];
        @endphp

        @foreach ($systemLinks as $link)
            <a href="{{ $link['url'] }}"
               title="{{ $link['label'] }}"
               class="flex items-center gap-2 px-4 py-2 rounded transition-all duration-200 group
               {{ $link['check']
                   ? 'bg-blue-50 text-blue-700 font-semibold border-l-4 border-blue-500 shadow-inner animate-pulse'
                   : 'hover:bg-blue-100 hover:text-blue-800 hover:shadow hover:font-semibold text-gray-700' }}">
                <i class="fas {{ $link['icon'] }} transition-transform duration-300 group-hover:-translate-y-1"></i>
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- 📌 Подвал --}}
    <div class="px-6 py-4 border-t text-xs text-gray-500 bg-gray-50">
        Версия CMS: <strong>1.0</strong>
    </div>

    {{-- 🔄 Анимации --}}
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { box-shadow: inset 0 0 0 rgba(0, 0, 0, 0); }
            50% { box-shadow: inset 0 0 10px rgba(96, 165, 250, 0.25); }
        }
        .animate-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
</aside>
