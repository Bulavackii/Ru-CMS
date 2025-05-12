<aside
    class="w-64 h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-lg flex flex-col z-40 transition-all duration-300">

    {{-- 🔰 Логотип / Верх --}}
    <div
        class="flex items-center justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/flag.jpg') }}" alt="Флаг России" class="w-8 h-5 object-cover rounded-sm shadow" />
            <span class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                ⚙️ Панель
            </span>
        </div>
    </div>

    {{-- 📂 Навигация --}}
    <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-2 text-[15px] font-medium">

        {{-- Контент --}}
        <div>
            <p class="text-[11px] uppercase text-gray-400 dark:text-gray-500 font-semibold px-2 mb-1">Контент</p>
            @php
                $links = [
                    [
                        'route' => route('admin.news.index'),
                        'check' => request()->is('admin/news*'),
                        'icon' => 'fa-newspaper',
                        'label' => 'Новости',
                    ],
                    [
                        'route' => route('admin.categories.index'),
                        'check' => request()->is('admin/categories*'),
                        'icon' => 'fa-tags',
                        'label' => 'Категории',
                    ],
                    [
                        'route' => route('admin.slideshow.index'),
                        'check' => request()->is('admin/slideshow*'),
                        'icon' => 'fa-images',
                        'label' => 'Слайдшоу',
                    ],
                    [
                        'route' => route('admin.files.index'),
                        'check' => request()->is('admin/files*'),
                        'icon' => 'fa-folder',
                        'label' => 'Файлы',
                    ],
                ];
            @endphp

            @foreach ($links as $link)
                <a href="{{ $link['route'] }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition group
                   {{ $link['check']
                       ? 'bg-black text-white font-semibold shadow-md'
                       : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-black dark:hover:text-white' }}">
                    <i class="fas {{ $link['icon'] }} w-5 opacity-70 group-hover:opacity-100 transition"></i>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Система --}}
        <div class="mt-5">
            <p class="text-[11px] uppercase text-gray-400 dark:text-gray-500 font-semibold px-2 mb-1">Система</p>
            @php
                $systemLinks = [
                    [
                        'url' => '/admin/modules',
                        'check' => request()->is('admin/modules'),
                        'icon' => 'fa-cubes',
                        'label' => 'Модули',
                    ],
                    [
                        'url' => '/admin/users',
                        'check' => request()->is('admin/users'),
                        'icon' => 'fa-users',
                        'label' => 'Пользователи',
                    ],
                    [
                        'url' => '/admin/search',
                        'check' => request()->is('admin/search'),
                        'icon' => 'fa-search',
                        'label' => 'Поиск',
                    ],
                    [
                        'url' => route('admin.notifications.index'),
                        'check' => request()->is('admin/notifications*'),
                        'icon' => 'fa-bell',
                        'label' => 'Уведомления',
                    ],
                ];
            @endphp

            @foreach ($systemLinks as $link)
                <a href="{{ $link['url'] }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition group
                   {{ $link['check']
                       ? 'bg-black text-white font-semibold shadow-md'
                       : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-black dark:hover:text-white' }}">
                    <i class="fas {{ $link['icon'] }} w-5 opacity-70 group-hover:opacity-100 transition"></i>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- 💡 Совет дня --}}
    @php
        $tips = [
            '🧠 Хорошая структура — залог масштабируемости.',
            '🔐 Никогда не игнорируй безопасность.',
            '⚙️ Меньше — лучше. Убирай лишнее.',
            '📊 Анализируй поведение пользователей.',
        ];
        $tip = $tips[array_rand($tips)];
    @endphp
    <div
        class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400 italic bg-gray-50 dark:bg-gray-800 border-t border-b border-gray-200 dark:border-gray-700">
        {{ $tip }}
    </div>

    {{-- 📌 Подвал --}}
    <div class="px-6 py-4 border-t text-xs text-gray-500 dark:text-gray-500 bg-white dark:bg-gray-900">
        Версия CMS: <strong class="text-black dark:text-white">1.0</strong>
    </div>

    {{-- 🔄 Анимации --}}
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</aside>
