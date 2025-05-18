<div class="my-12 max-w-screen-xl mx-auto px-4">
    {{-- Заголовок раздела с иконкой новости --}}
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight flex items-center justify-center gap-2 select-none">
        <i class="fas fa-newspaper text-blue-600"></i>
        {{ $title ?? 'Новости' }}
    </h2>

    @if ($newsList->count())
        {{-- Контейнер карточек новостей: flex с переносом и отступами --}}
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($newsList as $news)
                @php
                    // Определяем источник медиа для карточки: обложка, видео в контенте или дефолтное изображение
                    $mediaSrc = $news->cover
                        ? asset('storage/' . $news->cover) // Если есть обложка, берём из storage
                        : (
                            preg_match('/<video[^>]*src="([^"]+)"/i', $news->content, $videoMatch)
                                ? $videoMatch[1] // Если есть видео тег в контенте — берём ссылку
                                : (
                                    preg_match('/<source[^>]*src="([^"]+)"/i', $news->content, $sourceMatch)
                                        ? $sourceMatch[1] // Или источник видео
                                        : (
                                            preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)
                                                ? $imgMatch[1] // Или первая картинка из контента
                                                : asset('images/no-image.png') // Иначе - заглушка
                                        )
                                )
                        );
                    // Проверяем, видео ли это по расширению файла
                    $isVideo = \Illuminate\Support\Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                @endphp

                {{-- Карточка новости --}}
                <div class="news-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-300 shadow-md hover:shadow-lg transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- 📰 Бейдж "NEWS" в правом верхнем углу --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none" title="Новости">
                        📰 NEWS
                    </div>

                    {{-- Категории слева сверху --}}
                    @if ($news->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($news->categories as $category)
                                <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                                   class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline select-none" title="{{ $category->title }}">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Обложка или видео --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            {{-- Видео с контролами --}}
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline controls>
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                {{-- fallback текст --}}
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            {{-- Статичное изображение --}}
                            <img src="{{ $mediaSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Заголовок новости с ссылкой --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight break-words break-all line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition" title="{{ $news->title }}">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- 📅 Дата публикации с иконкой --}}
                    <p class="text-sm text-gray-500 mb-2 flex items-center gap-1 select-none" title="Дата публикации">
                        <i class="far fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Краткое содержание (ограничено 220 символами, HTML удалён) --}}
                    <div class="text-sm text-gray-600 mb-3 line-clamp-4 break-words break-all" title="Превью новости">
                        💬 {!! Str::limit(strip_tags($news->content), 220) !!}
                    </div>

                    {{-- Кнопка "Читать далее" --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-auto block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow select-none" aria-label="Читать подробнее новость {{ $news->title }}">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Пагинация --}}
        @if ($newsList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2 select-none" aria-label="Пагинация новостей">
                {{-- Информация о диапазоне отображаемых записей --}}
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Показано с <span class="font-semibold">{{ $newsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $newsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $newsList->total() }}</span> записей
                </div>

                {{-- Навигация по страницам --}}
                <nav class="flex items-center space-x-2 rtl:space-x-reverse" role="navigation" aria-label="Навигация по страницам">
                    {{-- Кнопка Назад --}}
                    @if ($newsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed" aria-disabled="true">
                            ← Назад
                        </span>
                    @else
                        <a href="{{ $newsList->previousPageUrl() }}"
                           class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition"
                           rel="prev" aria-label="Предыдущая страница">
                            ← Назад
                        </a>
                    @endif

                    {{-- Номера страниц --}}
                    @foreach ($newsList->getUrlRange(1, $newsList->lastPage()) as $page => $url)
                        @if ($page == $newsList->currentPage())
                            <span class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm font-semibold shadow" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition"
                               aria-label="Страница {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Кнопка Вперёд --}}
                    @if ($newsList->hasMorePages())
                        <a href="{{ $newsList->nextPageUrl() }}"
                           class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition"
                           rel="next" aria-label="Следующая страница">
                            Вперёд →
                        </a>
                    @else
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed" aria-disabled="true">
                            Вперёд →
                        </span>
                    @endif
                </nav>
            </div>
        @endif
    @else
        {{-- Сообщение, если новостей нет --}}
        <p class="text-center text-gray-500 select-none">Нет опубликованных новостей.</p>
    @endif
</div>
