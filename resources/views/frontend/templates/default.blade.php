<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Новости' }}
    </h2>

    @if ($newsList->isNotEmpty())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($newsList as $news)
                @php
                    $imgSrc = null;
                    $isVideo = false;

                    if ($news->cover) {
                        $imgSrc = asset('storage/' . $news->cover);
                        $isVideo = \Illuminate\Support\Str::endsWith($imgSrc, ['.mp4', '.webm']);
                    } elseif (preg_match('/<video[^>]*>.*?<source[^>]+src="([^">]+)"/is', $news->content, $match)) {
                        $imgSrc = $match[1];
                        $isVideo = true;
                    } elseif (preg_match('/<source[^>]+src="([^">]+)"/i', $news->content, $match)) {
                        $imgSrc = $match[1];
                        $isVideo = true;
                    } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)) {
                        $imgSrc = $imgMatch[1];
                    }

                    $imgSrc = $imgSrc ?: asset('images/no-image.png');
                @endphp

                <div class="news-card relative flex flex-col border border-gray-100 hover:border-gray-200 p-5 shadow-md hover:shadow-xl transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- ✅ Мигающий бейдж "НОВОСТЬ" --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                        📰 НОВОСТЬ
                    </div>

                    <div class="w-full h-40 overflow-hidden mb-4 rounded-xl border border-gray-200">
                        @if ($isVideo)
                            <video controls class="w-full h-full object-cover rounded-xl">
                                <source src="{{ $imgSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $imgSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
                        @endif
                    </div>

                    <h3 class="text-xl font-semibold text-gray-900 mb-1 line-clamp-2 leading-tight">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                            {{ $news->title }}
                        </a>
                    </h3>

                    <p class="text-sm text-gray-500 mb-2">📅 {{ $news->created_at->format('d.m.Y') }}</p>

                    <div class="text-sm text-gray-700 mb-2">
                        Категории:
                        @forelse ($news->categories as $category)
                            <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                                {{ $category->title }}
                            </a>@if (!$loop->last),@endif
                        @empty
                            <span class="text-gray-400">Без категории</span>
                        @endforelse
                    </div>

                    <div class="text-sm text-gray-600 mb-4 line-clamp-3">
                        {!! Str::limit(strip_tags($news->content), 200) !!}
                    </div>

                    <a href="{{ route('news.show', $news->slug) }}" class="mt-auto text-sm text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Нет опубликованных новостей.</p>
    @endif
</div>
