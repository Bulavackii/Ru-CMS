{{-- resources/views/frontend/templates/slideshow.blade.php --}}
@if ($newsList->isNotEmpty())
    @foreach ($newsList as $item)
        @if ($item->slideshow)
            <div class="my-12">
                @include('Slideshow::public.slideshow', ['slideshow' => $item->slideshow])
            </div>
        @endif
    @endforeach
@endif
