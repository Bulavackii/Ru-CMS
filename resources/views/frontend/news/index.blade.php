@extends('layouts.frontend')

@section('title', 'Новости')

@section('content')
    <div class="my-12 max-w-screen-xl mx-auto px-4">
        <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
            {{ $title ?? 'Новости' }}
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
                        } elseif (preg_match('/<source[^>]+src="([^">]+)"/i', $news->content, $sourceMatch)) {
                            $imgSrc = $sourceMatch[1];
                        }
                        $imgSrc = $imgSrc ?: asset('images/no-image.png');
                    @endphp

                    <div class="news-card">
                        <div class="news-badge">📢 Новость</div>

                        <div class="w-full h-40 overflow-hidden mb-4 rounded-xl border border-gray-200">
                            @if (Str::endsWith($imgSrc, ['.mp4', '.webm']))
                                <video controls class="w-full h-full object-cover rounded-xl">
                                    <source src="{{ $imgSrc }}" type="video/mp4">
                                    Ваш браузер не поддерживает видео.
                                </video>
                            @else
                                <img src="{{ $imgSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
                            @endif
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 mb-2 leading-snug line-clamp-2">
                            <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                                {{ $news->title }}
                            </a>
                        </h3>

                        <div class="text-sm text-gray-500 mb-1">
                            📅 {{ $news->created_at->format('d.m.Y') }}
                        </div>

                        <div class="text-sm text-gray-600 mb-2">
                            Категории:
                            @forelse ($news->categories as $category)
                                <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                                    {{ $category->title }}
                                </a>{{ !$loop->last ? ',' : '' }}
                            @empty
                                <span class="text-gray-400">Без категории</span>
                            @endforelse
                        </div>

                        <div class="text-sm text-gray-700 mb-4 leading-relaxed line-clamp-4">
                            {!! Str::limit(strip_tags($news->content), 180) !!}
                        </div>

                        <a href="{{ route('news.show', $news->slug) }}" class="text-sm text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow mt-auto block">
                            Читать далее →
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-gray-500">Нет опубликованных новостей.</p>
        @endif

        <div class="mt-8">
            {{ $newsList->withQueryString()->links() }}
        </div>
    </div>
@endsection
