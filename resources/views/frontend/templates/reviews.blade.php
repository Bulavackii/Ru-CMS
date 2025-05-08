<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Отзывы клиентов' }}
    </h2>

    @php $reviewsList = $templates['reviews'] ?? collect(); @endphp

    @if ($reviewsList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($reviewsList as $review)
                @php
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
                    $isVideo = Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                @endphp

                <div class="review-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-200 shadow-md hover:shadow-xl transition-all bg-white rounded-2xl max-w-xs w-full">

                    {{-- 💬 Бейдж "REVIEWS" — жёлтая окантовка --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-yellow-500 text-yellow-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                        💬 REVIEWS
                    </div>

                    {{-- Категории (левый верх) --}}
                    @if ($review->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($review->categories as $category)
                                <a href="{{ url('/?category_reviews=' . $category->id) }}"
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
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="Фото отзыва" class="w-full h-full object-cover rounded-xl">
                        @endif
                    </div>

                    {{-- Автор и дата --}}
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-2">
                        <span class="font-semibold text-gray-900">👤 {{ $review->author ?? 'Аноним' }}</span>
                        <span>📅 {{ $review->created_at->format('d.m.Y') }}</span>
                    </div>

                    {{-- Контент --}}
                    <div class="text-sm text-gray-700 mb-3 line-clamp-4">
                        {!! Str::limit(strip_tags($review->content), 200) !!}
                    </div>

                    {{-- ⭐ Рейтинг --}}
                    @if (!empty($review->rating))
                        <div class="bg-yellow-100 text-yellow-900 text-sm font-semibold px-3 py-1 rounded-full w-fit">
                            ⭐ Оценка: {{ $review->rating }}/5
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Пока нет отзывов.</p>
    @endif
</div>
