@php
    $imgSrc = null;

    // 1. Обложка (загруженное изображение)
    if ($news->cover) {
        $imgSrc = asset('storage/' . $news->cover);
    } else {
        // 2. Встроенное <img>
        preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch);
        if (!empty($imgMatch[1])) {
            $imgSrc = $imgMatch[1];
        } else {
            // 3. <video src=...>
            preg_match('/<video[^>]+src="([^">]+)"/i', $news->content, $videoMatch);
            if (!empty($videoMatch[1])) {
                $imgSrc = $videoMatch[1];
            } else {
                // 4. <source src=...>
                preg_match('/<source[^>]+src="([^">]+)"/i', $news->content, $sourceMatch);
                $imgSrc = $sourceMatch[1] ?? null;

                // 5. Ссылка на YouTube
                if (!$imgSrc) {
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $news->content, $ytMatch);
                    if (!empty($ytMatch[1])) {
                        $youtubeId = $ytMatch[1];
                        $imgSrc = "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg";
                    }
                }
            }
        }
    }
@endphp

<div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-4 flex flex-col">
    {{-- Превью изображения или видео --}}
    @if ($imgSrc)
        @if (Str::endsWith($imgSrc, ['.mp4', '.webm']))
            <video controls class="mb-3 rounded-md max-h-48 w-full object-cover">
                <source src="{{ $imgSrc }}" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
        @else
            <img src="{{ $imgSrc }}" alt="{{ $news->title }}" class="mb-3 rounded-md max-h-48 w-full object-cover">
        @endif
    @endif

    {{-- Остальная часть карточки — без изменений --}}
    <h2 class="text-lg font-semibold mb-1">
        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline">
            {{ $news->title }}
        </a>
    </h2>

    <p class="text-sm text-gray-500 mb-2">{{ $news->created_at->format('d.m.Y') }}</p>

    <p class="text-gray-600 text-sm mb-2">
        Категории:
        @forelse ($news->categories as $category)
            <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                {{ $category->title }}
            </a>@if (!$loop->last),@endif
        @empty
            <span class="text-gray-400">Без категории</span>
        @endforelse
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
