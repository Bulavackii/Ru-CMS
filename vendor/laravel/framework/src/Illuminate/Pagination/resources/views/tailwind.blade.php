@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Навигация по страницам" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mt-6">
        <div class="text-sm text-gray-700 text-center sm:text-left">
            Показано от <strong>{{ $paginator->firstItem() }}</strong>
            до <strong>{{ $paginator->lastItem() }}</strong>
            из <strong>{{ $paginator->total() }}</strong> результатов
        </div>

        <div class="flex justify-center sm:justify-end">
            <span class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px animate-fade-in">
                {{-- Назад --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Предыдущая">
                        <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-l-md">
                            <i class="fas fa-angle-left"></i>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:text-blue-600 rounded-l-md transition-all duration-200" aria-label="Предыдущая">
                        <i class="fas fa-angle-left"></i>
                    </a>
                @endif

                {{-- Номера страниц --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default">{{ $element }}</span>
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-semibold text-white bg-blue-600 border border-blue-600 cursor-default scale-105">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-blue-600 bg-white border border-gray-300 hover:bg-gray-100 transition-all duration-200">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Вперёд --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:text-blue-600 rounded-r-md transition-all duration-200" aria-label="Следующая">
                        <i class="fas fa-angle-right"></i>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Следующая">
                        <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-r-md">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </nav>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-in-out;
        }
    </style>
@endif
