{{-- 🌐 Адаптивное и стилизованное меню сайта --}}
<nav class="bg-white dark:bg-gray-800 border-t border-b border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="max-w-screen-xl mx-auto px-4">
        <ul class="flex flex-wrap justify-center gap-4 py-3 text-sm font-medium">
            @foreach ($menus as $menu)
                @foreach ($menu->items->whereNull('parent_id') as $item)
                    @php
                        $hasChildren = $item->children->isNotEmpty();

                        // 🔗 Ссылка по типу пункта
                        $link = match($item->type) {
                            'url' => $item->url,
                            'page' => optional($item->linkedPage)?->slug ? route('frontend.pages.show', optional($item->linkedPage)?->slug) : '#',
                            'category' => url('/?category=' . $item->linked_id),
                            default => '#',
                        };

                        // 📌 Иконки по типу
                        $icon = match($item->type) {
                            'url' => '🌐',
                            'page' => '📄',
                            'category' => '🏷️',
                            default => '📌',
                        };
                    @endphp

                    {{-- 🔹 Основной пункт меню --}}
                    <li class="relative group">
                        <a href="{{ $link }}"
                           class="px-3 py-2 flex items-center gap-1 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-white transition">
                            {{ $icon }} {{ $item->title }}
                        </a>

                        {{-- 🔽 Подменю, если есть дочерние пункты --}}
                        @if ($hasChildren)
                            <ul class="absolute z-50 left-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded shadow-md min-w-[12rem]">
                                @foreach ($item->children as $child)
                                    @php
                                        $childLink = match($child->type) {
                                            'url' => $child->url,
                                            'page' => optional($child->linkedPage)?->slug ? route('frontend.pages.show', optional($child->linkedPage)?->slug) : '#',
                                            'category' => url('/?category=' . $child->linked_id),
                                            default => '#',
                                        };

                                        $childIcon = match($child->type) {
                                            'url' => '🔗',
                                            'page' => '📄',
                                            'category' => '🏷️',
                                            default => '📌',
                                        };
                                    @endphp
                                    <li>
                                        <a href="{{ $childLink }}"
                                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                            {{ $childIcon }} {{ $child->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
</nav>
