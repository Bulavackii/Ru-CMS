<div class="my-12">
    @foreach ($newsList as $news)
        @if ($news->slideshow && $news->slideshow->items->count())
            @include('Slideshow::public.slideshow', ['slideshow' => $news->slideshow])
        @endif
    @endforeach
</div>
