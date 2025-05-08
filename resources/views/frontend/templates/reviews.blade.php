<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Отзывы клиентов' }}
    </h2>

    @php
        $reviewsList = $templates['reviews'] ?? collect();
    @endphp

    @if ($reviewsList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($reviewsList as $review)
                @php
                    $imgSrc = null;
                    if ($review->cover) {
                        $imgSrc = asset('storage/' . $review->cover);
                    } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $review->content, $imgMatch)) {
                        $imgSrc = $imgMatch[1];
                    }
                    $imgSrc = $imgSrc ?: asset('images/no-image.png');
                @endphp

                <div class="review-card relative flex flex-col border border-gray-100 hover:border-gray-200 p-5 shadow-md hover:shadow-xl transition-all bg-white rounded-2xl max-w-xs w-full">

                    {{-- Анимированный бейдж --}}
                    <div class="review-badge animate-pulse z-10 absolute -top-3 right-3 bg-white border-2 border-emerald-600 text-emerald-600 text-xs font-bold px-3 py-1 rounded-full shadow-md">
                        💬 Отзыв
                    </div>

                    {{-- Изображение --}}
                    <div class="w-full h-40 overflow-hidden mb-4 rounded-xl border border-gray-200">
                        <img src="{{ $imgSrc }}" alt="Фото отзыва" class="w-full h-full object-cover">
                    </div>

                    {{-- Заголовок/автор и дата --}}
                    <div class="review-header mb-2 text-sm text-gray-500 flex justify-between">
                        <span class="font-semibold text-gray-900">👤 {{ $review->author ?? 'Аноним' }}</span>
                        <span>📅 {{ $review->created_at->format('d.m.Y') }}</span>
                    </div>

                    {{-- Текст отзыва --}}
                    <div class="review-content text-gray-700 text-sm mb-3 leading-relaxed">
                        {!! Str::limit(strip_tags($review->content), 180) !!}
                    </div>

                    {{-- Рейтинг --}}
                    @if (!empty($review->rating))
                        <div class="review-rating bg-yellow-100 text-yellow-900 text-sm font-semibold px-3 py-1 rounded-md w-fit">
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
