<div class="my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
    {{-- Заголовок раздела с иконкой --}}
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight flex items-center justify-center gap-2">
        <i class="fas fa-comments text-yellow-600"></i>
        {{ $title ?? 'Отзывы клиентов' }}
    </h2>

    @php
        // Получаем коллекцию отзывов из шаблонов (если есть)
        $reviewsList = $templates['reviews'] ?? collect();
    @endphp

    @if ($reviewsList->count())
        {{-- Контейнер отзывов с flex и отступами --}}
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($reviewsList as $review)
                @php
                    // Определяем источник медиа (обложка, видео или заглушка)
                    $mediaSrc = $review->cover
                        ? asset('storage/' . $review->cover)
                        : (
                            preg_match('/<video[^>]*src="([^"]+)"/i', $review->content, $videoMatch)
                                ? $videoMatch[1]
                                : (
                                    preg_match('/<source[^>]*src="([^"]+)"/i', $review->content, $sourceMatch)
                                        ? $sourceMatch[1]
                                        : (
                                            preg_match('/<img[^>]+src="([^">]+)"/i', $review->content, $imgMatch)
                                                ? $imgMatch[1]
                                                : asset('images/no-image.png')
                                        )
                                )
                        );
                    $isVideo = \Illuminate\Support\Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                @endphp

                {{-- Карточка отзыва --}}
                <div class="review-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-300 shadow-md hover:shadow-lg transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- 💬 Бейдж справа сверху --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-yellow-500 text-yellow-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none">
                        💬 REVIEWS
                    </div>

                    {{-- Категории слева сверху --}}
                    @if ($review->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($review->categories as $category)
                                <a href="{{ url('/?category_reviews=' . $category->id) }}"
                                   class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline select-none">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Обложка или видео --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" controls preload="metadata">
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="Фото отзыва" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Автор и дата --}}
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-2 select-none">
                        <span class="font-semibold text-gray-900 break-words break-all max-w-[60%]">
                            👤 {{ $review->author ?? 'Аноним' }}
                        </span>
                        <span>📅 {{ $review->created_at->format('d.m.Y') }}</span>
                    </div>

                    {{-- Краткий текст отзыва --}}
                    <div class="text-sm text-gray-700 mb-3 line-clamp-4 break-words break-all">
                        💬 {!! Str::limit(strip_tags($review->content), 200) !!}
                    </div>

                    {{-- ⭐ Рейтинг, если есть --}}
                    @if (!empty($review->rating))
                        <div class="bg-yellow-100 text-yellow-900 text-sm font-semibold px-3 py-1 rounded-full w-fit select-none">
                            ⭐ Оценка: {{ $review->rating }}/5
                        </div>
                    @endif

                    {{-- Кнопка "Читать далее" --}}
                    <a href="{{ route('news.show', $review->slug) }}"
                       class="mt-3 block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow select-none">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>

        {{-- 📄 Пагинация --}}
        @if ($reviewsList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2 select-none">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Показано с <span class="font-semibold">{{ $reviewsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $reviewsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $reviewsList->total() }}</span> отзывов
                </div>

                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    {{-- Кнопка Назад --}}
                    @if ($reviewsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            ← Назад
                        </span>
                    @else
                        <a href="{{ $reviewsList->previousPageUrl() }}"
                           class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition">
                            ← Назад
                        </a>
                    @endif

                    {{-- Номера страниц --}}
                    @foreach ($reviewsList->getUrlRange(1, $reviewsList->lastPage()) as $page => $url)
                        @if ($page == $reviewsList->currentPage())
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

                    {{-- Кнопка Вперёд --}}
                    @if ($reviewsList->hasMorePages())
                        <a href="{{ $reviewsList->nextPageUrl() }}"
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
        {{-- Сообщение если отзывов нет --}}
        <p class="text-center text-gray-500 select-none">Пока нет отзывов.</p>
    @endif
</div>
