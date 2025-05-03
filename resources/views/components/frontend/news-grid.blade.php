@props([
    'newsList' => [],
    'title' => '',
])

@if (count($newsList))
    <section class="mb-12">
        @if ($title)
            <h2 class="text-2xl font-bold text-center mb-6">{{ $title }}</h2>
        @endif

        <div class="flex flex-wrap justify-center gap-6 w-full">
            @foreach ($newsList as $news)
                <div class="flex">
                    @include('frontend.partials.news-card', ['news' => $news])
                </div>
            @endforeach
        </div>
    </section>
@endif
