<div class="my-12 max-w-screen-xl mx-auto px-4">
    {{-- Заголовок с иконкой --}}
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight flex items-center justify-center gap-2 select-none">
        <i class="fas fa-code text-green-600"></i>
        {{ $title ?? 'Уроки по PHP' }}
    </h2>

    @if ($newsList->count())
        {{-- Контейнер карточек: до 3 в ряд, до 12 на странице --}}
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

                {{-- Карточка урока --}}
                <div class="relative flex flex-col p-5 border border-gray-100 hover:border-gray-300 shadow-md hover:shadow-lg transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- 📘 бейдж "Урок" --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-green-600 text-green-600 text-xs font-bold px-3 py-1 rounded-full shadow-md select-none">
                        📘 PHP
                    </div>

                    {{-- Категории --}}
                    @if ($news->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($news->categories as $category)
                                <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                                   class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline select-none">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Обложка или видео --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline controls>
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Заголовок --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight break-words line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-green-600 transition">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- Дата --}}
                    <p class="text-sm text-gray-500 mb-2 flex items-center gap-1">
                        <i class="far fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Превью контента --}}
                    <div class="text-sm text-gray-600 mb-3 line-clamp-4">
                        💬 {!! Str::limit(strip_tags($news->content), 220) !!}
                    </div>

                    {{-- Кнопка --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-auto block text-center text-sm bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Пагинация --}}
        @if ($newsList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2">
                <div class="text-sm text-gray-500">
                    Показано с <span class="font-semibold">{{ $newsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $newsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $newsList->total() }}</span> записей
                </div>

                <nav class="flex items-center space-x-2 rtl:space-x-reverse" role="navigation">
                    @if ($newsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            ← Назад
                        </span>
                    @else
                        <a href="{{ $newsList->previousPageUrl() }}"
                           class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm">
                            ← Назад
                        </a>
                    @endif

                    @foreach ($newsList->getUrlRange(1, $newsList->lastPage()) as $page => $url)
                        @if ($page == $newsList->currentPage())
                            <span class="px-3 py-1.5 bg-green-600 text-white rounded-md text-sm font-semibold shadow">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($newsList->hasMorePages())
                        <a href="{{ $newsList->nextPageUrl() }}"
                           class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm">
                            Вперёд →
                        </a>
                    @else
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            Вперёд →
                        </span>
                    @endif
                </nav>
            </div>
        @endif
    @else
        <p class="text-center text-gray-500">Нет уроков по PHP.</p>
    @endif
</div>
