<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Новости' }}
    </h2>

    @if ($newsList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($newsList as $news)
                @php
                    $imgSrc = null;
                    $isVideo = false;

                    if ($news->cover) {
                        $imgSrc = asset('storage/' . $news->cover);
                        $isVideo = \Illuminate\Support\Str::endsWith($imgSrc, ['.mp4', '.webm']);
                    } elseif (preg_match('/<video[^>]*>.*?<source[^>]+src="([^">]+)"/is', $news->content, $match)) {
                        $imgSrc = $match[1];
                        $isVideo = true;
                    } elseif (preg_match('/<source[^>]+src="([^">]+)"/i', $news->content, $match)) {
                        $imgSrc = $match[1];
                        $isVideo = true;
                    } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)) {
                        $imgSrc = $imgMatch[1];
                    }

                    $imgSrc = $imgSrc ?: asset('images/no-image.png');
                @endphp

                <div
                    class="news-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-200 shadow-md hover:shadow-xl transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- 📰 Бейдж "НОВОСТЬ" --}}
                    <div
                        class="absolute -top-3 right-3 z-10 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                        📰 NEWS
                    </div>

                    {{-- 🏷️ Категории --}}
                    @if ($news->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($news->categories as $category)
                                <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                                    class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Обложка --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline>
                                <source src="{{ $imgSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $imgSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
                        @endif
                    </div>

                    {{-- Заголовок --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight max-h-14 overflow-hidden">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- 📅 Дата --}}
                    <p class="text-sm text-gray-500 mb-2">
                        📅 {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- 🧾 Краткое содержание --}}
                    <div class="text-sm text-gray-600 mb-3 line-clamp-4">
                        💬 {!! Str::limit(strip_tags($news->content), 220) !!}
                    </div>

                    {{-- 🔘 Кнопка --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                        class="mt-auto block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>

        {{-- 📄 Пагинация --}}
        {{-- 📄 Пагинация --}}
        @if ($newsList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2">
                {{-- ℹ️ Инфо о записях --}}
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Показано с <span class="font-semibold">{{ $newsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $newsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $newsList->total() }}</span> записей
                </div>

                {{-- Навигация --}}
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    {{-- Назад --}}
                    @if ($newsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            ← Назад
                        </span>
                    @else
                        <a href="{{ $newsList->previousPageUrl() }}"
                            class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition">
                            ← Назад
                        </a>
                    @endif

                    {{-- Номера страниц --}}
                    @foreach ($newsList->getUrlRange(1, $newsList->lastPage()) as $page => $url)
                        @if ($page == $newsList->currentPage())
                            <span class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm font-semibold shadow">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                                class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Вперёд --}}
                    @if ($newsList->hasMorePages())
                        <a href="{{ $newsList->nextPageUrl() }}"
                            class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition">
                            Вперёд →
                        </a>
                    @else
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            Вперёд →
                        </span>
                    @endif
                </div>
            </div>
        @endif
    @else
        <p class="text-center text-gray-500">Нет опубликованных новостей.</p>
    @endif
</div>
