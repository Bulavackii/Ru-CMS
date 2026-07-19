@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Навигация по страницам" class="flex items-center justify-between">
        {{-- 📱 Мобильная пагинация --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                    ← Назад
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:text-gray-500 focus:outline-none transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                    ← Назад
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:text-gray-500 focus:outline-none transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                    Вперёд →
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                    Вперёд →
                </span>
            @endif
        </div>

        {{-- 💻 Десктопная пагинация --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            {{-- ℹ️ Информация о текущем диапазоне --}}
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-400">
                    Показано с
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    по
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    из
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    записей
                </p>
            </div>

            {{-- 🔢 Номера страниц --}}
            <div>
                <span class="relative z-0 inline-flex rtl:flex-row-reverse shadow-sm rounded-md">
                    {{-- Назад --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Предыдущая страница">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md dark:bg-gray-800 dark:border-gray-600" aria-hidden="true">
                                ←
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                           class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:text-gray-400 focus:z-10 focus:outline-none transition dark:bg-gray-800 dark:border-gray-600 dark:hover:text-gray-300"
                           aria-label="Предыдущая страница">
                            ←
                        </a>
                    @endif

                    {{-- Страницы --}}
                    @foreach ($elements as $element)
                        {{-- Три точки --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default dark:bg-gray-800 dark:border-gray-600">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Номера страниц --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-black border border-gray-300 cursor-default dark:border-gray-700">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}"
                                       class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:text-gray-500 focus:z-10 focus:outline-none transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-white"
                                       aria-label="Перейти на страницу {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Вперёд --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                           class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:text-gray-400 focus:z-10 focus:outline-none transition dark:bg-gray-800 dark:border-gray-600 dark:hover:text-gray-300"
                           aria-label="Следующая страница">
                            →
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Следующая страница">
                            <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md dark:bg-gray-800 dark:border-gray-600" aria-hidden="true">
                                →
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
