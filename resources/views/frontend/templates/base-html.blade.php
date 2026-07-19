@push('styles')
<link rel="stylesheet" href="{{ local_css('prism-tomorrow.min.css') }}">
<style>
    .code-block {
        position: relative;
    }
    .copy-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background-color: #1f2937;
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        cursor: pointer;
        z-index: 10;
        transition: background-color 0.2s ease-in-out;
    }
    .copy-btn:hover {
        background-color: #374151;
    }
</style>
@endpush

@push('scripts')
<script src="{{ local_js('prism.min.js') }}"></script>
<script src="{{ local_js('prism-markup.min.js') }}"></script>
<script src="{{ local_js('prism-html.min.js') }}"></script>
<script>
    function copyCode(btn) {
        const code = btn.parentElement.querySelector('code');
        if (code) {
            const text = code.innerText;
            navigator.clipboard.writeText(text).then(() => {
                btn.innerText = '✅ Скопировано';
                setTimeout(() => btn.innerText = '📋 Копировать', 2000);
            });
        }
    }
</script>
@endpush

<div class="my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 dark:text-white tracking-tight flex items-center justify-center gap-2 select-none">
        <i class="fas fa-code text-red-600"></i>
        {{ $title ?? 'Уроки по HTML' }}
    </h2>

    @if ($newsList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($newsList as $news)
                @php
                    $mediaSrc = $news->cover
                        ? asset('storage/' . $news->cover)
                        : (
                            preg_match('/<video[^>]*src="([^"]+)"/i', $news->content, $videoMatch)
                                ? $videoMatch[1]
                                : (
                                    preg_match('/<source[^>]*src="([^"]+)"/i', $news->content, $sourceMatch)
                                        ? $sourceMatch[1]
                                        : (
                                            preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)
                                                ? $imgMatch[1]
                                                : asset('images/no-image.png')
                                        )
                                )
                        );
                    $isVideo = \Illuminate\Support\Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                @endphp

                <div class="relative flex flex-col p-5 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-2xl max-w-xs w-full shadow hover:shadow-lg transition-all duration-200 group">
                    <div class="absolute -top-3 right-3 z-10 bg-white dark:bg-gray-900 border-2 border-red-600 text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md select-none">
                        📕 HTML
                    </div>

                    @if ($news->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($news->categories as $category)
                                <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                                   class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline select-none">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 dark:border-gray-800 relative bg-gray-100 dark:bg-gray-800">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline controls>
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-1 leading-tight break-words line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-red-600 dark:hover:text-red-400 transition">
                            {{ $news->title }}
                        </a>
                    </h3>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1">
                        <i class="far fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    <div class="text-sm text-gray-600 dark:text-gray-300 mb-3 line-clamp-4">
                        💬 {!! Str::limit(strip_tags($news->content), 220) !!}
                    </div>

                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-auto block text-center text-sm bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>

        @if ($newsList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Показано с <span class="font-semibold">{{ $newsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $newsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $newsList->total() }}</span> записей
                </div>

                <nav class="flex items-center space-x-2 rtl:space-x-reverse" role="navigation">
                    @if ($newsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-500 rounded-md text-sm cursor-not-allowed">← Назад</span>
                    @else
                        <a href="{{ $newsList->previousPageUrl() }}"
                           class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md text-sm">
                            ← Назад
                        </a>
                    @endif

                    @foreach ($newsList->getUrlRange(1, $newsList->lastPage()) as $page => $url)
                        @if ($page == $newsList->currentPage())
                            <span class="px-3 py-1.5 bg-red-600 text-white rounded-md text-sm font-semibold shadow">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md text-sm">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($newsList->hasMorePages())
                        <a href="{{ $newsList->nextPageUrl() }}"
                           class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md text-sm">
                            Вперёд →
                        </a>
                    @else
                        <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            Вперёд →
                        </span>
                    @endif
                </nav>
            </div>
        @endif
    @else
        <p class="text-center text-gray-500 dark:text-gray-400">Нет уроков по HTML.</p>
    @endif
</div>
