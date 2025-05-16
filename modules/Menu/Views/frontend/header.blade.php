<nav class="bg-white dark:bg-gray-800 border-t border-b border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="max-w-screen-xl mx-auto px-4">
        <ul class="flex flex-wrap justify-center gap-4 py-3 text-sm font-medium">
            @foreach ($menus as $menu)
                @foreach ($menu->items->whereNull('parent_id') as $item)
                    @php
                        $hasChildren = $item->children->count() > 0;
                        $link = match($item->type) {
                            'url' => $item->url,
                            'page' => route('frontend.pages.show', $item->linked_id),
                            'category' => url('/?category=' . $item->linked_id),
                            default => '#',
                        };
                    @endphp
                    <li class="relative group">
                        <a href="{{ $link }}"
                           class="px-3 py-2 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-white transition">
                            {{ $item->title }}
                        </a>

                        @if ($hasChildren)
                            <ul class="absolute z-50 left-0 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded shadow-md hidden group-hover:block min-w-[10rem]">
                                @foreach ($item->children as $child)
                                    @php
                                        $childLink = match($child->type) {
                                            'url' => $child->url,
                                            'page' => route('frontend.pages.show', $child->linked_id),
                                            'category' => url('/?category=' . $child->linked_id),
                                            default => '#',
                                        };
                                    @endphp
                                    <li>
                                        <a href="{{ $childLink }}"
                                           class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            {{ $child->title }}
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
