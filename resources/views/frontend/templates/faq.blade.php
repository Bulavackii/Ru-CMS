<div class="my-12 max-w-screen-xl mx-auto px-4">
    {{-- Заголовок раздела с иконкой вопроса --}}
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight flex items-center justify-center gap-2">
        <i class="fas fa-question-circle text-blue-600"></i>
        {{ $title ?? 'Часто задаваемые вопросы' }}
    </h2>

    @php
        // Получаем коллекцию FAQ из шаблонов (если есть)
        $faqList = $templates['faq'] ?? collect();
    @endphp

    @if ($faqList->count())
        {{-- Контейнер карточек FAQ с flex и промежутками --}}
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($faqList as $faq)
                @php
                    // Определяем источник медиа (обложка, видео или заглушка)
                    $mediaSrc = $faq->cover
                        ? asset('storage/' . $faq->cover)
                        : (
                            preg_match('/<video[^>]*src="([^"]+)"/i', $faq->content, $videoMatch)
                                ? $videoMatch[1]
                                : (
                                    preg_match('/<source[^>]*src="([^"]+)"/i', $faq->content, $sourceMatch)
                                        ? $sourceMatch[1]
                                        : (
                                            preg_match('/<img[^>]+src="([^">]+)"/i', $faq->content, $imgMatch)
                                                ? $imgMatch[1]
                                                : asset('images/no-image.png')
                                        )
                                )
                        );
                    $isVideo = Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                @endphp

                {{-- Карточка FAQ --}}
                <div class="faq-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-300 shadow-md hover:shadow-lg transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- ❓ Бейдж FAQ справа сверху --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none">
                        ❓ FAQ
                    </div>

                    {{-- Категории FAQ слева сверху --}}
                    @if ($faq->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($faq->categories as $category)
                                <a href="{{ url('/?category_faq=' . $category->id) }}"
                                   class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline select-none">
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
                            <img src="{{ $mediaSrc }}" alt="{{ $faq->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Заголовок FAQ с ссылкой --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight break-words break-all line-clamp-2">
                        <a href="{{ route('news.show', $faq->slug) }}" class="hover:text-blue-600 transition" title="{{ $faq->title }}">
                            {{ $faq->title }}
                        </a>
                    </h3>

                    {{-- 📅 Дата создания FAQ --}}
                    <p class="text-sm text-gray-500 mb-2 flex items-center gap-1 select-none">
                        <i class="far fa-calendar-alt"></i> {{ $faq->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Краткий ответ, ограниченный по длине --}}
                    <div class="text-sm text-gray-700 mb-3 line-clamp-4 break-words break-all">
                        💬 {!! Str::limit(strip_tags($faq->content), 200) !!}
                    </div>

                    {{-- Кнопка "Подробнее" --}}
                    <div class="mt-auto">
                        <a href="{{ route('news.show', $faq->slug) }}"
                           class="block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow select-none">
                            Подробнее →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 📄 Пагинация --}}
        @if ($faqList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2 select-none">
                {{-- Текст пагинации --}}
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Показано с <span class="font-semibold">{{ $faqList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $faqList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $faqList->total() }}</span> вопросов
                </div>

                {{-- Навигация --}}
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    {{-- Кнопка Назад --}}
                    @if ($faqList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed">
                            ← Назад
                        </span>
                    @else
                        <a href="{{ $faqList->previousPageUrl() }}"
                           class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition">
                            ← Назад
                        </a>
                    @endif

                    {{-- Номера страниц --}}
                    @foreach ($faqList->getUrlRange(1, $faqList->lastPage()) as $page => $url)
                        @if ($page == $faqList->currentPage())
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
                    @if ($faqList->hasMorePages())
                        <a href="{{ $faqList->nextPageUrl() }}"
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
        {{-- Сообщение если вопросов нет --}}
        <p class="text-center text-gray-500">Нет вопросов.</p>
    @endif
</div>
