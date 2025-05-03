@props([
    'newsList' => [],
    'title' => 'Блок',
])

<section class="mb-12">
    <h2 class="text-2xl font-bold text-center mb-6">{{ $title }}</h2>

    @if (count($newsList))
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($newsList as $news)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-4 flex flex-col">
                    @php
                        $imgSrc = null;

                        if ($news->cover) {
                            $imgSrc = asset('storage/' . $news->cover);
                        } else {
                            preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch);
                            if (!empty($imgMatch[1])) {
                                $imgSrc = $imgMatch[1];
                            } else {
                                preg_match('/<video[^>]+src="([^">]+)"/i', $news->content, $videoMatch);
                                if (!empty($videoMatch[1])) {
                                    $imgSrc = $videoMatch[1];
                                } else {
                                    preg_match('/<source[^>]+src="([^">]+)"/i', $news->content, $sourceMatch);
                                    $imgSrc = $sourceMatch[1] ?? null;
                                }
                            }
                        }
                    @endphp

                    @if ($imgSrc)
                        @if (Str::endsWith($imgSrc, ['.mp4', '.webm']))
                            <video controls class="mb-3 rounded-md max-h-48 w-full object-cover">
                                <source src="{{ $imgSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $imgSrc }}" alt="{{ $news->title }}"
                                class="mb-3 rounded-md max-h-48 w-full object-cover">
                        @endif
                    @endif

                    <h3 class="text-lg font-semibold mb-1">
                        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline">
                            {{ $news->title }}
                        </a>
                    </h3>

                    <p class="text-sm text-gray-500 mb-2">
                        {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    <div class="text-sm text-gray-700 mb-4 overflow-hidden max-h-32 relative">
                        <div class="absolute bottom-0 left-0 w-full h-8 bg-gradient-to-t from-gray-100 to-transparent"></div>
                        {!! Str::limit(strip_tags($news->content), 300) !!}
                    </div>

                    <a href="{{ route('news.show', $news->slug) }}"
                        class="mt-auto text-blue-600 hover:underline text-sm font-medium">
                        Читать далее →
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center">Нет записей.</p>
    @endif
</section>
