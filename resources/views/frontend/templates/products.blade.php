@php
    $title = $title ?? 'Товары';
@endphp

<div class="mb-10">
    <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">{{ $title }}</h2>

    <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($newsList as $news)
            <div class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition">
                {{-- Обложка / картинка --}}
                @php
                    $imgSrc = null;
                    if ($news->cover) {
                        $imgSrc = asset('storage/' . $news->cover);
                    } else {
                        preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch);
                        $imgSrc = $imgMatch[1] ?? null;
                    }
                @endphp

                @if ($imgSrc)
                    <img src="{{ $imgSrc }}" alt="{{ $news->title }}"
                         class="h-48 w-full object-cover">
                @endif

                <div class="p-4 flex flex-col flex-grow">
                    {{-- Заголовок --}}
                    <h3 class="text-lg font-semibold text-gray-800 mb-1 truncate">
                        {{ $news->title }}
                    </h3>

                    {{-- Дата или цена (можешь заменить или расширить) --}}
                    <div class="text-sm text-gray-500 mb-2">
                        {{ $news->created_at->format('d.m.Y') }}
                    </div>

                    {{-- Краткое описание --}}
                    <div class="text-sm text-gray-700 flex-grow overflow-hidden max-h-24 mb-4">
                        {!! Str::limit(strip_tags($news->content), 120) !!}
                    </div>

                    {{-- Кнопка --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded mt-auto">
                        Подробнее
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
