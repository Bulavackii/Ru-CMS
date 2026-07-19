{{--
    Виджет отзывов
    Можно встроить в любое место проекта через:
    @include('Reviews::frontend.widget', ['itemId' => $product_id, 'itemType' => 'product', 'showForm' => true])

    Или через API:
    <div id="reviews-widget"></div>
    <script>
        fetch('/api/reviews/widget?item_id={{ $product_id }}&item_type=product&blade=1')
            .then(r => r.text())
            .then(html => document.getElementById('reviews-widget').innerHTML = html);
    </script>
--}}

<div class="reviews-widget" id="reviews-widget-{{ $itemId }}-{{ $itemType }}">
    {{-- Статистика --}}
    <div class="reviews-stats mb-4">
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold">{{ $stats['average'] ?? 0 }}</span>
            <span class="text-yellow-500">⭐</span>
            <span class="text-gray-600">из 5</span>
            <span class="text-gray-500">({{ $stats['count'] ?? 0 }} отзывов)</span>
        </div>

        @if(isset($stats['distribution']))
        <div class="mt-2 space-y-1">
            @foreach([5,4,3,2,1] as $rating)
                @if(isset($stats['distribution'][$rating]))
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-4">{{ $rating }}⭐</span>
                    <div class="flex-1 bg-gray-200 rounded h-2">
                        <div class="bg-yellow-500 h-2 rounded"
                             style="width: {{ ($stats['distribution'][$rating] / $stats['count']) * 100 }}%"></div>
                    </div>
                    <span class="w-8 text-right">{{ $stats['distribution'][$rating] }}</span>
                </div>
                @endif
            @endforeach
        </div>
        @endif
    </div>

    {{-- Список отзывов --}}
    @if($reviews->count() > 0)
    <div class="reviews-list space-y-4 mb-4">
        @foreach($reviews as $review)
        <div class="review-item border-b pb-3">
            <div class="flex items-start justify-between">
                <div>
                    <div class="font-semibold">
                        {{ $review->name ?? $review->user?->name ?? 'Гость' }}
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $review->created_at->format('d.m.Y') }}
                    </div>
                </div>
                <div class="text-yellow-600 font-bold">{{ $review->rating }}⭐</div>
            </div>

            @if($review->title)
            <div class="font-semibold mt-1">{{ $review->title }}</div>
            @endif

            <div class="text-gray-700 mt-1">{{ $review->content }}</div>

            {{-- Ответы --}}
            @if($review->children->count() > 0)
            <div class="ml-4 mt-2 space-y-2">
                @foreach($review->children as $reply)
                <div class="bg-gray-50 p-2 rounded text-sm">
                    <div class="font-semibold text-gray-600">
                        {{ $reply->user?->name ?? 'Менеджер' }}
                        <span class="text-xs text-gray-500">{{ $reply->created_at->format('d.m.Y') }}</span>
                    </div>
                    <div>{{ $reply->content }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center text-gray-500 py-4">
        Отзывов пока нет. Будьте первым!
    </div>
    @endif

    {{-- Форма отзыва --}}
    @if($showForm ?? true)
    <div class="review-form mt-4 p-4 bg-gray-50 rounded">
        <h3 class="font-bold mb-3">Оставить отзыв</h3>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-3">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 text-red-800 p-2 rounded mb-3">{{ session('error') }}</div>
        @endif

        <form action="{{ route('reviews.submit', ['itemId' => $itemId, 'itemType' => $itemType]) }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Имя *</label>
                    <input type="text" name="name" required
                           value="{{ old('name') }}"
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email *</label>
                    <input type="email" name="email" required
                           value="{{ old('email') }}"
                           class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Оценка *</label>
                <div class="flex gap-2">
                    @foreach([1,2,3,4,5] as $rating)
                    <label class="cursor-pointer">
                        <input type="radio" name="rating" value="{{ $rating }}"
                               {{ old('rating') == $rating ? 'checked' : '' }}
                               class="peer sr-only">
                        <span class="text-2xl peer-checked:text-yellow-500 text-gray-300">⭐</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Заголовок</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Отзыв *</label>
                <textarea name="content" required rows="4"
                          class="w-full border rounded px-3 py-2">{{ old('content') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Каптча *</label>
                <input type="text" name="captcha" required
                       placeholder="Введите код с картинки"
                       class="w-full border rounded px-3 py-2">
                <div class="mt-2">
                    {!! captcha_img() !!}
                </div>
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                Отправить отзыв
            </button>
        </form>
    </div>
    @endif
</div>

<style>
.reviews-widget {
    font-family: inherit;
}

.reviews-widget .review-item:last-child {
    border-bottom: none;
}

.reviews-widget input[type="radio"]:checked + span {
    color: #f59e0b;
}
</style>
