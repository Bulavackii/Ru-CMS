<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Товары' }}
    </h2>

    @if ($newsList->count())
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

                    $isVideo = Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                    $price = $news->price ?? null;
                    $stock = $news->stock ?? null;
                    $isPromo = $news->is_promo ?? false;
                @endphp

                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-5 flex flex-col relative border border-gray-100 hover:border-gray-200 max-w-xs w-full">

                    {{-- 🔥 Бейдж "Акция" --}}
                    @if ($isPromo)
                        <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-red-600 text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                            🔥 АКЦИЯ
                        </div>
                    @endif

                    {{-- 🎥 Обложка: видео или картинка --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline>
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
                        @endif
                    </div>

                    {{-- 🏷️ Заголовок --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 line-clamp-2 leading-tight">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- 📅 Дата --}}
                    <p class="text-sm text-gray-500 mb-2">
                        📅 {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- 🧾 Описание --}}
                    <div class="text-sm text-gray-600 mb-4 line-clamp-3">
                        {!! Str::limit(strip_tags($news->content), 160) !!}
                    </div>

                    {{-- 💰 Цена и 📦 Остаток --}}
                    @if ($price || !is_null($stock))
                        <div class="flex flex-col items-end space-y-2 text-sm mb-4 mt-2">
                            @if ($price)
                                <div class="inline-flex items-center gap-2 bg-green-50 text-green-800 px-3 py-1.5 rounded-md shadow-sm font-medium">
                                    💰 <span class="text-sm">Цена:</span>
                                    <span class="font-semibold text-base">{{ number_format($price, 2, ',', ' ') }} ₽</span>
                                </div>
                            @endif
                            @if (!is_null($stock))
                                <div class="inline-flex items-center gap-2 bg-yellow-50 text-yellow-800 px-3 py-1.5 rounded-md shadow-sm font-medium">
                                    📦 <span class="text-sm">Осталось:</span>
                                    <span class="font-semibold">{{ $stock }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- 🔘 Кнопки --}}
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
