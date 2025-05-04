<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-bold text-center mb-8">
        {{ $title ?? 'Блок контента' }}
    </h2>

    @if ($newsList->count())
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($newsList as $news)
                <div class="bg-white rounded-xl shadow hover:shadow-lg transition-all p-4 flex flex-col">
                    {{-- Изображение / видео --}}
                    @php
                        $imgSrc = null;
                        if ($news->cover) {
                            $imgSrc = asset('storage/' . $news->cover);
                        } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)) {
                            $imgSrc = $imgMatch[1];
                        }
                    @endphp

                    @if ($imgSrc)
                        <img src="{{ $imgSrc }}" alt="{{ $news->title }}"
                             class="mb-3 rounded-md h-48 w-full object-cover">
                    @endif

                    {{-- Заголовок --}}
                    <h3 class="text-lg font-semibold mb-1 line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- Дата и категории --}}
                    <p class="text-sm text-gray-500 mb-2">{{ $news->created_at->format('d.m.Y') }}</p>
                    <p class="text-sm text-gray-500 mb-2">
                        Категории:
                        @forelse ($news->categories as $category)
                            <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                               class="text-blue-500 hover:underline">
                                {{ $category->title }}
                            </a>@if (!$loop->last), @endif
                        @empty
                            <span class="text-gray-400">Без категории</span>
                        @endforelse
                    </p>

                    {{-- Контент-превью --}}
                    <div class="text-sm text-gray-700 mb-4 line-clamp-4">
                        {!! Str::limit(strip_tags($news->content), 250) !!}
                    </div>

                    {{-- Кнопка --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-auto text-blue-600 hover:underline text-sm font-medium">
                        Подробнее →
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Нет записей в этом разделе.</p>
    @endif
</div>
