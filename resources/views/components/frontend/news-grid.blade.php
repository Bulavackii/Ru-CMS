@props([
    'newsList' => [],
    'title' => '',
])

@if (count($newsList))
    <section class="mb-12 w-full">
        {{-- 🔹 Заголовок секции --}}
        @if ($title)
            <h2 class="text-2xl font-bold text-center text-gray-800 dark:text-white mb-6">
                {{ $title }}
            </h2>
        @endif

        {{-- 📰 Сетка карточек новостей --}}
        <div class="flex flex-wrap justify-center gap-6">
            @foreach ($newsList as $news)
                <div class="flex">
                    {{-- 📦 Компонент карточки новости --}}
                    @include('frontend.partials.news-card', ['news' => $news])
                </div>
            @endforeach
        </div>
    </section>
@else
    {{-- ⚠️ Заглушка, если список пуст --}}
    <section class="my-12 text-center text-gray-500 dark:text-gray-400 text-sm italic">
        Пока нет доступных записей для отображения.
    </section>
@endif
