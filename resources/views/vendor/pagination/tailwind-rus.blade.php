@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Пагинация" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            {{-- Кнопка назад --}}
            @if ($paginator->onFirstPage())
                <span
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md">
                    Назад
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Назад
                </a>
            @endif

            {{-- Кнопка вперед --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                    class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Вперёд
                </a>
            @else
                <span
                    class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md">
                    Вперёд
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            {{-- Текст "Показано с X по Y из Z" --}}
            <div class="mr-6">
                <p class="text-sm text-gray-700 leading-5">
                    Показано
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    —
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    из
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    записей
                </p>
            </div>

            {{-- Ссылки на страницы --}}
            <div class="ml-6">
                <span class="relative z-0 inline-flex rounded-md shadow-sm">
                    {{-- Кнопка "Предыдущая страница" --}}
                    @if ($paginator->onFirstPage())
                        <span
                            aria-disabled="true"
                            aria-label="@lang('pagination.previous')"
                            class="relative inline-flex items-center px-2 py-2 text-gray-300 bg-white border border-gray-300 cursor-default rounded-l-md"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 15.707a1 1 0 01-1.414 0L6.586 11l4.707-4.707a1 1 0 111.414 1.414L9.414 11l3.293 3.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @else
                        <a
                            href="{{ $paginator->previousPageUrl() }}"
                            rel="prev"
                            aria-label="@lang('pagination.previous')"
                            class="relative inline-flex items-center px-2 py-2 text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 15.707a1 1 0 01-1.414 0L6.586 11l4.707-4.707a1 1 0 111.414 1.414L9.414 11l3.293 3.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Номера страниц --}}
                    @foreach ($elements as $element)
                        {{-- "Три точки" --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="relative inline-flex items-center px-4 py-2 -ml-px text-gray-700 bg-white border border-gray-300 cursor-default">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Массив ссылок --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="relative inline-flex items-center px-4 py-2 -ml-px text-white bg-blue-600 border border-blue-600 cursor-default">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Кнопка "Следующая страница" --}}
                    @if ($paginator->hasMorePages())
                        <a
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                            aria-label="@lang('pagination.next')"
                            class="relative inline-flex items-center px-2 py-2 -ml-px text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 4.293a1 1 0 011.414 0L13.414 9l-4.707 4.707a1 1 0 01-1.414-1.414L10.586 9 7.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span
                            aria-disabled="true"
                            aria-label="@lang('pagination.next')"
                            class="relative inline-flex items-center px-2 py-2 -ml-px text-gray-300 bg-white border border-gray-300 cursor-default rounded-r-md"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 4.293a1 1 0 011.414 0L13.414 9l-4.707 4.707a1 1 0 01-1.414-1.414L10.586 9 7.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
