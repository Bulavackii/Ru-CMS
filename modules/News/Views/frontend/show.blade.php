@extends('layouts.frontend')

@php
    $slug = '/news/' . ltrim((string)$newsItem->slug, '/');

    $seo = null;
    try {
        if (class_exists(\Modules\Seo\Models\SeoPage::class)) {
            $seo = \Modules\Seo\Models\SeoPage::query()->where('slug', $slug)->first();
        }
    } catch (\Throwable $e) {
        $seo = null;
    }

    $pageH1 = trim((string) data_get($seo, 'h1')) !== '' ? (string) $seo->h1 : (string) $newsItem->title;

    $pageTitle       = trim((string) data_get($seo, 'title'))       !== '' ? (string) $seo->title       : ((string) ($newsItem->meta_title ?: $newsItem->title));
    $pageDescription = trim((string) data_get($seo, 'description')) !== '' ? (string) $seo->description : (string) $newsItem->meta_description;
    $pageKeywords    = trim((string) data_get($seo, 'keywords'))    !== '' ? (string) $seo->keywords    : (string) $newsItem->meta_keywords;

    $canonical   = trim((string) data_get($seo, 'canonical')) ?: null;
    $robotsIndex = is_null($seo) ? true : (bool) ($seo->robots_index ?? true);
    $robotsFollow= is_null($seo) ? true : (bool) ($seo->robots_follow ?? true);
@endphp

@section('title', $pageTitle)
@push('meta')
    @if(!empty($pageDescription)) <meta name="description" content="{{ $pageDescription }}"> @endif
    @if(!empty($pageKeywords))    <meta name="keywords" content="{{ $pageKeywords }}"> @endif
    <meta name="robots" content="{{ $robotsIndex ? 'index' : 'noindex' }}, {{ $robotsFollow ? 'follow' : 'nofollow' }}">
    @if(!empty($canonical)) <link rel="canonical" href="{{ $canonical }}"> @endif

    @php $og = (array) data_get($seo, 'og', []); @endphp
    @if(!empty($og))
        @foreach($og as $prop => $value)
            @if(is_string($value) && $value !== '')
                @if(str_starts_with($prop, 'twitter:'))
                    <meta name="{{ $prop }}" content="{{ $value }}">
                @else
                    <meta property="{{ $prop }}" content="{{ $value }}">
                @endif
            @endif
        @endforeach
    @endif
@endpush

@section('content')
    {{-- FULL-BLEED контейнер: выходим из layout-ограничения .container и тянемся на всю ширину экрана --}}
    <div class="relative left-1/2 right-1/2 ml-[-50vw] mr-[-50vw] w-screen
                px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 overflow-hidden">

        {{-- Внутренний центрирующий контейнер: на десктопах ограничиваем разумно --}}
        <div class="mx-auto w-full max-w-full lg:max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl">

            {{-- ЕДИНСТВЕННЫЙ H1 --}}
            <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6 break-words">{{ $pageH1 }}</h1>

            {{-- Категории --}}
            <div class="text-gray-600 dark:text-gray-300 mb-4 sm:mb-6 flex flex-wrap items-center gap-2 text-sm sm:text-base">
                Категории:
                @forelse ($newsItem->categories as $cat)
                    <a href="{{ url('/?category_' . ($newsItem->template ?? 'default') . '=' . $cat->id) }}"
                       class="text-xs sm:text-sm bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded px-2 py-1 hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors">
                        {{ $cat->title }}
                    </a>
                @empty
                    <span class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">Без категории</span>
                @endforelse
            </div>

            {{-- Контент --}}
            <div class="news-content prose prose-gray dark:prose-invert max-w-none">
                {!! $newsItem->content !!}
            </div>

            {{-- Слайдшоу (если прикреплено) --}}
            @if ($newsItem->slideshow && $newsItem->slideshow->items->count())
                <div class="mt-8">
                    @include('Slideshow::public.slideshow', ['slideshow' => $newsItem->slideshow])
                </div>
            @endif

            <div class="mt-6 sm:mt-8">
                <a href="{{ route('news.index') }}" class="text-sm sm:text-base text-blue-600 dark:text-blue-400 hover:underline transition-colors">← Назад к списку</a>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
/* Типографика и адаптивность контента */
.news-content{overflow-wrap:break-word;word-wrap:break-word;word-break:break-word;hyphens:auto}
.news-content img,.news-content video,.news-content iframe,.news-content embed,.news-content object{
    max-width:100%;height:auto;display:block;margin:1rem auto
}
.news-content table{width:100%;overflow-x:auto;display:block;border-collapse:collapse}
.news-content table thead th{font-weight:600}
.news-content pre{white-space:pre-wrap;word-break:break-word}
.news-content a{word-break:break-word}

/* Чуть компактнее вертикальные отступы на мобилках */
@media (max-width: 640px){
  .news-content p{margin:0.75rem 0}
  .news-content h2{font-size:1.25rem;margin-top:1.25rem}
}

/* Темная тема для контента */
.dark .news-content {
    color: #e5e7eb;
}

.dark .news-content h1,
.dark .news-content h2,
.dark .news-content h3,
.dark .news-content h4,
.dark .news-content h5,
.dark .news-content h6 {
    color: #f3f4f6;
}

.dark .news-content a {
    color: #60a5fa;
}

.dark .news-content a:hover {
    color: #93c5fd;
}

.dark .news-content code {
    background-color: #374151;
    color: #f3f4f6;
}

.dark .news-content pre {
    background-color: #1f2937;
    color: #f3f4f6;
}

.dark .news-content blockquote {
    border-left-color: #4b5563;
    color: #d1d5db;
}

.dark .news-content table {
    border-color: #374151;
}

.dark .news-content table th,
.dark .news-content table td {
    border-color: #374151;
}

.dark .news-content table thead {
    background-color: #1f2937;
}
</style>
@endpush
