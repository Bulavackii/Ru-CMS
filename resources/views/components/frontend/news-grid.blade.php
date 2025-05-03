@props([
    'newsList' => [],
    'title' => '',
])

<section class="mb-12">
    @if ($title)
        <h2 class="text-2xl font-bold text-center mb-6">{{ $title }}</h2>
    @endif

    @if (count($newsList))
        <div class="grid justify-center gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($newsList as $news)
                <div class="flex">
                    @include('frontend.partials.news-card', ['news' => $news])
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center">Нет записей.</p>
    @endif
</section>
