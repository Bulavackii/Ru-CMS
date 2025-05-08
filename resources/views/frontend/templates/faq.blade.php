<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Часто задаваемые вопросы' }}
    </h2>

    @php $faqList = $templates['faq'] ?? collect(); @endphp

    @if ($faqList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($faqList as $faq)
                @php
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

                <div class="faq-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-200 shadow-md hover:shadow-xl transition-all bg-white rounded-2xl max-w-xs w-full">

                    {{-- ❓ Бейдж "FAQ" --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                        ❓ FAQ
                    </div>

                    {{-- 🏷️ Категории (левый верх, внутри карточки) --}}
                    @if ($faq->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($faq->categories as $category)
                                <a href="{{ url('/?category_faq=' . $category->id) }}"
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
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="{{ $faq->title }}" class="w-full h-full object-cover">
                        @endif
                    </div>

                    {{-- Вопрос --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight max-h-14 overflow-hidden">
                        <a href="{{ route('news.show', $faq->slug) }}" class="hover:text-blue-600 transition">
                            {{ $faq->title }}
                        </a>
                    </h3>

                    {{-- 📅 Дата --}}
                    <p class="text-sm text-gray-500 mb-2">
                        📅 {{ $faq->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Ответ --}}
                    <div class="text-sm text-gray-700 mb-3 line-clamp-4">
                        💬 {!! Str::limit(strip_tags($faq->content), 200) !!}
                    </div>

                    {{-- Кнопка --}}
                    <div class="mt-auto">
                        <a href="{{ route('news.show', $faq->slug) }}"
                           class="block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                            Подробнее →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Нет вопросов.</p>
    @endif
</div>
