
<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Товары' }}
    </h2>

    @if ($newsList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($newsList as $news)
                @php
                    $imgSrc = null;
                    if ($news->cover) {
                        $imgSrc = asset('storage/' . $news->cover);
                    } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)) {
                        $imgSrc = $imgMatch[1];
                    }
                    $imgSrc = $imgSrc ?: asset('images/no-image.png');

                    $price = $news->price ?? null;
                    $stock = $news->stock ?? null;
                    $isPromo = $news->is_promo ?? false;
                @endphp

                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-5 flex flex-col relative border border-gray-100 hover:border-gray-200 max-w-xs w-full">
                    @if ($isPromo)
                        <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-red-600 text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                            🔥 АКЦИЯ
                        </div>
                    @endif

                    <div class="pt-6">
                        <img src="{{ $imgSrc }}" alt="{{ $news->title }}"
                             class="mb-4 rounded-xl h-48 w-full object-cover border border-gray-200">
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-1 line-clamp-2 leading-tight">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                            {{ $news->title }}
                        </a>
                    </h3>

                    <p class="text-sm text-gray-500 mb-2">📅 {{ $news->created_at->format('d.m.Y') }}</p>

                    <div class="text-sm text-gray-600 mb-4 line-clamp-3">
                        {!! Str::limit(strip_tags($news->content), 160) !!}
                    </div>

                    @if ($price || !is_null($stock))
                        <div class="mb-4 space-y-2 text-sm">
                            @if ($price)
                                <div class="bg-green-100 text-green-900 font-medium px-3 py-1.5 rounded-md shadow-sm">
                                    💰 <strong>Цена:</strong> {{ number_format($price, 2, ',', ' ') }} ₽
                                </div>
                            @endif
                            @if (!is_null($stock))
                                <div class="bg-yellow-100 text-yellow-900 font-medium px-3 py-1.5 rounded-md shadow-sm">
                                    📦 <strong>Осталось:</strong> {{ $stock }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-auto flex gap-3">
                        <a href="#" class="flex-1 text-sm text-center bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-2.5 rounded-lg transition shadow">
                            🛒 В корзину
                        </a>
                        <a href="{{ route('news.show', $news->slug) }}" class="flex-1 text-sm text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                            Подробнее →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Нет товаров.</p>
    @endif
</div>
