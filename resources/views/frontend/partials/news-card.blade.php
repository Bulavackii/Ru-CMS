<div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-4 flex flex-col h-full">
    {{-- Обложка, либо первая картинка/видео из контента --}}
    @if ($news->cover)
        <img src="{{ asset('storage/' . $news->cover) }}" alt="{{ $news->title }}"
            class="mb-3 rounded-md max-h-48 w-full object-cover">
    @else
        @php
            preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch);
            preg_match('/<video[^>]+src="([^">]+)"/i', $news->content, $videoMatch);
        @endphp

        @if (!empty($imgMatch[1]))
            <img src="{{ $imgMatch[1] }}" alt="{{ $news->title }}" class="mb-3 rounded-md max-h-48 w-full object-cover">
        @elseif (!empty($videoMatch[1]))
            <video src="{{ $videoMatch[1] }}" controls class="mb-3 rounded-md max-h-48 w-full object-cover">
                Ваш браузер не поддерживает видео.
            </video>
        @endif
    @endif

    {{-- Заголовок --}}
    <h3 class="text-lg font-semibold mb-2">
        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:underline">
            {{ $news->title }}
        </a>
    </h3>

    {{-- Категории --}}
    <p class="text-gray-600 text-sm mb-2">
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

    {{-- Краткий текст — тянется вниз, даже если обложки нет --}}
    <div class="text-sm text-gray-700 mb-4 flex-grow">
        {!! Str::of(strip_tags($news->content))->limit(250, '...') !!}
    </div>

    {{-- Кнопка читать --}}
    <a href="{{ route('news.show', $news->slug) }}" class="mt-auto text-blue-600 hover:underline text-sm font-medium">
        Читать далее →
    </a>
</div>
