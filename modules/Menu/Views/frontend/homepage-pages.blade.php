@foreach ($pages as $page)
    <article class="w-full mb-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm transition-all duration-300">

        {{-- 📰 Заголовок страницы --}}
        <header class="px-6 pt-6 pb-2 text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white break-words leading-tight mb-2">
                @if (!empty($page->slug))
                    <a href="{{ route('frontend.pages.show', $page->slug) }}"
                       class="hover:text-blue-600 transition inline-flex items-center gap-1"
                       title="Открыть страницу">
                        🔗 {{ $page->title }}
                    </a>
                @else
                    {{ $page->title }}
                @endif
            </h2>

            {{-- 🏷️ Категории --}}
            @if ($page->categories->isNotEmpty())
                <div class="flex flex-wrap justify-center gap-2 text-xs sm:text-sm mb-3">
                    @foreach ($page->categories as $category)
                        <a href="{{ url('/?category=' . $category->id) }}"
                           class="inline-block bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100 px-3 py-1 rounded-full font-medium transition">
                            🏷️ {{ $category->title }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- 🔹 Декоративная линия --}}
            <div class="flex justify-center">
                <hr class="w-24 border-t-2 border-black dark:border-white opacity-30 mb-2">
            </div>
        </header>

        {{-- 📄 Контент страницы --}}
        <div class="page-content prose dark:prose-invert max-w-none px-6 pb-6 text-gray-800 dark:text-gray-100">
            {!! $page->content !!}
        </div>
    </article>
@endforeach

@push('styles')
<style>
    .page-content {
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: break-word;
    }

    .page-content img,
    .page-content iframe,
    .page-content video,
    .page-content embed,
    .page-content object {
        display: inline-block;
        max-width: 100%;
        height: auto;
        border-radius: 0.75rem;
        margin: 1rem auto;
    }

    .page-content img[style*="float:left"],
    .page-content iframe[style*="float:left"],
    .page-content video[style*="float:left"],
    .page-content embed[style*="float:left"],
    .page-content object[style*="float:left"],
    .page-content img[style*="float: left"],
    .page-content iframe[style*="float: left"],
    .page-content video[style*="float: left"],
    .page-content embed[style*="float: left"],
    .page-content object[style*="float: left"] {
        float: left;
        margin-right: 1rem;
        margin-left: 0;
    }

    .page-content img[style*="float:right"],
    .page-content iframe[style*="float:right"],
    .page-content video[style*="float:right"],
    .page-content embed[style*="float:right"],
    .page-content object[style*="float:right"],
    .page-content img[style*="float: right"],
    .page-content iframe[style*="float: right"],
    .page-content video[style*="float: right"],
    .page-content embed[style*="float: right"],
    .page-content object[style*="float: right"] {
        float: right;
        margin-left: 1rem;
        margin-right: 0;
    }

    .page-content:after {
        content: "";
        display: table;
        clear: both;
    }

    /* 🎨 UI для ссылок */
    .page-content a {
        color: #2563eb; /* синий в светлой теме */
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    .dark .page-content a {
        color: #60a5fa; /* светло-синий в тёмной теме */
    }

    .page-content a:hover {
        text-decoration: underline;
        color: #1d4ed8; /* темнее при ховере */
    }
</style>
@endpush

