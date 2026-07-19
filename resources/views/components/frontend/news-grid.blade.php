@props(['newsList' => [], 'title' => ''])

@if (count($newsList))
    <section class="mb-8 sm:mb-10 md:mb-12 w-full">
        @if ($title)
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-center text-gray-800 dark:text-white mb-4 sm:mb-6 px-4 sm:px-0">
                {{ $title }}
            </h2>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5
                    gap-4 sm:gap-5 md:gap-6 lg:gap-7 xl:gap-8">
            @foreach ($newsList as $news)
                @include('frontend.partials.news-card', ['news' => $news])
            @endforeach
        </div>
    </section>
@else
    <section class="my-8 sm:my-10 md:my-12 text-center text-gray-500 dark:text-gray-400 text-sm sm:text-base italic">
        Пока нет доступных записей для отображения.
    </section>
@endif
