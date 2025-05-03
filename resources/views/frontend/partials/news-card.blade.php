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

                if (!$imgSrc) {
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $news->content, $ytMatch);
                    if (!empty($ytMatch[1])) {
                        $youtubeId = $ytMatch[1];
                        $imgSrc = "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg";
                    }
                }
                if (!$imgSrc) {
                    $imgSrc = asset('images/no-image.png');
                }
            }
        }
    }
@endphp

<div
    class="bg-white rounded-lg shadow-md hover:shadow-lg transition flex flex-col w-full h-full max-w-xs mx-auto overflow-hidden">

    {{-- Превью --}}
    <div class="w-full h-48 bg-gray-100 rounded-t-md overflow-hidden flex items-center justify-center">
        @if ($imgSrc)
            @if (Str::endsWith($imgSrc, ['.mp4', '.webm']))
                <video controls class="w-full h-full object-cover">
                    <source src="{{ $imgSrc }}" type="video/mp4">
                    Ваш браузер не поддерживает видео.
                </video>
            @else
                <img src="{{ $imgSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
            @endif
        @endif
    </div>

    {{-- Контент --}}
    <div class="flex flex-col flex-grow p-4 overflow-hidden">

        {{-- Заголовок --}}
        <h2 class="text-lg font-semibold mb-2 h-12 overflow-hidden break-words">
            <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline truncate block w-full"
                title="{{ $news->title }}">
                {{ $news->title }}
            </a>
        </h2>

        {{-- Дата --}}
        <p class="text-sm text-gray-500 mb-1">{{ $news->created_at->format('d.m.Y') }}</p>

        {{-- Категории --}}
        <p class="text-gray-600 text-sm mb-2 break-words">
            Категории:
            @forelse ($news->categories as $category)
                <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                    {{ $category->title }}
                </a>
                @if (!$loop->last)
                    ,
                @endif
            @empty
                <span class="text-gray-400">Без категории</span>
            @endforelse
        </p>

        {{-- Краткое описание --}}
        <div class="text-sm text-gray-700 mb-4 overflow-hidden relative flex-grow max-h-32 leading-relaxed break-words">
            <div
                class="absolute bottom-0 left-0 w-full h-6 bg-gradient-to-t from-white to-transparent pointer-events-none">
            </div>
            {!! Str::limit(strip_tags($news->content), 300) !!}
        </div>

        {{-- Кнопка --}}
        <a href="{{ route('news.show', $news->slug) }}"
            class="mt-auto text-blue-600 hover:underline text-sm font-medium">
            Читать далее →
        </a>
    </div>
</div>
